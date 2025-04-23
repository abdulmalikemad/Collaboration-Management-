<<<<<<< HEAD
Abdulmalik alghrayni, [2025/04/22 22:43]
<?php
session_start(); // بدء الجلسة (Session) لتتبع المستخدم المسجل
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
  // إذا لم يكن المستخدم مسجل الدخول كطالب، يتم تحويله إلى صفحة تسجيل الدخول
=======
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'طالب') {
>>>>>>> c5b994a (project unit)
  header("Location: login.php");
  exit();
}

<<<<<<< HEAD
require_once 'Database.php'; // استدعاء ملف الاتصال بقاعدة البيانات

try {
  $db = new Database(); // إنشاء كائن من الكلاس Database
  $conn = $db->connect(); // الاتصال بقاعدة البيانات

  $studentId = $_SESSION['user']['id']; // الحصول على ID الطالب من الجلسة

  // استعلام لجلب آخر مشروع مسجل للطالب سواء كان قائداً أو عضواً
=======
require_once '../UserManagementModule/Database.php';

try {
  $db = new Database();
  $conn = $db->connect();
  $studentId = $_SESSION['user']['id'];

>>>>>>> c5b994a (project unit)
  $stmt = $conn->prepare("
    SELECT p.*, u.name AS supervisor_name
    FROM projects p
    JOIN users u ON p.supervisor_id = u.id
    WHERE p.id IN (
      SELECT project_id FROM project_members WHERE student_id = ?
      UNION
      SELECT id FROM projects WHERE leader_id = ?
    )
    ORDER BY p.id DESC
    LIMIT 1
  ");
<<<<<<< HEAD
  if (!$stmt) {
    throw new Exception("فشل في تجهيز الاستعلام.");
  }

  // ربط القيم بالاستعلام
  $stmt->bind_param("ii", $studentId, $studentId);
  $stmt->execute(); // تنفيذ الاستعلام
  $result = $stmt->get_result(); // الحصول على النتيجة
  $project = $result->fetch_assoc(); // جلب أول صف (آخر مشروع)

} catch (Exception $e) {
  // في حالة حدوث خطأ، عرض رسالة الخطأ
=======
  if (!$stmt) throw new Exception("فشل في تجهيز الاستعلام.");
  $stmt->bind_param("ii", $studentId, $studentId);
  $stmt->execute();
  $result = $stmt->get_result();
  $project = $result->fetch_assoc();

} catch (Exception $e) {
>>>>>>> c5b994a (project unit)
  echo "<p style='color:red; text-align:center;'>❌ حدث خطأ أثناء تحميل البيانات: " . $e->getMessage() . "</p>";
  exit();
}
?>

<<<<<<< HEAD

=======
>>>>>>> c5b994a (project unit)
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>لوحة الطالب | CMT</title>
<<<<<<< HEAD
  <!-- استدعاء خط من Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    /* تنسيقات عامة للصفحة */
=======
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
>>>>>>> c5b994a (project unit)
    body {
      font-family: 'Cairo', sans-serif;
      background: #f0f4f8;
      margin: 0;
      direction: rtl;
    }
<<<<<<< HEAD

    /* رأس الصفحة */
=======
>>>>>>> c5b994a (project unit)
    header {
      background-color: #1e3a8a;
      color: white;
      padding: 20px;
      font-size: 24px;
      font-weight: bold;
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
    }
<<<<<<< HEAD

    /* زر تسجيل الخروج */
=======
>>>>>>> c5b994a (project unit)
    .logout-btn {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      background-color: #d32f2f;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      font-size: 14px;
      transition: background-color 0.3s;
    }
<<<<<<< HEAD
    .logout-btn:hover {
      background-color: #b71c1c;
    }

    /* المكون الرئيسي */
=======
    .logout-btn:hover { background-color: #b71c1c; }
>>>>>>> c5b994a (project unit)
    main {
      max-width: 800px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
<<<<<<< HEAD

    /* عنوان رئيسي */
=======
>>>>>>> c5b994a (project unit)
    h2 {
      color: #0d47a1;
      text-align: center;
    }
<<<<<<< HEAD

    /* صندوق عرض تفاصيل المشروع */
=======
>>>>>>> c5b994a (project unit)
    .project-box {
      border: 1px solid #ccc;
      padding: 20px;
      border-radius: 10px;
      background-color: #f9f9f9;
    }
<<<<<<< HEAD

    /* حالة المشروع */
=======
>>>>>>> c5b994a (project unit)
    .status {
      font-weight: bold;
      margin-top: 15px;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
    }
    .pending { background-color: #fff3cd; color: #856404; }
    .approved { background-color: #d4edda; color: #155724; }
    .rejected { background-color: #f8d7da; color: #721c24; }
<<<<<<< HEAD

    /* سبب الرفض */
=======
>>>>>>> c5b994a (project unit)
    .reason {
      margin-top: 15px;
      padding: 12px;
      background-color: #fdecea;
      color: #b71c1c;
      border-radius: 8px;
      border: 1px solid #f5c6cb;
    }
<<<<<<< HEAD

    /* أزرار العمليات */
=======
>>>>>>> c5b994a (project unit)
    .action-button {
      display: inline-block;
      background-color: #1e88e5;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.3s;
    }
<<<<<<< HEAD
    .action-button:hover {
      background-color: #1565c0;
    }

=======
    .action-button:hover { background-color: #1565c0; }
>>>>>>> c5b994a (project unit)
    .actions {
      margin-top: 30px;
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
    }
  </style>
</head>
<body>

<<<<<<< HEAD
Abdulmalik alghrayni, [2025/04/22 22:43]
<!-- رأس الصفحة -->
  <header>
    لوحة الطالب - نظام إدارة المشاريع CMT
    <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
  </header>

  <!-- المحتوى الرئيسي -->
  <main>
    <h2> مرحبًا <?= htmlspecialchars($_SESSION['user']['name']) ?></h2>

    <?php if ($project): ?>
      <?php
        // تحديد نوع حالة المشروع لعرضها بالألوان
        $status = trim($project['status']);
        $statusClass = ($status === 'بانتظار الموافقة') ? 'pending' :
                       (($status === 'تمت الموافقة') ? 'approved' : 'rejected');
        $isLeader = ($_SESSION['user']['id'] == $project['leader_id']); // تحقق إن كان المستخدم قائد المشروع
      ?>
      <div class="project-box">
        <!-- عرض تفاصيل المشروع -->
        <h3>📌 عنوان المشروع: <?= htmlspecialchars($project['title']) ?></h3>
        <p>📝 الوصف: <?= nl2br(htmlspecialchars($project['description'])) ?></p>
        <p>👨‍🏫 المشرف: <?= htmlspecialchars($project['supervisor_name']) ?></p>
        <div class="status <?= $statusClass ?>">الحالة: <?= htmlspecialchars($status) ?></div>

        <!-- في حالة المشروع مرفوض، عرض السبب -->
        <?php if ($status === 'مرفوض' && !empty($project['rejection_reason'])): ?>
          <div class="reason">
            📌 <strong>سبب الرفض:</strong><br>
            <?= nl2br(htmlspecialchars($project['rejection_reason'])) ?>
          </div>
        <?php endif; ?>

        <!-- في حالة المشروع مقبول، عرض روابط المهام -->
        <?php if ($status === 'تمت الموافقة'): ?>
          <div class="actions">
            <a href="tasks.php" class="action-button">📋 عرض المهام</a>
            <?php if ($isLeader): ?>
              <a href="add_task.php" class="action-button">➕ إضافة مهمة</a>
            <?php endif; ?>
            <a href="questions.php" class="action-button">💬 استفسار للمشرف</a>
          </div>

        <!-- في حالة المشروع مرفوض، عرض زر تسجيل مشروع جديد -->
        <?php elseif ($status === 'مرفوض'): ?>
          <div class="actions">
            <a href="create_project.php" class="action-button">➕ تسجيل مشروع جديد</a>
          </div>
        <?php endif; ?>
      </div>

    <!-- في حالة عدم وجود أي مشروع -->
    <?php else: ?>
      <div style="text-align:center; margin-top: 40px;">
        <a href="create_project.php" class="action-button">➕ تسجيل مشروع جديد</a>
      </div>
    <?php endif; ?>
  </main>

</body>
</html>
=======
<header>
  لوحة الطالب - نظام إدارة المشاريع CMT
  <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
</header>

<main>
  <h2> مرحبًا <?= htmlspecialchars($_SESSION['user']['name']) ?></h2>

  <?php if ($project): ?>
    <?php
      $status = trim($project['status']);
      $statusClass = ($status === 'بانتظار الموافقة') ? 'pending' :
                     (($status === 'تمت الموافقة') ? 'approved' : 'rejected');
      $isLeader = ($_SESSION['user']['id'] == $project['leader_id']);
    ?>
    <div class="project-box">
      <h3>📌 عنوان المشروع: <?= htmlspecialchars($project['title']) ?></h3>
      <p>📝 الوصف: <?= nl2br(htmlspecialchars($project['description'])) ?></p>
      <p>👨‍🏫 المشرف: <?= htmlspecialchars($project['supervisor_name']) ?></p>
      <div class="status <?= $statusClass ?>">الحالة: <?= htmlspecialchars($status) ?></div>

      <?php if ($status === 'مرفوض' && !empty($project['rejection_reason'])): ?>
        <div class="reason">
          📌 <strong>سبب الرفض:</strong><br>
          <?= nl2br(htmlspecialchars($project['rejection_reason'])) ?>
        </div>
      <?php endif; ?>

      <?php if ($status === 'تمت الموافقة'): ?>
        <div class="actions">
          <a href="tasks.php" class="action-button">📋 عرض المهام</a>
          <?php if ($isLeader): ?>
            <a href="add_task.php" class="action-button">➕ إضافة مهمة</a>
          <?php endif; ?>
          <a href="questions.php" class="action-button">💬 استفسار للمشرف</a>
        </div>

      <?php elseif ($status === 'مرفوض'): ?>
        <div class="actions">
          <a href="../ProjectManagementModule/create_project.php" class="action-button">➕ تسجيل مشروع جديد</a>
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <div style="text-align:center; margin-top: 40px;">
      <a href="../ProjectManagementModule/create_project.php" class="action-button">➕ تسجيل مشروع جديد</a>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
>>>>>>> c5b994a (project unit)
