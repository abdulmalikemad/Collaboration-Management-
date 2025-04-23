<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'دكتور') {
  header("Location: ../UserManagementModule/login.php");
  exit();
}

require_once '../UserManagementModule/Database.php';
require_once 'Project.php';

try {
  $db = new Database();
  $conn = $db->connect();
  $project = new Project($conn);

  $supervisor_id = $_SESSION['user']['id'];

  // معالجة الموافقة أو الرفض
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'], $_POST['action'])) {
    $project_id = intval($_POST['project_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
      $status = 'تمت الموافقة';
      $stmt = $conn->prepare("UPDATE projects SET status = ?, rejection_reason = NULL WHERE id = ? AND supervisor_id = ?");
      $stmt->bind_param("sii", $status, $project_id, $supervisor_id);
    } elseif ($action === 'reject' && !empty($_POST['reason'])) {
      $status = 'مرفوض';
      $reason = $_POST['reason'];
      $stmt = $conn->prepare("UPDATE projects SET status = ?, rejection_reason = ? WHERE id = ? AND supervisor_id = ?");
      $stmt->bind_param("ssii", $status, $reason, $project_id, $supervisor_id);
    }

    if (isset($stmt) && $stmt->execute() && $stmt->affected_rows > 0) {
      echo "<script>alert('✅ تم تحديث حالة المشروع'); window.location.href='supervisor_requests.php';</script>";
      exit();
    } else {
      echo "<script>alert('❌ لم يتم تحديث المشروع - تحقق من البيانات');</script>";
    }
  }

  $pendingProjects = $project->getPendingProjectsForSupervisor($supervisor_id);

} catch (Exception $e) {
  die("<p style='text-align:center; color:red;'>❌ حدث خطأ: " . $e->getMessage() . "</p>");
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طلبات المشاريع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background-color: #f9f9f9;
      padding: 30px;
      direction: rtl;
    }
    .container {
      max-width: 950px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #0d47a1;
      margin-bottom: 30px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: center;
      vertical-align: middle;
    }
    th {
      background-color: #e3f2fd;
    }
    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }
    .approve { background-color: #4caf50; }
    .reject { background-color: #f44336; }
    .btn:hover { opacity: 0.9; }
    .reason-box {
      margin-top: 10px;
    }
    textarea {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-family: 'Cairo', sans-serif;
      resize: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>طلبات المشاريع بانتظار الموافقة</h2>
    <?php if ($pendingProjects && $pendingProjects->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>عنوان المشروع</th>
          <th>الوصف</th>
          <th>القائد</th>
          <th>الإجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $pendingProjects->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['leader_name']) ?></td>
            <td>
              <form method="POST" style="margin-bottom: 10px;">
                <input type="hidden" name="project_id" value="<?= $row['id'] ?>">
                <button type="submit" name="action" value="approve" class="btn approve">موافقة</button>
              </form>

              <form method="POST">
                <input type="hidden" name="project_id" value="<?= $row['id'] ?>">
                <div class="reason-box">
                  <textarea name="reason" rows="3" placeholder="سبب الرفض (إجباري عند الرفض)" required></textarea>
                </div>
                <button type="submit" name="action" value="reject" class="btn reject" style="margin-top: 8px;">رفض</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p style="text-align:center; color:#777; font-size:18px;">🚫 لا توجد مشاريع حالياً بانتظار الموافقة.</p>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px;">
      <a href="../UserManagementModule/supervisor_dashboard.php" style="
        display: inline-block;
        background-color: #1976d2;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
      ">🔙 العودة إلى لوحة التحكم</a>
    </div>
  </div>
</body>
</html>
