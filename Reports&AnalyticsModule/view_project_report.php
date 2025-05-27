<?php
session_start();
require_once '../UserManagementModule/Database.php';
require_once 'ReportGenerator.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] == 'طالب') {
  header("Location: ../login.php");
  exit();
}

if (!isset($_GET['project_id'])) {
  die("يجب تحديد رقم المشروع.");
}

$projectId = intval($_GET['project_id']);

$db = new Database();
$conn = $db->connect();
$report = new ReportGenerator($conn);

$data = null;
try {
  $data = $report->generateProjectProgressReport($projectId);
} catch (Exception $e) {
  die("حدث خطأ أثناء توليد التقرير: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>📊 تقرير تقدم المشروع</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f6f9;
      margin: 0;
      padding: 20px;
      color: #333;
    }

    .report-container {
      max-width: 600px;
      margin: 40px auto;
      background-color: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }

    .report-header {
      text-align: center;
      margin-bottom: 25px;
    }

    .report-header h2 {
      font-size: 24px;
      margin: 0;
      color: #2c3e50;
    }

    .report-list {
      list-style: none;
      padding: 0;
    }

    .report-list li {
      background-color: #f1f1f1;
      margin-bottom: 12px;
      padding: 12px 20px;
      border-radius: 8px;
      font-size: 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .report-list li span.label {
      font-weight: bold;
      color: #34495e;
    }

    .report-list li span.value {
      color: #16a085;
    }
  </style>
</head>
<body>

<?php if ($data): ?>
  <div class="report-container">
    <div class="report-header">
      <h2>📊 تقرير تقدم المشروع (رقم: <?= $projectId ?>)</h2>
    </div>

    <ul class="report-list">
      <li><span class="label">عدد المهام الكلي:</span> <span class="value"><?= $data['total_tasks'] ?></span></li>
      <li><span class="label">عدد المهام المكتملة:</span> <span class="value"><?= $data['completed_tasks'] ?></span></li>
      <li><span class="label">نسبة التقدم:</span> <span class="value"><?= $data['progress_rate'] ?></span></li>
      <li><span class="label">عدد الملفات المرفوعة:</span> <span class="value"><?= $data['files_uploaded'] ?></span></li>
    </ul>
  </div>
<?php else: ?>
  <div class="report-container">
    <p style="color: red;">⚠️ لا يمكن عرض التقرير حالياً. تحقق من توفر البيانات أو من وجود المشروع المطلوب.</p>
  </div>
<?php endif; ?>

</body>
</html>

