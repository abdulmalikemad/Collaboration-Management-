<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'دكتور') {
  header("Location: login.php");
  exit();
}
require_once '../UserManagementModule/Database.php';
try {
  $db = new Database();
  $conn = $db->connect();

  $supervisor_id = $_SESSION['user']['id'];
  $supervisor_name = $_SESSION['user']['name'];

  $stmt = $conn->prepare("
    SELECT p.*, u.name AS leader_name, u.id AS leader_id
    FROM projects p
    JOIN users u ON p.leader_id = u.id
    WHERE p.supervisor_id = ? AND p.status = 'تمت الموافقة'
  ");
  if (!$stmt) throw new Exception("فشل في تجهيز استعلام المشاريع.");
  $stmt->bind_param("i", $supervisor_id);
  $stmt->execute();
  $result = $stmt->get_result();

} catch (Exception $e) {
  die("<p style='color:red; text-align:center;'>❌ حدث خطأ: {$e->getMessage()}</p>");
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📂 المشاريع الجارية</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f0f4f8;
      margin: 0;
      direction: rtl;
    }
    header {
      background-color: #1e3a8a;
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 24px;
      font-weight: bold;
    }
    .container {
      max-width: 900px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }
    h2 {
      text-align: center;
      color: #0d47a1;
      margin-bottom: 20px;
    }
    .project-box {
      border: 1px solid #ddd;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      background-color: #f9f9f9;
    }
    .project-box h3 {
      margin: 0 0 10px;
      color: #1565c0;
    }
    .project-box p {
      margin: 6px 0;
      color: #444;
    }
    .btn {
      display: inline-block;
      background-color: #1e88e5;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: 0.3s;
      margin-top: 10px;
      margin-left: 8px;
    }
    .btn:hover {
      background-color: #1565c0;
    }
    .chat-btn {
      background-color: #ff7043;
      position: relative;
    }
    .chat-btn:hover {
      background-color: #e64a19;
    }
    .notif {
      background: red;
      color: white;
      font-size: 13px;
      padding: 2px 7px;
      border-radius: 50%;
      position: absolute;
      top: -8px;
      left: -10px;
    }
  </style>
</head>
<body>
  <header>📂 المشاريع الجارية تحت إشراف: <?= htmlspecialchars($supervisor_name) ?></header>
  <div class="container">
    <h2>قائمة المشاريع الموافق عليها</h2>

    <?php if ($result->num_rows === 0): ?>
      <p style="text-align:center; color:#777;">🚫 لا توجد مشاريع جارية حاليًا.</p>
    <?php else: ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="project-box">
          <h3><?= htmlspecialchars($row['title']) ?></h3>
          <p>👤 قائد الفريق: <?= htmlspecialchars($row['leader_name']) ?></p>
          <p>📅 المدة: <?= $row['start_date'] ?> إلى <?= $row['end_date'] ?></p>

          <?php
          try {
            // جلب أسماء الأعضاء باستثناء القائد
            $member_stmt = $conn->prepare("
              SELECT u.name FROM project_members pm
              JOIN users u ON pm.student_id = u.id
              WHERE pm.project_id = ? AND u.id != ?
            ");
            $member_stmt->bind_param("ii", $row['id'], $row['leader_id']);
            $member_stmt->execute();
            $members_result = $member_stmt->get_result();

            $member_names = [];
            while ($m = $members_result->fetch_assoc()) {
              $member_names[] = htmlspecialchars($m['name']);
            }

            // عدد الرسائل غير المقروءة
            $msg_stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM messages WHERE project_id = ? AND is_read = 0 AND sender_id != ?");
            $msg_stmt->bind_param("ii", $row['id'], $supervisor_id);
            $msg_stmt->execute();
            $msg_result = $msg_stmt->get_result();
            $unread = $msg_result->fetch_assoc()['unread'];
          } catch (Exception $e) {
            $member_names = ["خطأ في تحميل الأعضاء"];
            $unread = 0;
          }
          ?>

          <p>👥 أعضاء الفريق: <?= implode("، ", $member_names) ?></p>

          <a class="btn" href="supervisor_tasks.php?project_id=<?= $row['id'] ?>">📋 عرض المهام</a>
          <a class="btn chat-btn" href="supervisor_chat.php?project_id=<?= $row['id'] ?>">
            💬 الدردشة <?= $unread > 0 ? "<span class='notif'>$unread</span>" : "" ?>
          </a>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</body>
</html>
