<?php
session_start(); // بدء جلسة لتخزين بيانات المستخدم بعد تسجيل الدخول

require_once 'Database.php'; // استدعاء ملف الاتصال بقاعدة البيانات
require_once 'User.php';     // استدعاء كلاس المستخدم

$db = new Database();
$conn = $db->connect(); // الاتصال بقاعدة البيانات

$user = new User($conn); // إنشاء كائن من كلاس User
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // التحقق من أن الطلب تم إرساله باستخدام POST
  try {
    $message = $user->login($_POST); // محاولة تسجيل الدخول باستخدام بيانات النموذج

    // إذا تم تسجيل المستخدم وتم تحديد دوره
    if (isset($_SESSION['user']) && isset($_SESSION['user']['role'])) {
      $role = $_SESSION['user']['role'];

      // التوجيه حسب نوع المستخدم
      if ($role === 'طالب') {
        header("Location: student_dashboard.php");
        exit();
      } elseif ($role === 'دكتور') {
        header("Location: supervisor_dashboard.php");
        exit();
      } elseif ($role === 'ادمن') {
        header("Location: admin_dashboard.php");
        exit();
      } else {
        // دور غير معروف
        $_SESSION['login_error'] = "دور المستخدم غير معروف.";
        header("Location: login.php");
        exit();
      }

    } else {
      // لم يتم تسجيل الدخول
      $_SESSION['login_error'] = $message;
      header("Location: login.php");
      exit();
    }

  } catch (Exception $e) {
    // حدث خطأ أثناء المعالجة
    $_SESSION['login_error'] = "حدث خطأ أثناء محاولة تسجيل الدخول.";
    header("Location: login.php");
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول | CMT</title>

  <!-- استدعاء خط جميل للواجهة -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

  <!-- تنسيق CSS للواجهة -->
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #e0f2fe;
      direction: rtl;
      margin: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    header {
      background-color: #1e3a8a;
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 24px;
      font-weight: bold;
    }
    main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }
    .login-box {
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 500px;
    }
    .login-box h2 {
      text-align: center;
      color: #1d4ed8;
      margin-bottom: 30px;
    }
    label {
      display: block;
      margin-bottom: 8px;
      color: #111827;
      font-weight: bold;
    }
    input {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      margin-bottom: 20px;
      font-size: 16px;
    }
    .password-wrapper {
      position: relative;
    }
    .toggle-password {
      position: absolute;
      left: -17px;
      top: 34%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
    }
    button[type="submit"] {
      width: 100%;
      background-color: #3b82f6;
      color: white;
      padding: 14px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
    }
    button[type="submit"]:hover {
      background-color: #2563eb;
    }
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    .back-link a {
      color: #1d4ed8;
      text-decoration: none;
      font-weight: bold;
    }
    .error {
      background-color: #ffebee;
      color: #c62828;
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-weight: bold;
      text-align: center;
    }
    footer {
      background: #f1f5f9;
      text-align: center;
      padding: 15px;
      font-size: 14px;
    }
  </style>
</head>
<body>

  <!-- ترويسة النظام -->
  <header>نظام إدارة المشاريع CMT</header>

  <main>
    <div class="login-box">
      <h2>تسجيل الدخول</h2>

      <!-- عرض رسالة الخطأ إن وُجدت -->
      <?php if (isset($_SESSION['login_error'])): ?>
        <div class="error"><?= htmlspecialchars($_SESSION['login_error']) ?></div>
        <?php unset($_SESSION['login_error']); ?>
      <?php endif; ?>

      <!-- نموذج تسجيل الدخول -->
      <form method="POST" action="login.php">
        <label for="studentId">رقم القيد</label>
        <input type="text" id="studentId" name="studentId" required>

        <label for="password">كلمة المرور</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" required>
          <!-- زر عرض/إخفاء كلمة المرور -->
          <button type="button" class="toggle-password" onclick="togglePassword(this)">
            👁
          </button>
        </div>

        <!-- زر الدخول -->
        <button type="submit">دخول</button>
      </form>

      <!-- روابط مساعدة -->
      <div class="back-link">
        <p>ليس لديك حساب؟ <a href="Register.php">إنشاء حساب جديد</a></p>
        <p><a href="index.html">العودة إلى الصفحة الرئيسية</a></p>
      </div>
    </div>
  </main>

  <!-- التذييل -->
  <footer>جميع الحقوق محفوظة &copy; 2025 - نظام CMT</footer>

  <!-- سكربت عرض كلمة المرور -->
  <script>
    function togglePassword(el) {
      const input = el.parentElement.querySelector('input');
      input.type = input.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>
