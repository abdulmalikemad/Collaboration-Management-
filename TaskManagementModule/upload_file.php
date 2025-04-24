<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  header("Location: login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once 'Task.php';
require_once 'CommentManager.php';

$db = new Database();
$conn = $db->connect();

$taskObj = new Task($conn);
$commentObj = new CommentManager($conn);

$studentId = $_SESSION['user']['id'];
$taskId = isset($_GET['task']) ? (int)$_GET['task'] : 0;

if (!$taskId) die("❌ رقم المهمة غير صالح.");

// ✅ جلب تفاصيل المهمة للطالب (حتى لو مش مكلف)
$task = $taskObj->getTaskDetailsForStudent($taskId, $studentId);
if (!$task) die("❌ لا يمكنك الوصول إلى هذه المهمة.");

// ✅ تحقق إن كان الطالب مكلف بالمهمة
$isAssigned = false;
$check = $conn->prepare("SELECT id FROM task_assignments WHERE task_id = ? AND student_id = ?");
$check->bind_param("ii", $taskId, $studentId);
$check->execute();
$assignedResult = $check->get_result();
$isAssigned = $assignedResult->num_rows > 0;

$today = new DateTime();
$dueDate = new DateTime($task['due_date']);
$canUpload = $today <= $dueDate;
$message = "";

// ✅ حذف ملف
if (isset($_GET['delete']) && $isAssigned) {
  $fileId = (int)$_GET['delete'];
  $deleted = $taskObj->deleteFile($fileId, $studentId);
  if ($deleted) {
    header("Location: upload_file.php?task=$taskId");
    exit();
  }
}

// ✅ رفع ملفات (فقط لو الطالب مكلف)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canUpload && $isAssigned && isset($_FILES['task_files'])) {
  $uploadDir = "uploads/";
  if (!is_dir($uploadDir)) mkdir($uploadDir);

  foreach ($_FILES['task_files']['tmp_name'] as $i => $tmpName) {
    if ($_FILES['task_files']['error'][$i] === UPLOAD_ERR_OK) {
      $originalName = basename($_FILES['task_files']['name'][$i]);
      $cleanedName = preg_replace("/[^A-Za-z0-9_\-.]/", "_", $originalName);
      $fileName = time() . "_" . $cleanedName;
      $path = $uploadDir . $fileName;

      if (move_uploaded_file($tmpName, $path)) {
        $taskObj->uploadFile($taskId, $studentId, $path);
        $message = "✅ تم رفع الملفات بنجاح!";
      }
    }
  }
  header("Refresh:1");
}

// ✅ إضافة تعليق
if (isset($_POST['comment']) && trim($_POST['comment']) !== '') {
  $comment = trim($_POST['comment']);
  $commentObj->addComment($taskId, $studentId, 'طالب', $comment);
  $message = "✅ تم إضافة التعليق!";
}

// ✅ جلب الملفات والتعليقات
$files = $taskObj->getFilesForStudent($taskId, $studentId);
$comments = $commentObj->getCommentsByTask($taskId);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📤 رفع ملفات المهمة</title>
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
    <h2>📤 رفع ملفات المهمة: <?= htmlspecialchars($task['title']) ?></h2>
    <p>🗓️ من <?= $task['start_date'] ?> إلى <?= $task['due_date'] ?></p>

    <?php if (!empty($message)): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($canUpload && $isAssigned): ?>
      <form method="POST" enctype="multipart/form-data">
        <label>📎 اختر الملفات:</label>
        <input type="file" name="task_files[]" multiple required>
        <button type="submit">🔼 رفع</button>
      </form>
    <?php elseif (!$isAssigned): ?>
      <p style="color: orange;">⚠️ يمكنك مشاهدة هذه المهمة فقط لأنك لست مكلفًا بها.</p>
    <?php else: ?>
      <p style="color: red;">⛔ انتهى وقت رفع الملفات.</p>
    <?php endif; ?>

    <div class="file-list">
      <h3>📁 الملفات المرفوعة:</h3>
      <?php if (empty($files)): ?>
        <p>🚫 لم يتم رفع أي ملف.</p>
      <?php else: ?>
        <?php foreach ($files as $f): ?>
          <div class="file-item">
            <div>
              <a href="<?= $f['file_path'] ?>" download><?= basename($f['file_path']) ?></a>
              <small>(<?= $f['upload_date'] ?>)</small>
            </div>
            <?php if ($isAssigned): ?>
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
      <h3>💬 التعليقات:</h3>
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
        <textarea name="comment" placeholder="💬 اكتب تعليقًا..." rows="3" required></textarea>
        <button type="submit">➕ إضافة تعليق</button>
      </form>
    </div>
  </div>
</body>
</html>
