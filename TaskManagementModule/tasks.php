<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: ../UserManagementModule/login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once '../TaskManagementModule/TaskManager.php';

$userName = "الطالب";
$tasks = null;
$project = null;

try {
  $db = new Database();
  $conn = $db->connect();
  $taskManager = new TaskManager($conn);
  $taskManager->autoCloseOverdueTasks();

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

  $tasks = $isLeader
    ? $taskManager->getTasksByProject($project_id)
    : $taskManager->getTasksForStudent($userId);

} catch (Exception $e) {
  echo "<p style='color:red; text-align:center;'>❌ حدث خطأ: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📋 مهام المشروع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background-color: #f0f2f5;
      margin: 0;
      direction: rtl;
    }

    header {
      background-color: #1e40af;
      color: #fff;
      padding: 30px 0;
      text-align: center;
      font-size: 28px;
      font-weight: bold;
      letter-spacing: 1px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    main {
      max-width: 1000px;
      margin: 40px auto;
      padding: 30px;
      background-color: #ffffff;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    h2 {
      color: #1e40af;
      text-align: center;
      margin-bottom: 10px;
    }

    h3 {
      color: #333;
      text-align: center;
      margin-top: 30px;
      margin-bottom: 20px;
    }

    .tasks-container {
      display: flex;
      flex-wrap: wrap;
      gap: 25px;
      justify-content: center;
    }

    .task-card {
      width: 280px;
      background: #f9f9f9;
      border-radius: 16px;
      border-right: 6px solid #ccc;
      padding: 20px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }

    .task-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }

    .task-card h4 {
      margin: 0 0 10px;
      font-size: 20px;
      color: #0d47a1;
    }

    .task-card p {
      margin: 6px 0;
      color: #444;
      font-size: 15px;
    }

    .upload-btn {
      display: inline-block;
      margin-top: 14px;
      padding: 10px 18px;
      background-color: #1e88e5;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
      transition: background-color 0.2s ease;
    }

    .upload-btn:hover {
      background-color: #1565c0;
    }

    .status {
      font-weight: bold;
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-block;
      margin-top: 8px;
    }

    .status.done {
      background-color: #c8e6c9;
      color: #2e7d32;
    }

    .status.late {
      background-color: #ffcdd2;
      color: #c62828;
    }

    .status.warning {
      background-color: #fff3cd;
      color: #f57c00;
    }

    .status.active {
      background-color: #e3f2fd;
      color: #1976d2;
    }

    .no-tasks {
      text-align: center;
      color: #777;
      font-size: 16px;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<header>📋 مهام المشروع - <?= htmlspecialchars($project['title'] ?? 'لا يوجد') ?></header>

<main>
  <h2>👋 مرحبًا <?= htmlspecialchars($userName) ?></h2>
  <h3>📋 قائمة المهام</h3>

  <?php if ($tasks && $tasks->num_rows > 0): ?>
    <div class="tasks-container">
      <?php while ($task = $tasks->fetch_assoc()): ?>
        <?php
          $today = new DateTime();
          $due = new DateTime($task['due_date']);
          $diff = (int)$today->diff($due)->format("%r%a");

          // جلب الطلاب المخصصين للمهمة
          $assignedStmt = $conn->prepare("SELECT student_id FROM task_assignments WHERE task_id = ?");
          $assignedStmt->bind_param("i", $task['id']);
          $assignedStmt->execute();
          $assignedResult = $assignedStmt->get_result();

          $assignedStudents = [];
          while ($row = $assignedResult->fetch_assoc()) {
              $assignedStudents[] = $row['student_id'];
          }

          // جلب الملفات المرفوعة وأسماء الطلاب
          $fileStmt = $conn->prepare("SELECT tf.student_id, tf.upload_date, u.name AS student_name 
                                      FROM task_files tf 
                                      JOIN users u ON tf.student_id = u.id 
                                      WHERE tf.task_id = ?");
          $fileStmt->bind_param("i", $task['id']);
          $fileStmt->execute();
          $fileResult = $fileStmt->get_result();

          $uploadedStudents = [];
          $fileDetails = [];
          while ($row = $fileResult->fetch_assoc()) {
              $uploadedStudents[] = $row['student_id'];
              $fileDetails[$row['student_id']] = $row;
          }

          $allSubmitted = !array_diff($assignedStudents, $uploadedStudents);

          if ($allSubmitted) {
              $statusClass = "done"; $statusText = "✅ تم الاستلام"; $borderColor = "#4caf50";
          } elseif ($diff < 0) {
              $statusClass = "late"; $statusText = "❌ متأخرة"; $borderColor = "#f44336";
          } elseif ($diff <= 3) {
              $statusClass = "warning"; $statusText = "⚠️ اقترب التسليم"; $borderColor = "#ff9800";
          } else {
              $statusClass = "active"; $statusText = "🟢 تحت المتابعة"; $borderColor = "#42a5f5";
          }
        ?>

        <div class="task-card" style="border-right-color: <?= $borderColor ?>;">
          <h4>📝 <?= htmlspecialchars($task['title']) ?></h4>
          <p>🗓️ <?= $task['start_date'] ?> - <?= $task['due_date'] ?></p>
          <span class="status <?= $statusClass ?>"><?= $statusText ?></span><br>

          <a class="upload-btn" href="../FileManagementModule/upload_file.php?task=<?= $task['id'] ?>">
            📤 رفع / تعديل الملف
          </a>

          <div style="margin-top: 12px;">
            <strong>👥 الطلاب المكلفين:</strong>
            <ul style="padding-right: 18px; margin-top: 6px; color: #333;">
              <?php
              foreach ($assignedStudents as $studentId) {
                $getNameStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                $getNameStmt->bind_param("i", $studentId);
                $getNameStmt->execute();
                $nameResult = $getNameStmt->get_result();
                $studentName = $nameResult->fetch_assoc()['name'] ?? 'طالب';

                $hasSubmitted = in_array($studentId, $uploadedStudents);
                $uploadDate = $fileDetails[$studentId]['upload_date'] ?? null;
                $submittedText = $hasSubmitted ? "<span style='color:green;'>✅ (سلّم - ".date('Y-m-d', strtotime($uploadDate)).")</span>" : "<span style='color:red;'>❌ (لم يسلّم)</span>";

                echo "<li>" . htmlspecialchars($studentName) . " $submittedText</li>";
              }
              ?>
            </ul>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="no-tasks">🚫 لا توجد مهام حتى الآن.</p>
  <?php endif; ?>
</main>

</body>
</html>
