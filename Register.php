<?php

session_start(); // بدء الجلسة للتعامل مع الرسائل المرسلة
require_once 'Database.php'; // استيراد ملف الاتصال بقاعدة البيانات
require_once 'User.php'; // استيراد ملف الكود المتعلق بالمستخدم
// إنشاء كائن من فئة User
$user = new User($conn);
$success = null; // متغير لتخزين الرسالة الناجحة

// إذا كان الطلب من نوع POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // محاولة تسجيل المستخدم
    $result = $user->register($_POST);
    
    // إذا كانت النتيجة تحتوي على رمز ✅
    if (str_starts_with($result, '✅')) {
      $success = $result; // حفظ الرسالة الناجحة
    } else {
      $_SESSION['register_error'] = $result; // حفظ رسالة الخطأ في الجلسة
      header("Location: registration.php"); // إعادة التوجيه إلى نفس الصفحة
      exit();
    }
  } catch (Exception $e) {
    // في حالة حدوث استثناء (خطأ)
    $_SESSION['register_error'] = "❌ حدث خطأ أثناء التسجيل."; // حفظ رسالة الخطأ في الجلسة
    header("Location: registration.php"); // إعادة التوجيه إلى نفس الصفحة
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إنشاء حساب | CMT</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500&display=swap" rel="stylesheet">
  <style>
    /* تنسيق الصفحة */
    body {
      font-family: 'Cairo', sans-serif;
      margin: 0;
      background: linear-gradient(to right, #e3f2fd, #f1f5f9);
      direction: rtl;
    }
    header {
      background-color: #1e3a8a;
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 26px;
      font-weight: bold;
    }
    .container {
      max-width: 600px;
      margin: 50px auto;
      background-color: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
    }
    h2 {
      color: #0d47a1;
      text-align: center;
      margin-bottom: 30px;
    }
    label {
      display: block;
      font-weight: bold;
      margin-bottom: 6px;
      margin-top: 15px;
      color: #333;
    }
    input, select {
      width: 100%;
      padding: 12px;
      border-radius: 10px;
      border: 1px solid #ccc;
      margin-bottom: 16px;
      font-size: 16px;
      transition: border-color 0.3s ease;
    }
    input:focus, select:focus {
      border-color: #3b82f6;
      outline: none;
    }
    button {
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 10px;
      padding: 14px;
      font-size: 17px;
      font-weight: bold;
      width: 100%;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover {
      background-color: #1d4ed8;
    }
    .message {
      text-align: center;
      padding: 12px;
      font-weight: bold;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .error {
      background-color: #ffebee;
      color: #c62828;
      border: 1px solid #f44336;
    }
    .success {
      background-color: #d4edda;
      color: #2e7d32;
      border: 1px solid #43a047;
    }
    footer {
      text-align: center;
      font-size: 14px;
      padding: 20px;
      color: #666;
      margin-top: 40px;
    }
  </style>
</head>
<body>
  <header>📘 إنشاء حساب جديد - نظام CMT</header>

  <div class="container">
    <h2>✍️ التسجيل</h2>

    <!-- عرض رسائل الخطأ أو النجاح -->
    <?php if (isset($_SESSION['register_error'])): ?>
      <div class="message error"><?= $_SESSION['register_error']; unset($_SESSION['register_error']); ?></div>
    <?php elseif ($success): ?>
      <div class="message success"><?= $success ?></div>
    <?php endif; ?>

    <!-- نموذج التسجيل -->
    <form method="POST" action="registration.php">
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
        <option value="ذكر">ذكر</option>
        <option value="أنثى">أنثى</option>
      </select>

      <button type="submit">📥 إنشاء الحساب</button>
    </form>
  </div>

  <footer>
    جميع الحقوق محفوظة &copy; 2025 - نظام إدارة المشاريع CMT
  </footer>
</body>
</html>
