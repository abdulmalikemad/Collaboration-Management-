<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'دكتور') {
  header("Location: login.php");
  exit();
}

$name = $_SESSION['user']['name'];
$id = $_SESSION['user']['id'];

require_once 'Database.php';
require_once 'Project.php';

try {
  $db = new Database();
  $conn = $db->connect();

  $project = new Project($conn);
  $pendingProjects = $project->getPendingProjectsForSupervisor($id);

} catch (Exception $e) {
  die("<p style='text-align:center; color:red;'>❌ خطأ في الاتصال أو جلب البيانات: {$e->getMessage()}</p>");
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>لوحة المشرف</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      margin: 0;
      background: #f0f4f8;
      direction: rtl;
    }
    header {
      position: relative;
      background-color: #1e3a8a;
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 26px;
      font-weight: bold;
    }
    .logout-link {
      position: absolute;
      top: 20px;
      left: 20px;
      color: #fff;
      text-decoration: none;
      font-weight: bold;
      background-color: #d32f2f;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 15px;
      transition: background-color 0.3s ease;
    }
    .logout-link:hover {
      background-color: #b71c1c;
    }
    main {
      max-width: 850px;
      margin: 40px auto;
      padding: 30px;
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      text-align: center;
    }
    .welcome {
      font-size: 22px;
      margin-bottom: 30px;
      color: #0d47a1;
    }
    .action-button {
      display: inline-block;
      background: linear-gradient(to left, #42a5f5, #1e88e5);
      color: white;
      padding: 14px 30px;
      font-size: 18px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }
    .action-button:hover {
      background-color: #1565c0;
    }
  </style>
</head>
<body>
  <header>
    لوحة المشرف - نظام إدارة المشاريع CMT
    <a href="logout.php" class="logout-link"> تسجيل الخروج</a>
  </header>

  <main>
    <div class="welcome">مرحبًا بك دكتور <?= htmlspecialchars($name) ?></div>

    <?php if ($pendingProjects && $pendingProjects->num_rows > 0): ?>
      <div style="
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 25px;
        font-size: 17px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
      ">
        <svg xmlns="http://www.w3.org/2000/svg" fill="#ffc107" height="24" viewBox="0 0 24 24" width="24" style="flex-shrink: 0;">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 22c1.1 0 1.99-.9 1.99-2h-4a2 2 0 002.01 2zM18.29 17H5.71c-.45 0-.67-.54-.35-.85l1.8-1.79V9c0-3.07 1.64-5.64 4.5-6.32V2.5a1.5 1.5 0 113 0v.18c2.86.68 4.5 3.25 4.5 6.32v5.36l1.8 1.79c.32.31.1.85-.35.85z"/>
        </svg>
        <span><strong>لديك <?= $pendingProjects->num_rows ?> مشروع/مشاريع</strong> بانتظار الموافقة</span>
        <a href="supervisor_requests.php?id=<?= $id ?>" style="
          background-color: #1e88e5;
          color: white;
          padding: 6px 14px;
          border-radius: 8px;
          text-decoration: none;
          font-size: 15px;
          font-weight: bold;
        ">عرض الطلبات</a>
      </div>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: 20px; align-items: center;">
      <a class="action-button" href="supervisor_requests.php?id=<?= $id ?>">
        📌 عرض طلبات المشاريع بانتظار الموافقة
      </a>
      <a class="action-button" href="supervisor_active.php?id=<?= $id ?>" style="background: linear-gradient(to left, #66bb6a, #43a047);">
        📂 عرض المشاريع الجارية
      </a>
    </div>
  </main>
</body>
</html>
