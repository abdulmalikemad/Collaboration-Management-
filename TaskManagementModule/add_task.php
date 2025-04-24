<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once 'Task.php';


try {
  $db = new Database();
  $conn = $db->connect();
  $taskObj = new Task($conn);

  $userId = $_SESSION['user']['id'];
  $userName = $_SESSION['user']['name'];
  $isLeader = false;
  $project_id = null;

  // جلب معلومات المشروع
  $projectStmt = $conn->prepare("
    SELECT p.id, p.title, p.leader_id 
    FROM project_members pm
    JOIN projects p ON pm.project_id = p.id
    WHERE pm.student_id = ?
    LIMIT 1
  ");
  $projectStmt->bind_param("i", $userId);
  $projectStmt->execute();
  $project = $projectStmt->get_result()->fetch_assoc();

  if ($project) {
    $project_id = $project['id'];
    $isLeader = ($userId == $project['leader_id']);
  }

  // معالجة الحفظ
  if ($isLeader && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $start = $_POST['start_date'];
    $due = $_POST['due_date'];
    $assigned = isset($_POST['assigned']) ? $_POST['assigned'] : [];

    if (count($assigned) === 2) {
      if ($taskObj->createTask($title, $start, $due, $project_id, $assigned)) {
        header("Location: tasks.php");
        exit();
      } else {
        echo "<p style='color:red; text-align:center;'>⚠️ فشل إنشاء المهمة. تأكد من البيانات.</p>";
      }
    } else {
      echo "<p style='color:red; text-align:center;'>⚠️ يجب اختيار طالبين تمامًا.</p>";
    }
  }
} catch (Exception $e) {
  echo "<p style='color:red; text-align:center;'>⚠️ خطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>➕ إضافة مهمة</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f4f6f9; margin: 0; direction: rtl; }
    .container { max-width: 700px; margin: 50px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #1e3a8a; margin-bottom: 25px; }
    label { font-weight: bold; margin-top: 12px; display: block; }
    input, select { width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #ccc; border-radius: 8px; }
    .checkbox-list label { margin: 5px 0; display: block; }
    button {
      background-color: #1e88e5;
      color: white;
      padding: 10px 20px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>➕ إضافة مهمة جديدة</h2>

    <?php if ($isLeader): ?>
      <form method="POST">
        <label>عنوان المهمة:</label>
        <input type="text" name="title" required>

        <label>تاريخ البدء:</label>
        <input type="date" name="start_date" required>

        <label>تاريخ التسليم:</label>
        <input type="date" name="due_date" required>

        <label>اختر طالبين فقط:</label>
        <div class="checkbox-list">
          <?php
            $stmt = $conn->prepare("
              SELECT u.id, u.name 
              FROM project_members pm 
              JOIN users u ON pm.student_id = u.id 
              WHERE pm.project_id = ? AND u.id != ?
            ");
            $stmt->bind_param("ii", $project_id, $userId);
            $stmt->execute();
            $members = $stmt->get_result();
            while ($m = $members->fetch_assoc()):
          ?>
            <label><input type="checkbox" name="assigned[]" value="<?= $m['id'] ?>"> <?= htmlspecialchars($m['name']) ?></label>
          <?php endwhile; ?>
          <label><input type="checkbox" name="assigned[]" value="<?= $userId ?>"> <?= htmlspecialchars($userName) ?> (أنا)</label>
        </div>

        <button type="submit">✔️ إضافة المهمة</button>
      </form>

      <script>
        document.querySelector('form').addEventListener('submit', function(e) {
          const checked = document.querySelectorAll('input[name="assigned[]"]:checked');
          if (checked.length !== 2) {
            e.preventDefault();
            alert("⚠️ يجب اختيار طالبين تمامًا لهذه المهمة.");
          }
        });
      </script>
    <?php else: ?>
      <p style="color:red; text-align:center;">❌ لا يمكنك إضافة مهمة لأنك لست قائد المشروع.</p>
    <?php endif; ?>
  </div>
</body>
</html>
