<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

$name = $_SESSION['user']['name'];
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>لوحة تحكم الأدمن</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600&display=swap" rel="stylesheet">
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
      padding: 20px 40px;
      font-size: 24px;
      position: relative;
    }

    .logout-btn {
      position: absolute;
      left: 20px;
      top: 20px;
      background: #e53935;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 15px;
      font-weight: bold;
      transition: background 0.3s;
    }

    .logout-btn:hover {
      background: #c62828;
    }

    main {
      max-width: 800px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      text-align: center;
    }

    h2 {
      color: #0d47a1;
      margin-bottom: 30px;
    }

    .btn {
      display: inline-block;
      background: #1e88e5;
      color: white;
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: bold;
      margin: 15px;
      transition: background 0.3s;
    }

    .btn:hover {
      background: #1565c0;
    }
  </style>
</head>
<body>

<header>
   لوحة تحكم الأدمن
  <a href="logout.php" class="logout-btn"> تسجيل الخروج</a>
</header>

<main>
  <h2>مرحبًا، <?= htmlspecialchars($name) ?> </h2>

  <a href="add_supervisor.php" class="btn"> إضافة مشرف جديد</a>
  <a href="manage_supervisors.php" class="btn"> إدارة المشرفين</a>
  <a href="add_admin.php" class="btn"> تسجيل أدمن جديد</a>  <!-- زر تسجيل أدمن -->
  <a href="manage_admins.php" class="btn"> إدارة الأدمن</a>  <!-- زر إدارة الأدمن -->
  <a href="manage_students.php" class="btn"> إدارة الطلبة</a>  <!-- زر إدارة الطلبة -->
</main>

</body>
</html>
