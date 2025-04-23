<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';

try {
  $db = new Database();
  $conn = $db->connect();

  $project_id = $_GET['id'] ?? null;
  if (!$project_id) {
    throw new Exception("❌ لم يتم تحديد المشروع.");
  }

  // جلب بيانات المشروع
  $stmt = $conn->prepare("
    SELECT p.*, u1.name AS leader_name, u2.name AS supervisor_name 
    FROM projects p 
    JOIN users u1 ON p.leader_id = u1.id 
    JOIN users u2 ON p.supervisor_id = u2.id 
    WHERE p.id = ?
  ");
  if (!$stmt) {
    throw new Exception("فشل في تحضير استعلام المشروع: " . $conn->error);
  }

  $stmt->bind_param("i", $project_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    throw new Exception("❌ المشروع غير موجود.");
  }

  $project = $result->fetch_assoc();

  // جلب الأعضاء (عدا القائد)
  $members_stmt = $conn->prepare("
    SELECT name 
    FROM users 
    WHERE id IN (
      SELECT student_id FROM project_members 
      WHERE project_id = ? AND student_id != ?
    )
  ");
  if (!$members_stmt) {
    throw new Exception("فشل في تحضير استعلام الأعضاء: " . $conn->error);
  }

  $members_stmt->bind_param("ii", $project_id, $project['leader_id']);
  $members_stmt->execute();
  $members_result = $members_stmt->get_result();
  $members = [];
  while ($row = $members_result->fetch_assoc()) {
    $members[] = $row['name'];
  }

} catch (Exception $e) {
  echo "<p style='color:red; text-align:center; font-size:18px;'>" . $e->getMessage() . "</p>";
  exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تفاصيل المشروع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f1f5f9;
      padding: 40px;
      direction: rtl;
    }
    .box {
      max-width: 700px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #0d47a1;
      margin-bottom: 30px;
    }
    p {
      font-size: 18px;
      margin: 10px 0;
    }
    .label {
      font-weight: bold;
      color: #333;
    }
    .pending {
      background-color: #fff3cd;
      padding: 15px;
      border-radius: 10px;
      margin-top: 20px;
      color: #856404;
      text-align: center;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="box">
    <h2>📄 تفاصيل المشروع</h2>

    <p><span class="label">عنوان المشروع:</span> <?= htmlspecialchars($project['title']) ?></p>
    <p><span class="label">الوصف:</span> <?= htmlspecialchars($project['description']) ?></p>
    <p><span class="label">القائد:</span> <?= htmlspecialchars($project['leader_name']) ?></p>
    <p><span class="label">المشرف:</span> <?= htmlspecialchars($project['supervisor_name']) ?></p>
    <p><span class="label">الأعضاء:</span> <?= implode('، ', $members) ?></p>
    <p><span class="label">الحالة:</span> <?= htmlspecialchars($project['status']) ?></p>

    <?php if ($project['status'] === 'بانتظار الموافقة'): ?>
      <div class="pending">📌 المشروع بانتظار موافقة المشرف</div>
    <?php endif; ?>
  </div>
</body>
</html>
