<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once 'Task.php';

try {
  $db = new Database();
  $conn = $db->connect();
  $taskObj = new Task($conn);
  $taskObj->autoCloseOverdueTasks();

  $userId = $_SESSION['user']['id'];
  $userName = $_SESSION['user']['name'];
  $isLeader = false;
  $project_id = null;

  $stmt = $conn->prepare("
    SELECT p.*, p.leader_id AS leader, u.name AS supervisor_name 
    FROM project_members pm
    JOIN projects p ON pm.project_id = p.id
    JOIN users u ON p.supervisor_id = u.id
    WHERE pm.student_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $projectResult = $stmt->get_result();
  $project = $projectResult->fetch_assoc();

  if ($project) {
    $project_id = $project['id'];
    $isLeader = ($userId == $project['leader']);
  }

  $tasks = $isLeader ? $taskObj->getTasksByProject($project_id) : $taskObj->getTasksForStudent($userId);
} catch (Exception $e) {
  die("<p style='color:red; text-align:center;'>❌ حدث خطأ: " . $e->getMessage() . "</p>");
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📋 مهام المشروع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f1f5f9; direction: rtl; margin: 0; }
    header { background-color: #1e3a8a; color: white; text-align: center; padding: 20px; font-size: 24px; }
    main { max-width: 900px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    h2 { color: #0d47a1; text-align: center; }
    .upload-btn { background-color: #4caf50; padding: 8px 16px; border-radius: 6px; color: white; font-weight: bold; text-decoration: none; display: inline-block; margin-top: 10px; }
  </style>
</head>
<body>
<header>📋 مهام المشروع - <?= htmlspecialchars($project['title'] ?? 'لا يوجد') ?></header>
<main>
  <h2>👋 مرحبًا <?= htmlspecialchars($userName) ?></h2>

  <h3 style="text-align:center;">📋 قائمة المهام</h3>

  <?php if ($tasks && $tasks->num_rows > 0): ?>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
      <?php while ($task = $tasks->fetch_assoc()): ?>
        <?php
          $today = new DateTime();
          $due = new DateTime($task['due_date']);
          $diff = (int)$today->diff($due)->format("%r%a");

          $isAssigned = false;
          $check = $conn->prepare("SELECT id FROM task_assignments WHERE task_id = ? AND student_id = ?");
          $check->bind_param("ii", $task['id'], $userId);
          $check->execute();
          $assigned = $check->get_result();
          $isAssigned = $assigned->num_rows > 0;

          $fileCheck = $conn->prepare("SELECT file_path, upload_date FROM task_files WHERE task_id = ? AND student_id = ?");
          $fileCheck->bind_param("ii", $task['id'], $userId);
          $fileCheck->execute();
          $fileRes = $fileCheck->get_result();
          $file = $fileRes->fetch_assoc();

          if ($file) {
            $cardColor = "#e8f5e9"; $borderColor = "#4caf50"; $status = "✅ تم التسليم";
          } elseif ($diff < 0) {
            $cardColor = "#ffebee"; $borderColor = "#f44336"; $status = "❌ متأخرة";
          } elseif ($diff <= 3) {
            $cardColor = "#fff8e1"; $borderColor = "#ff9800"; $status = "⚠️ اقترب التسليم";
          } else {
            $cardColor = "#e3f2fd"; $borderColor = "#42a5f5"; $status = "🟢 تحت المتابعة";
          }
        ?>
        <div style="background: <?= $cardColor ?>; border-right: 6px solid <?= $borderColor ?>; width: 280px; padding: 20px; border-radius: 14px;">
          <h4>📝 <?= htmlspecialchars($task['title']) ?></h4>
          <p>🗓️ <?= $task['start_date'] ?> - <?= $task['due_date'] ?></p>
          <p>📌 الحالة: <?= $status ?></p>
          <a class="upload-btn" href="upload_file.php?task=<?= $task['id'] ?>">
  <?= $isAssigned ? " رفع / تعديل الملف" : " عرض التفاصيل" ?>
</a>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p style="text-align:center; color:#888;">🚫 لا توجد مهام حتى الآن.</p>
  <?php endif; ?>
</main>
</body>
</html>
