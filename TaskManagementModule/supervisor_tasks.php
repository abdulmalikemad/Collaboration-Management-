<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'دكتور') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once __DIR__ . '/TaskManager.php';
require_once __DIR__ . '/TaskComment.php';
require_once __DIR__ . '/../FileManagementModule/FileManager.php';

$db = new Database();
$conn = $db->connect();
$taskManager = new TaskManager($conn);
$commentManager = new TaskComment($conn);
$fileManager = new FileManager($conn);

$supervisor_id = $_SESSION['user']['id'];
$supervisor_name = $_SESSION['user']['name'];
$message = null;

// تأجيل المهمة مع تعديل الحالة إذا كانت مغلقة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postpone_task'], $_POST['new_due_date'])) {
  $taskId = (int) $_POST['postpone_task'];
  $newDueDate = $_POST['new_due_date'];

  try {
    // أولاً: جلب الحالة الحالية للمهمة
    $currentStatus = null;
    $statusStmt = $conn->prepare("SELECT status FROM tasks WHERE id = ?");
    if (!$statusStmt) throw new Exception("فشل في تجهيز استعلام الحالة.");
    $statusStmt->bind_param("i", $taskId);
    $statusStmt->execute();
    $statusStmt->bind_result($currentStatus);
    $statusStmt->fetch();
    $statusStmt->close();

    // تحديث الموعد وربما الحالة إذا كانت مغلقة
    if ($currentStatus === 'مغلقة') {
      $stmt = $conn->prepare("UPDATE tasks SET due_date = ?, status = 'جارية' WHERE id = ?");
    } else {
      $stmt = $conn->prepare("UPDATE tasks SET due_date = ? WHERE id = ?");
    }

    if (!$stmt) throw new Exception("فشل في تجهيز استعلام التأجيل.");
    $stmt->bind_param("si", $newDueDate, $taskId);

    if ($stmt->execute()) {
      $message = "✅ تم تأجيل الموعد النهائي بنجاح.";
    } else {
      $message = "❌ حدث خطأ أثناء التأجيل.";
    }

  } catch (Exception $e) {
    $message = "❌ خطأ: " . $e->getMessage();
  }
}
// إضافة تعليق
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['comment']) && !isset($_POST['postpone_task'])) {
  $task_id = (int) $_POST['task_id'];
  $comment = trim($_POST['comment']);
  if ($comment !== '') {
    $commentManager->add($task_id, $supervisor_id, 'دكتور', $comment);
    $message = "✅ تم إضافة تعليقك بنجاح.";
  }
}

$project_id = $_GET['project_id'] ?? null;
$filter_status = $_GET['status'] ?? 'all';

