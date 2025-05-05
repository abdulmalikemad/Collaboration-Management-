<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: ../UserManagementModule/login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once '../TaskManagementModule/Task.php';
require_once 'FileManager.php';

$db = new Database();
$conn = $db->connect();

$taskObj = new Task($conn);
$fileManager = new FileManager($conn);

$studentId = $_SESSION['user']['id'];
$taskId = isset($_GET['task']) ? (int)$_GET['task'] : 0;

if (!$taskId) die(" رقم المهمة غير صالح.");
$task = $taskObj->getTaskDetailsForStudent($taskId, $studentId);
if (!$task) die(" لا يمكنك الوصول إلى هذه المهمة.");

$check = $conn->prepare("SELECT id FROM task_assignments WHERE task_id = ? AND student_id = ?");
$check->bind_param("ii", $taskId, $studentId);
$check->execute();
$isAssigned = $check->get_result()->num_rows > 0;

$today = new DateTime();
$dueDate = new DateTime($task['due_date']);
$canUpload = $today <= $dueDate;

$message = "";

//  رفع ملف جديد (بدون استبدال)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canUpload && $isAssigned && isset($_FILES['task_file'])) {
  $uploadDir = "../TaskManagementModule/uploads/";
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

  $tmpName = $_FILES['task_file']['tmp_name'];
  if ($_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
    $originalName = basename($_FILES['task_file']['name']);
    $cleanedName = preg_replace("/[^A-Za-z0-9_\-.]/", "_", $originalName);
    $fileName = time() . "_" . $cleanedName;
    $path = $uploadDir . $fileName;

    if (move_uploaded_file($tmpName, $path)) {
      $fileManager->uploadTaskFile($taskId, $studentId, $path);
      $message = " تم رفع الملف بنجاح.";
      header("Refresh:1");
    }
  }
}


?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title> رفع ملف المهمة</title>
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f1f5f9; direction: rtl; padding: 30px; }
    .container { max-width: 700px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    h2 { color: #0d47a1; margin-bottom: 10px; }
    .message { color: green; font-weight: bold; margin-bottom: 10px; }
    input[type="file"] { margin-top: 10px; }
    button {
      background: #1e88e5; color: white; border: none;
      padding: 10px 20px; border-radius: 8px; cursor: pointer;
      font-weight: bold; margin-top: 10px;
    }
    .file-item {
      background: #e3f2fd; padding: 10px; margin-top: 10px;
      border-radius: 8px; font-size: 14px; display: flex; justify-content: space-between;
    }
    .file-item a { color: #0d47a1; text-decoration: none; font-weight: bold; }
  </style>
</head>
<body>
  <div class="container">
    <h2> رفع ملف المهمة: <?= htmlspecialchars($task['title']) ?></h2>
    <p>🗓 من <?= $task['start_date'] ?> إلى <?= $task['due_date'] ?></p>

    <?php if (!empty($message)): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($canUpload && $isAssigned): ?>
      <form method="POST" enctype="multipart/form-data">
        <label>📎 اختر الملف:</label>
        <input type="file" name="task_file" required>
        <button type="submit"> رفع الملف</button>
      </form>
    <?php elseif (!$isAssigned): ?>
      <p style="color: orange;"> لا يمكنك رفع الملف لأنك غير مكلف بهذه المهمة.</p>
    <?php else: ?>
      <p style="color: red;"> انتهى الموعد النهائي، لا يمكنك رفع الملف.</p>
    <?php endif; ?>

   
  </div>
</body>
</html>