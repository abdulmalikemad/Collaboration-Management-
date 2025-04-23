<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once 'Project.php';

$error = "";
$supervisors = [];
$students = [];

try {
  $db = new Database();
  $conn = $db->connect();
  $project = new Project($conn);

  $userId = $_SESSION['user']['id'];
  $userName = $_SESSION['user']['name'];

  $supervisors = $project->getSupervisors();
  $students = $project->getAvailableStudents($userId);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $supervisor_id = $_POST['supervisor_id'];
    $members = isset($_POST['members']) ? $_POST['members'] : [];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if (count($members) === 2) {
      $project_id = $project->create($title, $description, $userId, $supervisor_id, $start_date, $end_date);
      if ($project_id) {
        $project->assignMembers($project_id, $members);
        header("Location: ../UserManagementModule/student_dashboard.php");
        exit();
      } else {
        $error = "⚠️ تعذر إنشاء المشروع. حاول مرة أخرى.";
      }
    } else {
      $error = "⚠️ يجب اختيار عضوين للمشروع.";
    }
  }
} catch (Exception $e) {
  $error = "⚠️ حدث خطأ أثناء المعالجة: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تسجيل مشروع جديد</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f1f5f9;
      direction: rtl;
      margin: 0;
    }
    header {
      background-color: #1e3a8a;
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 24px;
      font-weight: bold;
    }
    .container {
      max-width: 800px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    h2 {
      color: #0d47a1;
      text-align: center;
    }
    label {
      font-weight: bold;
      margin-top: 15px;
      display: block;
    }
    input, textarea, select {
      width: 100%;
      padding: 12px;
      margin-top: 8px;
      margin-bottom: 20px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    .btn {
      background-color: #1e88e5;
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn:hover {
      background-color: #1565c0;
    }
    .error {
      color: red;
      text-align: center;
      margin-bottom: 15px;
    }
    .checkbox-group {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    .checkbox-group label {
      display: flex;
      align-items: center;
      padding: 12px;
      background-color: #f1f5f9;
      border: 1px solid #ccc;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.2s ease;
      font-weight: normal;
    }
    .checkbox-group input[type="checkbox"] {
      margin-left: 10px;
      transform: scale(1.2);
    }
    .checkbox-group label:hover {
      background-color: #e3f2fd;
      border-color: #1e88e5;
    }
  </style>
</head>
<body>
  <header>📘 تسجيل مشروع جديد</header>
  <div class="container">
    <h2>مرحبًا <?= htmlspecialchars($userName ?? "") ?> 👋</h2>

    <?php if (!empty($error)): ?>
      <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
      <label>📌 عنوان المشروع:</label>
      <input type="text" name="title" required>

      <label>📝 وصف المشروع:</label>
      <textarea name="description" rows="4" required></textarea>

      <label>👨‍🏫 اختر المشرف:</label>
      <select name="supervisor_id" required>
        <option value="">-- اختر مشرفًا --</option>
        <?php if ($supervisors instanceof mysqli_result): ?>
          <?php while ($sup = $supervisors->fetch_assoc()): ?>
            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
          <?php endwhile; ?>
        <?php endif; ?>
      </select>

      <label>📅 تاريخ بداية المشروع:</label>
      <input type="date" name="start_date" required>

      <label>📅 تاريخ نهاية المشروع:</label>
      <input type="date" name="end_date" required>

      <label>👥 اختر عضوين من الفريق:</label>
      <input type="text" id="studentSearch" placeholder="🔍 ابحث عن اسم الطالب..." style="width:100%; padding:12px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #ccc; font-size:16px;">

      <div class="checkbox-group">
        <?php if ($students instanceof mysqli_result): ?>
          <?php while ($stu = $students->fetch_assoc()): ?>
            <label>
              <input type="checkbox" name="members[]" value="<?= $stu['id'] ?>">
              <?= htmlspecialchars($stu['name']) ?>
            </label>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="color: #888;">لا يوجد طلاب متاحين حالياً</p>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn">📥 تسجيل المشروع</button>
    </form>
  </div>

  <script>
    document.getElementById("studentSearch").addEventListener("input", function () {
      const query = this.value.toLowerCase();
      const labels = document.querySelectorAll(".checkbox-group label");
      labels.forEach(label => {
        const text = label.textContent.toLowerCase();
        label.style.display = text.includes(query) ? "flex" : "none";
      });
    });
  </script>
</body>
</html>
