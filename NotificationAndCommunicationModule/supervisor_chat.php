<?php
session_start();

// ✅ التحقق من أن المستخدم مسجل كـ "دكتور"
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'دكتور') {
  header("Location: login.php");
  exit();
}

// ✅ استدعاء الملفات الضرورية
require_once '../UserManagementModule/Database.php';
require_once('ProjectChatManager.php');

// ✅ استخراج معلومات الدكتور والمشروع من الجلسة والرابط
$supervisor_id = $_SESSION['user']['id'];
$supervisor_name = $_SESSION['user']['name'];
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

$errorMsg = ""; // لتخزين رسالة الخطأ
$project = null; // بيانات المشروع
$messages = []; // الرسائل المرتبطة بالمشروع

try {
  // ✅ الاتصال بقاعدة البيانات
  $db = new Database();
  $conn = $db->connect();
  $chatManager = new ProjectChatManager($conn);

  // ✅ التأكد من أن الدكتور مشرف على هذا المشروع
  $check = $conn->prepare("SELECT title FROM projects WHERE id = ? AND supervisor_id = ?");
  if (!$check) throw new Exception("فشل تجهيز استعلام المشروع.");
  $check->bind_param("ii", $project_id, $supervisor_id);
  if (!$check->execute()) throw new Exception("فشل تنفيذ استعلام المشروع.");
  $result = $check->get_result();
  $project = $result->fetch_assoc();

  if (!$project) {
    throw new Exception("🚫 لا يمكنك الوصول إلى هذه المحادثة.");
  }

  // ✅ إذا تم إرسال رسالة جديدة
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']); // إزالة الفراغات
    if ($message !== '') {
      if (!$chatManager->sendProjectMessage($project_id, $supervisor_id, 'دكتور', $message)) {
        throw new Exception("❌ فشل في إرسال الرسالة.");
      }
      // ✅ إعادة تحميل الصفحة بعد الإرسال لتفادي التكرار
      header("Location: supervisor_chat.php?project_id=$project_id");
      exit();
    }
  }

  // ✅ جلب جميع رسائل المحادثة لهذا المشروع
  $messages = $chatManager->getProjectMessages($project_id);
  if ($messages === false) {
    throw new Exception("❌ فشل في جلب الرسائل.");
  }

} catch (Exception $e) {
  $errorMsg = $e->getMessage(); // التقاط رسالة الخطأ
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>💬 محادثة المشروع - <?= $project ? htmlspecialchars($project['title']) : '—' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f0f0f0;
      margin: 0;
      direction: rtl;
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
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .message {
      padding: 10px 15px;
      margin: 10px 0;
      border-radius: 10px;
      max-width: 75%;
      clear: both;
    }
    .from-me {
      background-color: #e3f2fd; /* رسائل الدكتور */
      margin-right: auto;
      text-align: left;
    }
    .from-them {
      background-color: #fff9c4; /* رسائل الطلاب */
      margin-left: auto;
      text-align: right;
    }
    .message small {
      display: block;
      font-size: 12px;
      color: #555;
      margin-top: 5px;
    }
    form {
      margin-top: 30px;
      display: flex;
      gap: 10px;
    }
    input[type="text"] {
      flex: 1;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 16px;
    }
    button {
      background-color: #1e88e5;
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }
    .back {
      text-align: center;
      margin-top: 20px;
    }
    .back a {
      color: #0d47a1;
      text-decoration: none;
      font-weight: bold;
    }
    .error {
      color: red;
      text-align: center;
      font-weight: bold;
      margin: 15px 0;
    }
  </style>
</head>
<body>

<header>💬 محادثة المشروع: <?= $project ? htmlspecialchars($project['title']) : '—' ?></header>

<div class="chat-container">

  <?php if ($errorMsg): ?>
    <!-- ✅ عرض رسالة الخطأ إذا وُجدت -->
    <div class="error"><?= htmlspecialchars($errorMsg) ?></div>

  <?php elseif ($messages): ?>
    <!-- ✅ عرض الرسائل واحدة تلو الأخرى -->
    <?php while ($row = $messages->fetch_assoc()): ?>
      <div class="message <?= $row['sender_id'] === $supervisor_id ? 'from-me' : 'from-them' ?>">
        <strong><?= htmlspecialchars($row['sender_name'] ?? 'غير معروف') ?> (<?= htmlspecialchars($row['sender_role']) ?>)</strong><br>
        <?= nl2br(htmlspecialchars($row['message'])) ?>
        <small><?= $row['created_at'] ?></small>
      </div>
    <?php endwhile; ?>

    <!-- ✅ نموذج إرسال رسالة جديدة -->
    <form method="POST">
      <input type="text" name="message" placeholder="✍️ اكتب رسالتك هنا..." required>
      <button type="submit">📤 إرسال</button>
    </form>
  <?php endif; ?>

  <!-- ✅ زر للعودة لصفحة المشاريع الفعّالة -->
  <div class="back">
    <p><a href="../ProjectManagementModule/supervisor_active.php">⬅️ العودة للمشاريع</a></p>
  </div>

</div>

</body>
</html>
