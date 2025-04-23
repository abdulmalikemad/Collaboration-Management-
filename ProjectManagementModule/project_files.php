<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: ../UserManagementModule/login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';

try {
  $db = new Database();
  $conn = $db->connect();
  $userId = $_SESSION['user']['id'];

  // جلب بيانات المشروع المرتبط بالطالب
  $stmt = $conn->prepare("
    SELECT p.id, p.title
    FROM projects p
    JOIN project_members pm ON pm.project_id = p.id
    WHERE pm.student_id = ?
    LIMIT 1
  ");
  if (!$stmt) throw new Exception("خطأ في تحضير استعلام المشروع.");
  
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  $project = $result->fetch_assoc();

  if (!$project) {
    throw new Exception("❌ لا يوجد مشروع مرتبط بحسابك.");
  }

  $projectId = $project['id'];

} catch (Exception $e) {
  echo "<p style='color:red; text-align:center; font-size:18px;'>" . $e->getMessage() . "</p>";
  exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📁 ملفات المشروع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background-color: #f5f7fa;
      direction: rtl;
      margin: 0;
    }
    header {
      background-color: #1e3a8a;
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 24px;
    }
    .container {
      max-width: 900px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    h2 {
      color: #0d47a1;
      margin-bottom: 20px;
      text-align: center;
    }
    .task-block {
      border: 1px solid #ddd;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 25px;
      background-color: #f9f9f9;
    }
    .task-block h3 {
      margin-top: 0;
      color: #1565c0;
    }
    .file-entry {
      background-color: #eef6ff;
      padding: 10px;
      margin: 8px 0;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .file-entry span {
      font-weight: bold;
    }
    .file-entry a {
      background-color: #1e88e5;
      color: white;
      padding: 6px 12px;
      text-decoration: none;
      border-radius: 6px;
    }
    .file-entry a:hover {
      background-color: #1565c0;
    }
  </style>
</head>
<body>
  <header>📁 ملفات المشروع - <?= htmlspecialchars($project['title']) ?></header>
  <div class="container">
    <h2>👥 جميع الملفات المرفوعة من أعضاء المشروع</h2>

    <?php
    try {
      $tasks = $conn->prepare("SELECT id, title FROM tasks WHERE project_id = ?");
      if (!$tasks) throw new Exception("فشل في تحضير استعلام المهام.");
      
      $tasks->bind_param("i", $projectId);
      $tasks->execute();
      $taskResult = $tasks->get_result();

      if ($taskResult->num_rows === 0) {
        echo "<p style='text-align:center; color:#777;'>🚫 لا توجد مهام مرتبطة بالمشروع حتى الآن.</p>";
      } else {
        while ($task = $taskResult->fetch_assoc()) {
          echo "<div class='task-block'>";
          echo "<h3>📌 المهمة: " . htmlspecialchars($task['title']) . "</h3>";

          $files = $conn->prepare("
            SELECT tf.file_path, tf.upload_date, u.name 
            FROM task_files tf
            JOIN users u ON tf.student_id = u.id
            WHERE tf.task_id = ?
          ");
          if (!$files) throw new Exception("فشل في تحضير استعلام الملفات.");
          
          $files->bind_param("i", $task['id']);
          $files->execute();
          $fileResult = $files->get_result();

          if ($fileResult->num_rows === 0) {
            echo "<p style='color:#999;'>⛔ لا توجد ملفات مرفوعة لهذه المهمة بعد.</p>";
          } else {
            while ($file = $fileResult->fetch_assoc()) {
              echo "<div class='file-entry'>";
              echo "<span>👤 " . htmlspecialchars($file['name']) . " — " . date("Y-m-d", strtotime($file['upload_date'])) . "</span>";
              echo "<a href='" . $file['file_path'] . "' download>📥 عرض الملف</a>";
              echo "</div>";
            }
          }

          echo "</div>";
        }
      }

    } catch (Exception $e) {
      echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
    }
    ?>
  </div>
</body>
</html>
