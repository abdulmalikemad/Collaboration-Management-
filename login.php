<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول | CMT</title>
  <!-- استيراد خط Cairo من Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

  <style>
    /* تعيين الخط العام وتنسيق الجسم الرئيسي للصفحة */
    body {
      font-family: 'Cairo', sans-serif;
      background: #e0f2fe;
      direction: rtl;
      margin: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    /* تنسيق رأس الصفحة */
    header {
      background-color: #1e3a8a;
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 24px;
      font-weight: bold;
    }
    /* منطقة المحتوى الرئيسي في وسط الصفحة */
    main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }
    /* مربع تسجيل الدخول */
    .login-box {
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 500px;
    }
    /* عنوان النموذج */
    .login-box h2 {
      text-align: center;
      color: #1d4ed8;
      margin-bottom: 30px;
    }
    /* تنسيق التسميات */
    label {
      display: block;
      margin-bottom: 8px;
      color: #111827;
      font-weight: bold;
    }
    /* تنسيق حقول الإدخال */
    input {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      margin-bottom: 20px;
      font-size: 16px;
    }
    /* حاوية حقل كلمة المرور مع زر إظهار/إخفاء */
    .password-wrapper {
      position: relative;
    }
    /* زر إظهار/إخفاء كلمة المرور */
    .toggle-password {
      position: absolute;
      left: -17px;
      top: 34%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
    }
    /* زر إرسال النموذج */
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
    /* روابط أسفل نموذج تسجيل الدخول */
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    .back-link a {
      color: #1d4ed8;
      text-decoration: none;
      font-weight: bold;
    }
    /* تنسيق تذييل الصفحة */
    footer {
      background: #f1f5f9;
      text-align: center;
      padding: 15px;
      font-size: 14px;
    }
  </style>
</head>
<body>

  <!-- رأس الصفحة -->
  <header>نظام إدارة المشاريع CMT</header>

  <!-- المحتوى الرئيسي -->
  <main>
    <div class="login-box">
      <h2>تسجيل الدخول</h2>

      <!-- نموذج تسجيل الدخول -->
      <form>
        <!-- حقل رقم القيد -->
        <label for="studentId">رقم القيد</label>
        <input type="text" id="studentId" name="studentId" required>

        <!-- حقل كلمة المرور -->
        <label for="password">كلمة المرور</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" required>
          <button type="button" class="toggle-password" onclick="togglePassword(this)">
            👁
          </button>
        </div>

        <!-- زر إرسال النموذج -->
        <button type="submit">دخول</button>
      </form>

      <!-- روابط إضافية -->
      <div class="back-link">
        <p>ليس لديك حساب؟ <a href="registration.php">إنشاء حساب جديد</a></p>
        <p><a href="index.html">العودة إلى الصفحة الرئيسية</a></p>
      </div>
    </div>
  </main>

  <!-- تذييل الصفحة -->
  <footer>جميع الحقوق محفوظة &copy; 2025 - نظام CMT</footer>

  <!-- سكربت لإظهار وإخفاء كلمة المرور -->
  <script>
    function togglePassword(el) {
      const input = el.parentElement.querySelector('input');
      input.type = input.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>
