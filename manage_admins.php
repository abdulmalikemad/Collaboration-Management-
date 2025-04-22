<?php
session_start(); // بدء الجلسة للتحقق من حالة تسجيل الدخول للمستخدم
require_once 'Database.php'; // استيراد ملف الاتصال بقاعدة البيانات
require_once 'User.php'; // استيراد ملف الكود المتعلق بالمستخدم

// التحقق من أن المستخدم هو أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php"); // إذا لم يكن المستخدم أدمن، يتم تحويله إلى صفحة تسجيل الدخول
  exit();
}

$db = new Database(); // إنشاء كائن من قاعدة البيانات
$conn = $db->connect(); // الاتصال بقاعدة البيانات
$user = new User($conn); // إنشاء كائن من فئة User للتعامل مع بيانات المستخدم

// البحث عن الأدمن إذا كان موجود
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; // إذا كان هناك بحث، أخذ قيمة البحث من الرابط
$admins = $user->getAllAdmins($search); // استدعاء دالة للحصول على جميع الأدمن مع إمكانية البحث
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إدارة الأدمن</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600&display=swap" rel="stylesheet"> <!-- استيراد الخط -->
  <style>
    body { font-family: 'Cairo', sans-serif; background: #f0f4f8; margin: 0; direction: rtl; } /* تنسيق عام للصفحة */
    header { background-color: #1e3a8a; color: white; padding: 20px; text-align: center; font-size: 24px; } /* رأس الصفحة */
    main {
      max-width: 1000px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    h2 { text-align: center; color: #0d47a1; margin-bottom: 20px; } /* عنوان رئيسي */
    form { text-align: center; margin-bottom: 20px; } /* تنسيق نموذج البحث */
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
    .edit { background-color: #4caf50; } /* تنسيق زر التعديل */
    .delete { background-color: #f44336; } /* تنسيق زر الحذف */
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

<header>🛠️ إدارة الأدمن</header>
<main>
  <h2>قائمة الأدمن</h2>

  <!-- نموذج البحث -->
  <form method="GET">
    <input type="text" name="search" placeholder="🔍 ابحث بالاسم أو رقم القيد" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">بحث</button>
  </form>

  <?php if (!empty($search)): ?>
    <!-- زر تحديث للرجوع إلى عرض جميع الأدمن -->
    <div class="refresh-btn">
      <a href="manage_admins.php">🔄 عرض كل الأدمن</a>
    </div>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>الاسم</th>
        <th>رقم القيد</th>
        <th>البريد الإلكتروني</th>
        <th>الجنس</th>
        <th>إجراءات</th> <!-- إجراءات مثل التعديل والحذف -->
      </tr>
    </thead>
    <tbody>
      <?php if (!$admins || $admins->num_rows === 0): ?>
        <!-- إذا لم توجد نتائج -->
        <tr><td colspan="5">لا توجد نتائج.</td></tr>
      <?php else: ?>
        <!-- عرض الأدمنين -->
        <?php while ($admin = $admins->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($admin['name']) ?></td>
            <td><?= htmlspecialchars($admin['student_id']) ?></td>
            <td><?= htmlspecialchars($admin['email']) ?></td>
            <td><?= htmlspecialchars($admin['gender']) ?></td>
            <td>
              <a class="btn edit" href="edit_admin.php?id=<?= $admin['id'] ?>">تعديل</a>
              <a class="btn delete" href="delete_admin.php?id=<?= $admin['id'] ?>" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</main>

</body>
</html>
