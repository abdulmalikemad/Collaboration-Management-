<?php
// ✅ بدء الجلسة (Session)
session_start();

// ✅ استدعاء الملفات الأساسية: الاتصال بقاعدة البيانات وكلاس المستخدم
require_once 'Database.php';
require_once 'User.php';

// ✅ التحقق من أن المستخدم الحالي هو "أدمن"
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

// ✅ إنشاء اتصال بقاعدة البيانات وإنشاء كائن المستخدم
$db = new Database();
$conn = $db->connect();
$user = new User($conn);

// 🔍 التحقق من وجود عملية بحث
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ✅ جلب جميع الطلبة (مع البحث إذا وُجد)
$students = $user->getAllStudents($search);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إدارة الطلبة</title>
  <!-- ✅ استيراد خط عربي من Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600&display=swap" rel="stylesheet">
  <style>
    /* ✅ تنسيقات CSS العامة للصفحة */
    body {
      font-family: 'Cairo', sans-serif;
      background: #f0f4f8;
      margin: 0;
      direction: rtl;
    }

    header {
      background-color: #1e3a8a;
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 24px;
    }

    main {
      max-width: 1000px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #0d47a1;
      margin-bottom: 20px;
    }

    /* ✅ نموذج البحث */
    form {
      text-align: center;
      margin-bottom: 20px;
    }

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

    /* ✅ جدول عرض الطلبة */
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

    /* ✅ أزرار الإجراءات (تعديل - حذف) */
    a.btn {
      padding: 6px 12px;
      border-radius: 6px;
      color: white;
      font-weight: bold;
      text-decoration: none;
    }

    .edit {
      background-color: #4caf50;
    }

    .delete {
      background-color: #f44336;
    }

    /* ✅ زر التحديث بعد البحث */
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

<!-- ✅ عنوان الصفحة -->
<header>👨‍🎓 إدارة الطلبة</header>

<main>
  <h2>قائمة الطلبة</h2>

  <!-- ✅ نموذج البحث -->
  <form method="GET">
    <input type="text" name="search" placeholder="🔍 ابحث بالاسم أو رقم القيد" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">بحث</button>
  </form>

  <!-- ✅ زر عرض الكل بعد البحث -->
  <?php if (!empty($search)): ?>
    <div class="refresh-btn">
      <a href="manage_students.php">🔄 عرض كل الطلبة</a>
    </div>
  <?php endif; ?>

  <!-- ✅ جدول عرض البيانات -->
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
      <?php if (!$students || $students->num_rows === 0): ?>
        <!-- ✅ في حال عدم وجود نتائج -->
        <tr><td colspan="5">لا توجد نتائج.</td></tr>
      <?php else: ?>
        <!-- ✅ عرض كل طالب -->
        <?php while ($student = $students->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($student['name']) ?></td>
            <td><?= htmlspecialchars($student['student_id']) ?></td>
            <td><?= htmlspecialchars($student['email']) ?></td>
            <td><?= htmlspecialchars($student['gender']) ?></td>
            <td>
              <!-- ✅ زر تعديل -->
              <a class="btn edit" href="edit_student.php?id=<?= $student['id'] ?>">تعديل</a>
              <!-- ✅ زر حذف -->
              <a class="btn delete" href="delete_student.php?id=<?= $student['id'] ?>" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</main>

</body>
</html>
