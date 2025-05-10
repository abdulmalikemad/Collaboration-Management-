<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: ../UserManagementModule/login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';

//  تهيئة المتغيرات بشكل مسبق لتفادي التحذيرات
$notifications = false;
$reminderTasks = false;
$errorMsg = "";

$studentId = $_SESSION['user']['id'];

try {
  $db = new Database();
  $conn = $db->connect();

  //  تحديث الإشعارات غير المقروءة إلى مقروءة
  $updateStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
  $updateStmt->bind_param("i", $studentId);
  $updateStmt->execute();

  //  جلب الإشعارات
  $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
  if (!$stmt) throw new Exception("فشل تجهيز استعلام الإشعارات.");
  $stmt->bind_param("i", $studentId);
  if (!$stmt->execute()) throw new Exception("فشل تنفيذ استعلام الإشعارات.");
  $notifications = $stmt->get_result();

  // حساب تاريخ الغد
  $tomorrow = (new DateTime('+1 day'))->format('Y-m-d');

  // جلب المهام التي تنتهي غدًا
  $reminderStmt = $conn->prepare("
    SELECT t.title, t.due_date
    FROM tasks t
    JOIN task_assignments ta ON ta.task_id = t.id
    WHERE ta.student_id = ? AND DATE(t.due_date) = ?
  ");
  if (!$reminderStmt) throw new Exception("فشل تجهيز استعلام التذكير بالمهمات.");
  $reminderStmt->bind_param("is", $studentId, $tomorrow);
  if (!$reminderStmt->execute()) throw new Exception("فشل تنفيذ استعلام المهام.");
  $reminderTasks = $reminderStmt->get_result();

} catch (Exception $e) {
  $errorMsg = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>🔔 الإشعارات</title>
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f9f9f9;
      direction: rtl;
      padding: 30px;
    }
    .notif {
      background: #fff;
      border: 1px solid #ccc;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .unread {
      border-right: 6px solid #1e88e5;
    }
    .reminder {
      background-color: #fff8e1;
      border-right: 6px solid #ffa000;
    }
    .time {
      color: #888;
      font-size: 13px;
      margin-top: 5px;
    }
    .error {
      color: red;
      font-weight: bold;
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <h2>🔔 إشعاراتك</h2>

  <!--  عرض الخطأ في حال حدوثه -->
  <?php if (!empty($errorMsg)): ?>
    <div class="error">❌ <?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <!--  عرض تذكيرات المهام التي تنتهي غدًا -->
  <?php if ($reminderTasks && $reminderTasks->num_rows > 0): ?>
    <?php while ($task = $reminderTasks->fetch_assoc()): ?>
      <div class="notif reminder">
        ⏰ <strong>تذكير: لديك مهمة تنتهي غدًا</strong><br>
        📌 <?= htmlspecialchars($task['title']) ?>
        <div class="time">🗓️ <?= $task['due_date'] ?></div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>

  <!--  عرض الإشعارات من جدول notifications -->
  <?php if ($notifications && $notifications->num_rows > 0): ?>
    <?php while ($n = $notifications->fetch_assoc()): ?>
      <div class="notif <?= $n['is_read'] == 0 ? 'unread' : '' ?>">
        <strong><?= htmlspecialchars($n['message']) ?></strong>
        <div class="time">🕒 <?= $n['created_at'] ?></div>
      </div>
    <?php endwhile; ?>
  <?php elseif (!$errorMsg): ?>
    <p>🚫 لا توجد إشعارات حالياً.</p>
  <?php endif; ?>

</body>
</html>
