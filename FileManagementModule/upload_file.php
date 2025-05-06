<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: ../UserManagementModule/login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once '../TaskManagementModule/Task.php';
require_once '../TaskManagementModule/CommentManager.php';
require_once 'FileManager.php';

$db = new Database();
$conn = $db->connect();

$taskObj = new Task($conn);
$commentObj = new CommentManager($conn);
$fileManager = new FileManager($conn);

$studentId = $_SESSION['user']['id'];
$taskId = isset($_GET['task']) ? (int)$_GET['task'] : 0;

if (!$taskId) die(" رقم المهمة غير صالح.");

$task = $taskObj->getTaskDetailsForStudent($taskId, $studentId);
if (!$task) die(" لا يمكنك الوصول إلى هذه المهمة.");

$isAssigned = false;
$check = $conn->prepare("SELECT id FROM task_assignments WHERE task_id = ? AND student_id = ?");
$check->bind_param("ii", $taskId, $studentId);
$check->execute();
$isAssigned = $check->get_result()->num_rows > 0;

$today = new DateTime();
$dueDate = new DateTime($task['due_date']);
$canUpload = $today <= $dueDate;
$canDelete = $today <= $dueDate;
$message = "";

//  حذف ملف
if (isset($_GET['delete']) && $isAssigned) {
  if (!$canDelete) die(" لا يمكنك حذف الملف بعد انتهاء الموعد النهائي.");
  $fileId = (int)$_GET['delete'];
  $deleted = $fileManager->deleteTaskFile($fileId, $studentId);
  if ($deleted) {
    header("Location: upload_file.php?task=$taskId");
    exit();
  }
}

//  استبدال ملف
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
      //  استبدال الملف السابق بالجديد
      $fileManager->replaceTaskFile($taskId, $studentId, $path);
      $message = " تم استبدال الملف بنجاح!";
      header("Refresh:1");
    }
  }
}

//  إضافة تعليق
if (isset($_POST['comment']) && trim($_POST['comment']) !== '') {
  $comment = trim($_POST['comment']);
  $commentObj->addComment($taskId, $studentId, 'طالب', $comment);
  $message = " تم إضافة التعليق!";
}

$files = $fileManager->getFilesForStudent($taskId, $studentId);
$comments = $commentObj->getCommentsByTask($taskId);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title> استبدال ملف المهمة</title>
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f1f5f9; direction: rtl; padding: 30px; }
    .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    h2 { color: #0d47a1; }
    .message { color: green; font-weight: bold; margin-bottom: 10px; }
    input[type="file"] { margin-top: 10px; }
    button { background: #1e88e5; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 10px; }
    .file-list { margin-top: 25px; }
    .file-item { background: #e3f2fd; padding: 10px; margin-bottom: 10px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
    .file-item a { text-decoration: none; color: #0d47a1; font-weight: bold; }
    .file-item form { display: inline; }
    .delete-btn { background: #d32f2f; color: white; border: none; padding: 5px 12px; border-radius: 6px; font-size: 14px; cursor: pointer; }
    .comment { background: #f0f0f0; padding: 10px; border-radius: 8px; margin-top: 10px; }
    .comment span { font-weight: bold; color: #0d47a1; }
  </style>
</head>
<body>
  <div class="container">
    <h2> استبدال ملف المهمة: <?= htmlspecialchars($task['title']) ?></h2>
    <p> من <?= $task['start_date'] ?> إلى <?= $task['due_date'] ?></p>

    <?php if (!empty($message)): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($canUpload && $isAssigned): ?>
      <form method="POST" enctype="multipart/form-data">
        <label>📎 اختر الملف الجديد:</label>
        <input type="file" name="task_file" required>
        <button type="submit">🔄 استبدال الملف</button>
      </form>
    <?php elseif (!$isAssigned): ?>
      <p style="color: orange;"> يمكنك مشاهدة هذه المهمة فقط لأنك لست مكلفًا بها.</p>
    <?php else: ?>
      <p style="color: red;"> انتهى وقت رفع الملفات.</p>
    <?php endif; ?>

    <div class="file-list">
      <h3> الملف الحالي:</h3>
      <?php if (empty($files)): ?>
        <p> لا يوجد ملف حالي.</p>
      <?php else: ?>
        <?php foreach ($files as $f): ?>
          <div class="file-item">
            <div>
              <a href="<?= $f['file_path'] ?>" download><?= basename($f['file_path']) ?></a>
              <small>(<?= $f['upload_date'] ?>)</small>
            </div>
            <?php if ($isAssigned && $canDelete): ?>
              <form method="GET">
                <input type="hidden" name="task" value="<?= $taskId ?>">
                <button type="submit" name="delete" value="<?= $f['id'] ?>" class="delete-btn">🗑️ حذف</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="comments">
      <h3> التعليقات:</h3>
      <?php if (empty($comments)): ?>
        <p>لا توجد تعليقات.</p>
      <?php else: ?>
        <?php foreach ($comments as $c): ?>
          <div class="comment">
            <span><?= htmlspecialchars($c['commenter_name']) ?> (<?= $c['role'] ?>):</span>
            <?= htmlspecialchars($c['comment']) ?>
            <br><small><?= $c['created_at'] ?></small>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <form method="POST">
        <textarea name="comment" placeholder=" اكتب تعليقًا..." rows="3" required></textarea>
        <button type="submit"> إضافة تعليق</button>
      </form>
    </div>
  </div>
</body>
</html>
