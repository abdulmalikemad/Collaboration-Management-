<?php
session_start();
require_once 'Database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

$db = new Database();
$conn = $db->connect();

// التحقق من وجود id في الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("❌ طلب غير صالح");
}

$id = intval($_GET['id']);
$message = "";

// جلب بيانات المشرف
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'دكتور'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$supervisor = $result->fetch_assoc();

if (!$supervisor) {
  die("❌ المشرف غير موجود");
}

// تحديث البيانات بعد إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'];
  $student_id = $_POST['student_id'];
  $email = $_POST['email'];
  $gender = $_POST['gender'];

  $update = $conn->prepare("UPDATE users SET name = ?, student_id = ?, email = ?, gender = ? WHERE id = ? AND role = 'دكتور'");
  $update->bind_param("ssssi", $name, $student_id, $email, $gender, $id);
  if ($update->execute()) {
    $message = "✅ تم تحديث بيانات المشرف بنجاح!";
    $supervisor = ['name' => $name, 'student_id' => $student_id, 'email' => $email, 'gender' => $gender];
  } else {
    $message = "❌ فشل التحديث: " . $update->error;
  }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تعديل مشرف</title>
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
    label { display: block; font-weight: bold; margin-top: 15px; color: #333; }
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

<header>✏️ تعديل بيانات مشرف</header>
<div class="container">
  <h2><?= htmlspecialchars($supervisor['name']) ?></h2>

  <?php if ($message): ?>
    <div class="message success"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>الاسم الكامل:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($supervisor['name']) ?>" required>

    <label>رقم القيد:</label>
    <input type="text" name="student_id" value="<?= htmlspecialchars($supervisor['student_id']) ?>" required>

    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($supervisor['email']) ?>" required>

    <label>الجنس:</label>
    <select name="gender" required>
      <option value="male" <?= $supervisor['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
      <option value="female" <?= $supervisor['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
    </select>

    <button type="submit">💾 حفظ التعديلات</button>
  </form>

  <div class="back">
    <p><a href="manage_supervisors.php">🔙 العودة إلى إدارة المشرفين</a></p>
  </div>
</div>

</body>
</html>
