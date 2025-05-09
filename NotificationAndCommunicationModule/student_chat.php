<?php
session_start();

// ✅ التحقق من تسجيل الدخول وأن المستخدم هو طالب
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: ../UserManagementModule/login.php");
  exit();
}

// ✅ استدعاء ملفات الاتصال بقاعدة البيانات وكلاس المحادثة
require_once '../UserManagementModule/Database.php';
require_once 'ProjectChatManager.php';

$studentId = $_SESSION['user']['id'];  // رقم الطالب من الجلسة
$project = null;
$messages = [];
$errorMsg = "";  // لتخزين الأخطاء إن وجدت

try {
  // ✅ فتح الاتصال بقاعدة البيانات
  $db = new Database();
  $conn = $db->connect();

  // ✅ إنشاء كائن لإدارة المحادثة
  $chatManager = new ProjectChatManager($conn);

  // ✅ جلب المشروع المرتبط بالطالب (سواء كعضو أو كقائد)
  $stmt = $conn->prepare("
    SELECT p.*, u.name AS supervisor_name
    FROM projects p
    JOIN users u ON p.supervisor_id = u.id
    WHERE p.id IN (
      SELECT project_id FROM project_members WHERE student_id = ?
      UNION
      SELECT id FROM projects WHERE leader_id = ?
    )
    ORDER BY p.id DESC
    LIMIT 1
  ");
  if (!$stmt) throw new Exception("فشل في تجهيز استعلام المشروع.");

  $stmt->bind_param("ii", $studentId, $studentId);
  if (!$stmt->execute()) throw new Exception("فشل في تنفيذ استعلام المشروع.");

  $projectResult = $stmt->get_result();
  $project = $projectResult->fetch_assoc();

  if (!$project) throw new Exception("🚫 لا يوجد مشروع مرتبط بك.");

  $projectId = $project['id'];  // رقم المشروع الحالي

  // ✅ عند إرسال رسالة جديدة من النموذج
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
      // إرسال الرسالة
      if (!$chatManager->sendProjectMessage($projectId, $studentId, 'طالب', $message)) {
        throw new Exception("❌ فشل في إرسال الرسالة.");
      }
      // إعادة التوجيه لمنع تكرار الإرسال
      header("Location: student_chat.php");
      exit();
    }
  }

  // ✅ جلب جميع رسائل المحادثة الخاصة بالمشروع
  $messagesResult = $chatManager->getProjectMessages($projectId);
  if ($messagesResult === false) {
    throw new Exception("❌ فشل في تحميل الرسائل.");
  }

} catch (Exception $e) {
  $errorMsg = $e->getMessage();  // تخزين رسالة الخطأ للعرض
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>💬 دردشة المشروع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f4f4f4;
      direction: rtl;
      margin: 0;
    }
    header {
      background: #1e3a8a;
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 22px;
    }
    .chat-container {
      max-width: 800px;
      margin: 30px auto;
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .message {
      background-color: #e3f2fd;
      margin: 10px 0;
      padding: 10px;
      border-radius: 10px;
    }
    .message.supervisor {
      background-color: #fff3cd;
      border: 1px solid #ffecb5;
    }
    .message strong { color: #0d47a1; }
    .message small {
      display: block;
      color: #555;
      font-size: 12px;
      margin-top: 5px;
    }
    form {
      display: flex;
      margin-top: 30px;
      gap: 10px;
    }
    input[type="text"] {
      flex: 1;
      padding: 12px;
      font-size: 16px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    button {
      background-color: #1e88e5;
      color: white;
      padding: 12px 20px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }
    .error {
      color: red;
      text-align: center;
      font-weight: bold;
      margin: 20px 0;
    }
  </style>
</head>
<body>

<header>
  💬 دردشة المشروع:
  <?= isset($project['title']) ? htmlspecialchars($project['title']) : '—' ?>
</header>

<div class="chat-container">

  <?php if ($errorMsg): ?>
    <!-- ✅ عرض رسالة الخطأ إن وجدت -->
    <div class="error"><?= htmlspecialchars($errorMsg) ?></div>

  <?php elseif ($messagesResult): ?>
    <!-- ✅ عرض رسائل المحادثة -->
    <?php while ($row = $messagesResult->fetch_assoc()): ?>
      <?php
        // ✅ تمييز رسائل المشرف بلون مختلف
        $role = strtolower(trim($row['sender_role']));
        $isSupervisor = in_array($role, ['مشرف', 'supervisor', 'دكتور']);
        $messageClass = $isSupervisor ? 'message supervisor' : 'message';
      ?>
      <div class="<?= $messageClass ?>">
        <strong>
          <?= htmlspecialchars($row['sender_name'] ?? 'غير معروف') ?> (<?= htmlspecialchars($row['sender_role']) ?>):
        </strong>
        <div><?= nl2br(htmlspecialchars($row['message'])) ?></div>
        <small>🕒 <?= $row['created_at'] ?></small>
      </div>
    <?php endwhile; ?>

    <!-- ✅ نموذج إرسال رسالة جديدة -->
    <form method="POST">
      <input type="text" name="message" placeholder="✍️ اكتب رسالتك هنا..." required>
      <button type="submit">📤 إرسال</button>
    </form>
  <?php endif; ?>

</div>

</body>
</html>
