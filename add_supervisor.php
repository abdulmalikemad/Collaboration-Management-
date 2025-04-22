<?php
// بدء جلسة المستخدم
session_start();

// التحقق من أن المستخدم هو أدمن، وإلا يتم تحويله إلى صفحة تسجيل الدخول
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

// استدعاء ملفات الاتصال بالكلاس والقاعدة
require_once 'Database.php';
require_once 'User.php';

// إنشاء اتصال بقاعدة البيانات
$db = new Database();
$conn = $db->connect();

// إنشاء كائن من كلاس المستخدم
$user = new User($conn);

// متغير لتخزين الرسائل (نجاح أو خطأ)
$message = null;

// تنفيذ الكود إذا كان الطلب من نوع POST (يعني تم الضغط على زر الإرسال)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // تجميع البيانات من النموذج في مصفوفة
    $data = [
      'name' => $_POST['name'],
      'studentId' => $_POST['studentId'],
      'email' => $_POST['email'],
      'password' => $_POST['password'],
      'confirmPassword' => $_POST['confirmPassword'],
      'gender' => $_POST['gender'],
      'role' => 'دكتور' // الدور ثابت هنا لأن الصفحة خاصة بإضافة مشرف
    ];

    // استدعاء دالة إضافة مشرف من الكلاس User
    $message = $user->addSupervisor($data);

  } catch (Exception $e) {
    // التقاط أي استثناء (خطأ) وإظهار رسالة
    $message = "❌ حدث خطأ أثناء الإضافة: " . $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إضافة مشرف | CMT</title>

  <!-- استدعاء خط Cairo من Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">

  <!-- تنسيق CSS مباشر داخل الصفحة -->
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background-color: #f9f9f9;
      direction: rtl;
      margin: 0;
    }

    header {
      background-color: #1e3a8a;
      color: white;
      padding: 20px;
      font-size: 24px;
      text-align: center;
    }

    .container {
      max-width: 600px;
      margin: 50px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }

    h2 {
      color: #0d47a1;
      text-align: center;
      margin-bottom: 30px;
    }

    label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
    }

    input, select {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 20px;
      font-size: 16px;
    }

    button {
      background-color: #1e88e5;
      color: white;
      padding: 14px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      width: 100%;
      cursor: pointer;
      font-weight: bold;
    }

    .message {
      text-align: center;
      margin-bottom: 15px;
      font-weight: bold;
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
      background-color: #e0e0e0;
      color: #333;
      padding: 10px 20px;
      text-decoration: none;
      font-weight: bold;
      border-radius: 8px;
      display: inline-block;
    }
  </style>
</head>

<body>

  <!-- عنوان الصفحة -->
  <header>➕ إضافة مشرف جديد</header>

  <!-- الحاوية الرئيسية -->
  <div class="container">
    <h2>معلومات المشرف</h2>

    <!-- عرض رسالة نجاح أو خطأ إن وجدت -->
    <?php if ($message): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <!-- نموذج إدخال بيانات المشرف -->
    <form method="POST">
      <label>الاسم الكامل:</label>
      <input type="text" name="name" required>

      <label>رقم القيد:</label>
      <input type="text" name="studentId" required>

      <label>البريد الإلكتروني:</label>
      <input type="email" name="email" required>

      <label>كلمة المرور:</label>
      <input type="password" name="password" required>

      <label>تأكيد كلمة المرور:</label>
      <input type="password" name="confirmPassword" required>

      <label>الجنس:</label>
      <select name="gender" required>
        <option value="">-- اختر --</option>
        <option value="male">ذكر</option>
        <option value="female">أنثى</option>
      </select>

      <button type="submit"> إضافة المشرف</button>
    </form>

    <!-- زر العودة إلى لوحة التحكم -->
    <div class="back-link">
      <a href="admin_dashboard.php"> العودة</a>
    </div>
  </div>

</body>
</html>