$tasksResult = $project_id
  ? $taskManager->getTasksByProject($project_id, $filter_status)
  : $taskManager->getTasksForSupervisor($supervisor_id, $filter_status);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📋 مهام المشاريع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f0f4f8; direction: rtl; margin: 0; }
    header {
      background: #1e3a8a;
      color: white;
      padding: 25px;
      font-size: 26px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    main {
      max-width: 1100px;
      margin: 40px auto;
      padding: 30px;
      background: white;
      border-radius: 14px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    h2 {
      color: #1e40af;
      text-align: center;
      margin-bottom: 30px;
    }
    .success {
      background-color: #e8f5e9;
      border: 1px solid #81c784;
      color: #2e7d32;
      padding: 12px;
      margin-bottom: 25px;
      border-radius: 8px;
      text-align: center;
      font-weight: bold;
    }
    form select {
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-family: 'Cairo', sans-serif;
    }
    .task-box {
      background: #f9fafb;
      padding: 25px;
      border: 1px solid #ccc;
      border-radius: 12px;
      margin-bottom: 35px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .task-box h3 {
      color: #1565c0;
      margin-bottom: 8px;
    }
    .status {
      font-weight: bold;
      padding: 6px 10px;
      border-radius: 8px;
      display: inline-block;
      margin-bottom: 10px;
    }
    .status-new {
      background: #fff8e1;
      color: #f9a825;
      border: 1px solid #fbc02d;
    }
    .status-progress {
      background: #e3f2fd;
      color: #1565c0;
      border: 1px solid #64b5f6;
    }
    .status-closed {
      background: #eeeeee;
      color: #616161;
      border: 1px solid #bdbdbd;
    }
    .status-complete {
      background: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #81c784;
    }
    .file-entry, .file-box, .comment-entry {
      margin-top: 10px;
      padding: 10px;
      border-radius: 8px;
      background: #f1f1f1;
    }
    .file-box {
      background: #e3f2fd;
      margin-top: 6px;
    }
    .file-box a {
      font-weight: bold;
      color: #1e88e5;
      text-decoration: none;
    }
    .comment-entry span {
      font-weight: bold;
      color: #0d47a1;
    }
    textarea, input[type="date"] {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-family: 'Cairo', sans-serif;
    }
    button {
      padding: 10px 20px;
      margin-top: 10px;
      background-color: #1e88e5;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
    button:hover {
      background-color: #1565c0;
    }
    .no-tasks {
      text-align: center;
      color: #888;
      font-size: 16px;
    }
  </style>
</head>
<body>
<header>📋 إدارة المهام لجميع مشاريعك</header>
<main>
  <h2>المهام التي يشرف عليها د. <?= htmlspecialchars($supervisor_name) ?></h2>

  <form method="GET" style="text-align:center;">
    <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">
    <select name="status" onchange="this.form.submit()">
      <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>كل المهام</option>
      <option value="جديدة" <?= $filter_status === 'جديدة' ? 'selected' : '' ?>>المهام الجديدة</option>
      <option value="جارية" <?= $filter_status === 'جارية' ? 'selected' : '' ?>>المهام الجارية</option>
      <option value="مغلقة" <?= $filter_status === 'مغلقة' ? 'selected' : '' ?>>المهام المغلقة</option>
      <option value="مكتملة" <?= $filter_status === 'مكتملة' ? 'selected' : '' ?>>المهام المكتملة</option>
    </select>
  </form>

  <?php if ($message): ?>
    <div class="success"><?= $message ?></div>
  <?php endif; ?>

  <?php if ($tasksResult && $tasksResult->num_rows > 0): ?>
    <?php $taskIndex = 1; ?>
    <?php while ($task = $tasksResult->fetch_assoc()): ?>
      <div class="task-box">
        <h3>📝 المهمة #<?= $taskIndex ?> - <?= htmlspecialchars($task['title']) ?></h3>
        <button type="button" onclick="toggleTaskDetails(<?= $task['id'] ?>)">👁️ عرض/إخفاء التفاصيل</button>

        <div id="task-details-<?= $task['id'] ?>" style="display:none; margin-top: 15px;">
          <?php
            $status = $task['status'];
            switch ($status) {
              case 'جديدة': $statusClass = 'status-new'; break;
              case 'جارية': $statusClass = 'status-progress'; break;
              case 'مغلقة': $statusClass = 'status-closed'; break;
              case 'مكتملة': $statusClass = 'status-complete'; break;
              default: $statusClass = '';
            }
            echo "<div class='status $statusClass'>الحالة: $status</div>";
          ?>

          <p>📅 من: <?= $task['start_date'] ?> إلى: <?= $task['due_date'] ?></p>

          <h4>👥 الطلاب المكلفون وملفاتهم:</h4>
          <?php
            $stmt = $conn->prepare("SELECT u.id, u.name FROM task_assignments ta JOIN users u ON ta.student_id = u.id WHERE ta.task_id = ?");
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
                    echo "<a href='{$file['file_path']}' download>📅 تحميل الملف</a> ";
                    echo "<small style='color:#555;'>⏱ {$file['upload_date']}</small>";
                    echo "</div>";
                  }
                } else {
                  echo " — <span style='color:#b71c1c;'>لا يوجد ملف</span>";
                }

                echo "</div>";
              }
            } else {
              echo "<p class='no-tasks'>❌ لا يوجد طلاب مكلّفون بهذه المهمة.</p>";
            }
          ?>

          <h4>💬 التعليقات:</h4>
          <?php
            $comments = $commentManager->getByTask($task['id']);
            if ($comments && $comments->num_rows > 0) {
              while ($comment = $comments->fetch_assoc()) {
                echo "<div class='comment-entry'>";
                echo "<span>" . htmlspecialchars($comment['name']) . " ({$comment['role']})</span>: ";
                echo htmlspecialchars($comment['comment']);
                echo "<div style='font-size:13px; color:#666;'>🕒 {$comment['created_at']}</div>";
                echo "</div>";
              }
            } else {
              echo "<p class='no-tasks'>لا توجد تعليقات.</p>";
            }
          ?>

          <form method="POST">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            <textarea name="comment" placeholder="💬 اكتب تعليقك هنا..." required></textarea>
            <button type="submit">➕ إضافة تعليق</button>
          </form>

          <form method="POST">
            <input type="hidden" name="postpone_task" value="<?= $task['id'] ?>">
            <label style="margin-top:10px;">🕒 تأجيل تاريخ التسليم:</label>
            <input type="date" name="new_due_date" required>
            <button type="submit">⏳ تأجيل</button>
          </form>
        </div>
      </div>
      <?php $taskIndex++; ?>
    <?php endwhile; ?>
  <?php else: ?>
    <p class="no-tasks">❌ لا توجد مهام حاليًا.</p>
  <?php endif; ?>
</main>

<script>
function toggleTaskDetails(taskId) {
  const el = document.getElementById('task-details-' + taskId);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

</body>
</html>
