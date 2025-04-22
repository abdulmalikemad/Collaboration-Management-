<?php
session_start();
require_once 'Database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

$db = new Database();
$conn = $db->connect();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$supervisors = [];

if ($search !== '') {
  $like = '%' . $search . '%';
  $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'دكتور' AND (name LIKE ? OR student_id LIKE ?)");
  $stmt->bind_param("ss", $like, $like);
} else {
  $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'دكتور'");
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $supervisors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إدارة المشرفين</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f0f4f8; margin: 0; direction: rtl; }
    header { background-color: #1e3a8a; color: white; padding: 20px; text-align: center; font-size: 24px; }
    main {
      max-width: 1000px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    h2 { text-align: center; color: #0d47a1; margin-bottom: 20px; }
    form { text-align: center; margin-bottom: 20px; }
    input[type="text"] {
      padding: 10px;
      width: 60%;
      max-width: 400px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    button {
      padding: 10px 20px;
      background-color: #1e88e5;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      margin-right: 10px;
      cursor: pointer;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: center;
    }
    th {
      background-color: #e3f2fd;
      color: #0d47a1;
    }
    a.btn {
      padding: 6px 12px;
      border-radius: 6px;
      color: white;
      font-weight: bold;
      text-decoration: none;
    }
    .edit { background-color: #4caf50; }
    .delete { background-color: #f44336; }
    .refresh-btn {
      text-align: center;
      margin-bottom: 20px;
    }
    .refresh-btn a {
      padding: 10px 20px;
      background-color: #e0e0e0;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      color: #333;
    }
  </style>
</head>
<body>

<header>📋 إدارة المشرفين</header>
<main>
  <h2>قائمة المشرفين</h2>

  <form method="GET">
    <input type="text" name="search" placeholder="🔍 ابحث بالاسم أو رقم القيد" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">بحث</button>
  </form>

  <?php if (!empty($search)): ?>
    <div class="refresh-btn">
      <a href="manage_supervisors.php">🔄 عرض كل المشرفين</a>
    </div>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>الاسم</th>
        <th>رقم القيد</th>
        <th>البريد الإلكتروني</th>
        <th>الجنس</th>
        <th>إجراءات</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($supervisors)): ?>
        <tr><td colspan="5">لا توجد نتائج.</td></tr>
      <?php else: ?>
        <?php foreach ($supervisors as $sup): ?>
          <tr>
            <td><?= htmlspecialchars($sup['name']) ?></td>
            <td><?= htmlspecialchars($sup['student_id']) ?></td>
            <td><?= htmlspecialchars($sup['email']) ?></td>
            <td><?= htmlspecialchars($sup['gender']) ?></td>
            <td>
              <a class="btn edit" href="edit_supervisor.php?id=<?= $sup['id'] ?>"> تعديل</a>
              <a class="btn delete" href="delete_supervisor.php?id=<?= $sup['id'] ?>" onclick="return confirm('هل أنت متأكد من الحذف؟')"> حذف</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</main>

</body>
</html>
