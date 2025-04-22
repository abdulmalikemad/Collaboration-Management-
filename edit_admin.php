<?php
session_start();
require_once 'Database.php';
require_once 'User.php';

//  تأكد من أن المستخدم أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

$db = new Database();
$conn = $db->connect();
$user = new User($conn);

//  تحقق من وجود id صالح في الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("❌ معرف الأدمن غير صالح");
}

$id = intval($_GET['id']);
$message = "";

//  جلب بيانات الأدمن المطلوب
$admin = $user->getUserByIdAndRole($id, 'ادمن');
if (!$admin) {
  die("❌ لا يوجد أدمن بهذا المعرف");
}

//  إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'name' => $_POST['name'],
    'student_id' => $_POST['student_id'],
    'email' => $_POST['email'],
    'gender' => $_POST['gender']
  ];

  if ($user->updateUser($id, $data, 'ادمن')) {
    $message = "✅ تم تحديث بيانات الأدمن بنجاح!";
    $admin = $user->getUserByIdAndRole($id, 'ادمن'); // إعادة جلب البيانات بعد التحديث
  } else {
    $message = "❌ فشل التحديث، حاول مرة أخرى.";
  }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تعديل أدمن</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background-color: #f0f4f8; margin: 0; direction: rtl; }
    header { background-color: #1e3a8a; color: white; padding: 20px; text-align: center; font-size: 24px; }
    .container {
      max-width: 600px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    h2 { color: #0d47a1; text-align: center; margin-bottom: 20px; }
    label { display: block; font-weight: bold; margin-top: 15px; }
    input, select {
      width: 100%;
      padding: 12px;
      font-size: 16px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-top: 6px;
    }
    button {
      margin-top: 25px;
      padding: 14px;
      background-color: #1e88e5;
      color: white;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      width: 100%;
      font-size: 16px;
      cursor: pointer;
    }
    .message {
      margin-top: 20px;
      font-weight: bold;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
    }
    .success { background: #d4edda; color: #256029; }
    .back { text-align: center; margin-top: 25px; }
    .back a {
      color: #1e3a8a;
      text-decoration: none;
      font-weight: bold;
    }
  </style>
</head>
<body>

<header> تعديل بيانات أدمن</header>

<div class="container">
  <h2><?= htmlspecialchars($admin['name']) ?></h2>

  <?php if ($message): ?>
    <div class="message success"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>الاسم الكامل:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>

    <label>رقم القيد:</label>
    <input type="text" name="student_id" value="<?= htmlspecialchars($admin['student_id']) ?>" required>

    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>

    <label>الجنس:</label>
    <select name="gender" required>
      <option value="male" <?= $admin['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
      <option value="female" <?= $admin['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
    </select>

    <button type="submit"> حفظ التعديلات</button>
  </form>

  <div class="back">
    <a href="manage_admins.php"> العودة لإدارة الأدمن</a>
  </div>
</div>

</body>
</html>