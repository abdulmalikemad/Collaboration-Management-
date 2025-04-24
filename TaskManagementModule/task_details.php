<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'دكتور') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once 'TaskManager.php';
require_once 'CommentManager.php';
$db = new Database();
$conn = $db->connect();

$supervisor_id = $_SESSION['user']['id'];
$task_id = isset($_GET['task_id']) ? (int) $_GET['task_id'] : 0;

// ✅ تحقق أن المهمة تابعة لمشروع يشرف عليه هذا الدكتور
$authStmt = $conn->prepare("
  SELECT p.title AS project_title, t.title AS task_title, t.start_date, t.due_date
  FROM tasks t
  JOIN projects p ON p.id = t.project_id
  WHERE t.id = ? AND p.supervisor_id = ?
");
$authStmt->bind_param("ii", $task_id, $supervisor_id);
$authStmt->execute();
$authResult = $authStmt->get_result();
if ($authResult->num_rows === 0) {
  die("<p style='color:red; text-align:center;'>🚫 لا تملك صلاحية لعرض هذه المهمة.</p>");
}
$taskInfo = $authResult->fetch_assoc();

// ✅ استدعاء الكلاسات
$taskManager = new TaskManager($conn);
$commentManager = new CommentManager($conn);

// ✅ إضافة تعليق
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['comment'])) {
  $comment = trim($_POST['comment']);
  $commentManager->addComment($task_id, $supervisor_id, $_SESSION['user']['role'], $comment);
  header("Location: task_details.php?task_id=$task_id");
  exit();
}

// ✅ جلب الملفات والتعليقات
$files = $taskManager->getTaskFiles($task_id);
$comments = $commentManager->getCommentsByTask($task_id);
$students = $taskManager->getAssignedStudents($task_id);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📋 تفاصيل المهمة</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; direction: rtl; background: #f4f6f9; margin: 0; }
    header { background: #1e3a8a; color: white; padding: 20px; text-align: center; font-size: 24px; }
    .container { max-width: 900px; margin: 30px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
    h2 { color: #0d47a1; text-align: center; margin-bottom: 30px; }
    .section { margin-bottom: 25px; }
    .file-entry, .comment-entry {
      padding: 12px; border-radius: 10px; margin-bottom: 10px;
    }
    .file-entry { background: #e3f2fd; border: 1px solid #90caf9; }
    .comment-entry { background: #f1f1f1; border: 1px solid #ccc; }
    .comment-entry span { font-weight: bold; color: #1565c0; }
    textarea {
      width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 10px;
    }
    button {
      padding: 10px 20px; background-color: #1e88e5; color: white;
      border: none; border-radius: 8px; font-weight: bold; cursor: pointer;
      margin-top: 10px;
    }
  </style>
</head>
<body>
<header>📋 تفاصيل المهمة: <?= htmlspecialchars($taskInfo['task_title']) ?></header>
<div class="container">
  <h2>المشروع: <?= htmlspecialchars($taskInfo['project_title']) ?></h2>

  <div class="section">
    <p>📅 من: <?= $taskInfo['start_date'] ?> - إلى: <?= $taskInfo['due_date'] ?></p>
    <p>👥 الطلاب المكلفين: <?= implode("، ", array_map(fn($s) => htmlspecialchars($s['name']), $students)) ?></p>
  </div>

  <div class="section">
    <h3>📁 الملفات المرفوعة:</h3>
    <?php if (count($files) > 0): ?>
      <?php foreach ($files as $f): ?>
        <div class="file-entry">
          👤 <?= htmlspecialchars($f['student_name']) ?> |
          ⏱ <?= $f['upload_date'] ?> |
          <a href="<?= $f['file_path'] ?>" download>📥 تحميل</a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:#888;">لا توجد ملفات مرفوعة.</p>
    <?php endif; ?>
  </div>

  <div class="section">
    <h3>💬 التعليقات:</h3>
    <?php if (count($comments) > 0): ?>
      <?php foreach ($comments as $c): ?>
        <div class="comment-entry">
          <span><?= htmlspecialchars($c['commenter_name']) ?> (<?= $c['role'] ?>)</span>: <?= htmlspecialchars($c['comment']) ?>
          <div style="font-size: 13px; color: #777;">🕒 <?= $c['created_at'] ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:#888;">لا توجد تعليقات بعد.</p>
    <?php endif; ?>

    <form method="POST">
      <textarea name="comment" placeholder="✍️ اكتب تعليقك هنا..." required></textarea>
      <button type="submit">➕ إضافة تعليق</button>
    </form>
  </div>
</div>
</body>
</html>
