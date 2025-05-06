<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'دكتور') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once __DIR__ . '/TaskManager.php';
require_once __DIR__ . '/CommentManager.php';
require_once __DIR__ . '/../FileManagementModule/FileManager.php';

$db = new Database();
$conn = $db->connect();
$taskManager = new TaskManager($conn);
$commentManager = new CommentManager($conn);
$fileManager = new FileManager($conn);

$supervisor_id = $_SESSION['user']['id'];
$supervisor_name = $_SESSION['user']['name'];
$message = null;

// تأجيل المهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postpone_task'], $_POST['new_due_date'])) {
  $taskId = (int) $_POST['postpone_task'];
  $newDueDate = $_POST['new_due_date'];
  $stmt = $conn->prepare("UPDATE tasks SET due_date = ? WHERE id = ?");
  $stmt->bind_param("si", $newDueDate, $taskId);
  $message = $stmt->execute() ? "✅ تم تأجيل الموعد النهائي بنجاح." : "❌ حدث خطأ أثناء التأجيل.";
}

// إضافة تعليق
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['comment']) && !isset($_POST['postpone_task'])) {
  $task_id = (int) $_POST['task_id'];
  $comment = trim($_POST['comment']);
  if ($comment !== '') {
    $commentManager->addComment($task_id, $supervisor_id, 'دكتور', $comment);
    $message = "✅ تم إضافة تعليقك بنجاح.";
  }
}

$project_id = $_GET['project_id'] ?? null;
$tasksResult = $project_id
  ? $taskManager->getTasksByProject($project_id)
  : $taskManager->getTasksForSupervisor($supervisor_id);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📋 مهام المشاريع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f1f5f9; direction: rtl; margin: 0; }
    header { background-color: #1e3a8a; color: white; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; }
    main { max-width: 1000px; margin: 30px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #0d47a1; margin-bottom: 30px; }
    .task-box { border: 1px solid #ddd; padding: 20px; border-radius: 12px; margin-bottom: 25px; background-color: #f9f9f9; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .file-entry, .comment-entry {
      background-color: #f1f1f1; border: 1px solid #ccc;
      margin-top: 10px; padding: 10px; border-radius: 8px; font-size: 15px;
    }
    .file-box {
      margin-top: 8px; padding: 6px 10px; background: #e8f5e9; border-radius: 6px;
    }
    .comment-entry span, .file-entry span { font-weight: bold; color: #0d47a1; }
    .download-btn {
      background-color: #388e3c; color: white; padding: 5px 10px;
      text-decoration: none; border-radius: 6px; font-weight: bold;
    }
    textarea, input[type="date"] {
      width: 100%; padding: 10px; border-radius: 8px;
      border: 1px solid #ccc; margin-top: 10px; font-family: 'Cairo', sans-serif;
    }
    button {
      margin-top: 8px; padding: 10px 20px;
      background-color: #1e88e5; color: white;
      border: none; border-radius: 8px; font-weight: bold; cursor: pointer;
    }
    .success { color: green; text-align: center; margin-bottom: 15px; font-weight: bold; }
  </style>
</head>
<body>
<header>📋 إدارة المهام لجميع مشاريعك</header>
<main>
  <h2>المهام التي يشرف عليها د. <?= htmlspecialchars($supervisor_name) ?></h2>
  <?php if ($message): ?>
    <p class="success"><?= $message ?></p>
  <?php endif; ?>

  <?php if ($tasksResult && $tasksResult->num_rows > 0): ?>
    <?php while ($task = $tasksResult->fetch_assoc()): ?>
      <div class="task-box">
        <h2 style="margin: 0 0 10px; color: #1e88e5;"> <?= htmlspecialchars($task['title']) ?></h2>
        <p style="color: #555; font-size: 14px;">📅 من: <?= $task['start_date'] ?> إلى: <?= $task['due_date'] ?></p>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

        <h4 style="color: #0d47a1;">👥 الطلاب المكلّفون وملفاتهم:</h4>
        <?php
          $stmt = $conn->prepare("
            SELECT u.id, u.name 
            FROM task_assignments ta 
            JOIN users u ON ta.student_id = u.id 
            WHERE ta.task_id = ?
          ");
          $stmt->bind_param("i", $task['id']);
          $stmt->execute();
          $assigned = $stmt->get_result();

          if ($assigned->num_rows > 0) {
            while ($student = $assigned->fetch_assoc()) {
              echo "<div class='file-entry'>";
              echo "👤 <strong>{$student['name']}</strong>";

              $files = $fileManager->getFilesForStudent($task['id'], $student['id']);
              if (!empty($files)) {
                foreach ($files as $file) {
                  echo "<div class='file-box'>";
                  echo "<a class='download-btn' href='{$file['file_path']}' download>📥 تحميل</a>";
                  echo " <small style='color:#555;'>⏱ {$file['upload_date']}</small>";
                  echo "</div>";
                }
              } else {
                echo " — <span style='color:#b71c1c;'>لا يوجد ملف</span>";
              }

              echo "</div>";
            }
          } else {
            echo "<p style='color:#777;'> لا يوجد طلاب مكلّفون بهذه المهمة.</p>";
          }
        ?>

        <h4 style="margin-top: 25px; color: #0d47a1;">💬 التعليقات:</h4>
        <?php
          $comments = $commentManager->getCommentsByTask($task['id']);
          if ($comments && $comments->num_rows > 0) {
            while ($comment = $comments->fetch_assoc()) {
              echo "<div class='comment-entry'>";
              echo "<span>{$comment['commenter_name']} ({$comment['role']})</span>: ";
              echo htmlspecialchars($comment['comment']);
              echo "<div style='font-size:13px; color:#666;'>🕒 {$comment['created_at']}</div>";
              echo "</div>";
            }
          } else {
            echo "<p style='color:#777;'>لا توجد تعليقات بعد.</p>";
          }
        ?>

        <form method="POST">
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
          <textarea name="comment" placeholder="💬 اكتب تعليقك هنا..." required></textarea>
          <button type="submit">➕ إضافة تعليق</button>
        </form>

        <form method="POST" style="margin-top:10px;">
          <input type="hidden" name="postpone_task" value="<?= $task['id'] ?>">
          <label>🕒 تأجيل تاريخ التسليم:</label>
          <input type="date" name="new_due_date" required>
          <button type="submit">⏳ تأجيل</button>
        </form>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center; color:#777;"> لا توجد مهام حاليًا.</p>
  <?php endif; ?>
</main>
</body>
</html>
