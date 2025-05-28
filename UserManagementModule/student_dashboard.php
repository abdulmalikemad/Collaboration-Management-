<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';

try {
  $db = new Database();
  $conn = $db->connect();
  $studentId = $_SESSION['user']['id'];

  // ✅ جلب عدد الإشعارات الغير مقروءة
  $notifStmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
  $notifStmt->bind_param("i", $studentId);
  $notifStmt->execute();
  $notifCountResult = $notifStmt->get_result()->fetch_assoc();
  $unreadCount = $notifCountResult['unread_count'];

  // ✅ جلب معلومات المشروع
  $stmt = $conn->prepare("
    SELECT p.*, u.name AS supervisor_name
    FROM projects p
    JOIN users u ON p.supervisor_id = u.id
    WHERE p.id IN (
      SELECT project_id FROM project_members WHERE student_id = ?
      UNION
      SELECT id FROM projects WHERE leader_id = ?
    )
    ORDER BY p.id DESC
    LIMIT 1
  ");
  if (!$stmt) throw new Exception("فشل في تجهيز الاستعلام.");
  $stmt->bind_param("ii", $studentId, $studentId);
  $stmt->execute();
  $result = $stmt->get_result();
  $project = $result->fetch_assoc();

} catch (Exception $e) {
  echo "<p style='color:red; text-align:center;'>❌ حدث خطأ أثناء تحميل البيانات: " . $e->getMessage() . "</p>";
  exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>لوحة الطالب | CMT</title>
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
      font-size: 24px;
      font-weight: bold;
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .logout-btn {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      background-color: #d32f2f;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      font-size: 14px;
      transition: background-color 0.3s;
    }
    .logout-btn:hover { background-color: #b71c1c; }

    .notif-btn {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      background-color: <?= $unreadCount > 0 ? '#ff7043' : '#757575' ?>;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      font-size: 14px;
      transition: background-color 0.3s;
    }

    .notif-btn:hover { background-color: #e65100; }

    main {
      max-width: 800px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    h2 { color: #0d47a1; text-align: center; }
    .project-box {
      border: 1px solid #ccc;
      padding: 20px;
      border-radius: 10px;
      background-color: #f9f9f9;
    }
    .status {
      font-weight: bold;
      margin-top: 15px;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
    }
    .pending { background-color: #fff3cd; color: #856404; }
    .approved { background-color: #d4edda; color: #155724; }
    .rejected { background-color: #f8d7da; color: #721c24; }
    .reason {
      margin-top: 15px;
      padding: 12px;
      background-color: #fdecea;
      color: #b71c1c;
      border-radius: 8px;
      border: 1px solid #f5c6cb;
    }
    .action-button {
      display: inline-block;
      background-color: #1e88e5;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.3s;
    }
    .action-button:hover { background-color: #1565c0; }
    .actions {
      margin-top: 30px;
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
    }
  </style>
</head>
<body>

<header>
  لوحة الطالب - نظام إدارة المشاريع CMT
  <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
  <a href="../NotificationAndCommunicationModule/notifications.php" class="notif-btn">
    🔔 الإشعارات <?= $unreadCount > 0 ? "($unreadCount)" : "" ?>
  </a>
</header>

<main>
  <h2> مرحبًا <?= htmlspecialchars($_SESSION['user']['name']) ?></h2>

  <?php if ($project): ?>
    <?php
      $status = trim($project['status']);
      $statusClass = ($status === 'بانتظار الموافقة') ? 'pending' :
                     (($status === 'تمت الموافقة') ? 'approved' : 'rejected');
      $isLeader = ($_SESSION['user']['id'] == $project['leader_id']);
    ?>
    <div class="project-box">
      <h3>📌 عنوان المشروع: <?= htmlspecialchars($project['title']) ?></h3>
      <p>📝 الوصف: <?= nl2br(htmlspecialchars($project['description'])) ?></p>
      <p>👨‍🏫 المشرف: <?= htmlspecialchars($project['supervisor_name']) ?></p>
      <div class="status <?= $statusClass ?>">الحالة: <?= htmlspecialchars($status) ?></div>

      <?php if ($status === 'مرفوض' && !empty($project['rejection_reason'])): ?>
        <div class="reason">
          📌 <strong>سبب الرفض:</strong><br>
          <?= nl2br(htmlspecialchars($project['rejection_reason'])) ?>
        </div>
      <?php endif; ?>

      <?php if ($status === 'تمت الموافقة'): ?>
        <div class="actions">
          <a href="../TaskManagementModule/tasks.php" class="action-button">📋 عرض المهام</a>
          <?php if ($isLeader): ?>
            <a href="../TaskManagementModule/add_task.php" class="action-button">➕ إضافة مهمة</a>
          <?php endif; ?>
          <a href="../NotificationAndCommunicationModule/student_chat.php?project_id=<?= $project['id'] ?>" class="action-button">💬 دردشة المشروع</a>
        </div>
      <?php elseif ($status === 'مرفوض'): ?>
        <div class="actions">
          <a href="../ProjectManagementModule/create_project.php" class="action-button">➕ تسجيل مشروع جديد</a>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div style="text-align:center; margin-top: 40px;">
      <a href="../ProjectManagementModule/create_project.php" class="action-button">➕ تسجيل مشروع جديد</a>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
