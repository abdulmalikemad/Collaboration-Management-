<?php

session_start(); // بدء الجلسة للتعامل مع الرسائل المرسلة
require_once 'Database.php'; // استيراد ملف الاتصال بقاعدة البيانات
require_once 'User.php'; // استيراد ملف الكود المتعلق بالمستخدم
// إنشاء كائن من قاعدة البيانات
$db = new Database();
$conn = $db->connect(); // الاتصال بقاعدة البيانات

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
  <!-- إضافة خط "Cairo" من Google Fonts لتنسيق النصوص -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500&display=swap" rel="stylesheet">
  <style>
    /* تنسيق جسم الصفحة */
    body {
      font-family: 'Cairo', sans-serif;
      margin: 0;
      background: linear-gradient(to right, #e3f2fd, #f1f5f9); /* خلفية متدرجة من الأزرق الفاتح */
      direction: rtl; /* تغيير اتجاه النص إلى اليمين */
    }

    /* تنسيق الرأس */
    header {
      background-color: #1e3a8a; /* لون الخلفية للأزرق الداكن */
      color: white; /* لون النص باللون الأبيض */
      padding: 20px;
      text-align: center;
      font-size: 26px;
      font-weight: bold;
    }

    /* تنسيق الحاوية التي تحتوي على النموذج */
    .container {
      max-width: 600px;
      margin: 50px auto;
      background-color: white; /* الخلفية البيضاء */
      padding: 40px;
      border-radius: 16px; /* الزوايا المدورة */
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1); /* الظل حول الحاوية */
    }

    /* تنسيق العنوان */
    h2 {
      color: #0d47a1; /* لون العنوان الأزرق */
      text-align: center;
      margin-bottom: 30px;
    }

    /* تنسيق الحقول المدخلة */
    label {
      display: block;
      font-weight: bold;
      margin-bottom: 6px;
      margin-top: 15px;
      color: #333; /* لون النص */
    }

    input, select {
      width: 100%; /* جعل الحقول تأخذ كامل العرض */
      padding: 12px;
      border-radius: 10px; /* الزوايا المدورة */
      border: 1px solid #ccc; /* لون الحدود */
      margin-bottom: 16px;
      font-size: 16px;
      transition: border-color 0.3s ease; /* تأثير التغيير في لون الحدود عند التركيز */
    }

    /* تغيير لون الحدود عند تركيز المستخدم على الحقول */
    input:focus, select:focus {
      border-color: #3b82f6; /* اللون الأزرق عند التركيز */
      outline: none; /* إزالة الحد الخارجي الافتراضي */
    }

    /* تنسيق زر الإرسال */
    button {
      background-color: #3b82f6; /* اللون الأزرق للزر */
      color: white;
      border: none;
      border-radius: 10px; /* الزوايا المدورة */
      padding: 14px;
      font-size: 17px;
      font-weight: bold;
      width: 100%; /* عرض الزر 100% */
      cursor: pointer;
      transition: background 0.3s; /* تأثير التغيير في اللون عند المرور بالماوس */
    }

    /* تغيير لون الزر عند المرور عليه بالماوس */
    button:hover {
      background-color: #1d4ed8; /* اللون الأزرق الداكن عند التمرير */
    }

    /* تنسيق الفوتر */
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
  <!-- رأس الصفحة -->
  <header>📘 إنشاء حساب جديد - نظام CMT</header>

  <!-- الحاوية التي تحتوي على النموذج -->
  <div class="container">
    <h2>✍️ التسجيل</h2>

    <!-- نموذج التسجيل -->
    <form method="POST" action="registration.php">
      <!-- حقل الاسم الكامل -->
      <label>الاسم الكامل:</label>
      <input type="text" name="name" required>

      <!-- حقل رقم القيد -->
      <label>رقم القيد:</label>
      <input type="text" name="studentId" required>

      <!-- حقل البريد الإلكتروني -->
      <label>البريد الإلكتروني:</label>
      <input type="email" name="email" required>

      <!-- حقل كلمة المرور -->
      <label>كلمة المرور:</label>
      <input type="password" name="password" required>

      <!-- حقل تأكيد كلمة المرور -->
      <label>تأكيد كلمة المرور:</label>
      <input type="password" name="confirmPassword" required>

      <!-- حقل اختيار الجنس -->
      <label>الجنس:</label>
      <select name="gender" required>
        <option value="">-- اختر --</option>
        <option value="ذكر">ذكر</option>
        <option value="أنثى">أنثى</option>
      </select>

      <!-- زر إرسال النموذج -->
      <button type="submit">📥 إنشاء الحساب</button>
    </form>
  </div>

  <!-- الفوتر -->
  <footer>
    جميع الحقوق محفوظة &copy; 2025 - نظام إدارة المشاريع CMT
  </footer>
</body>
</html>
