<?php
session_start();
require_once 'Database.php';
require_once 'User.php';

//  التحقق من أن المستخدم هو أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

//  التحقق من وجود معرف صالح
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die(" رابط غير صالح");
}

$id = intval($_GET['id']);

//  الاتصال بقاعدة البيانات
$db = new Database();
$conn = $db->connect();

//إنشاء كائن المستخدم
$user = new User($conn);

//  تنفيذ عملية الحذف باستخدام الكلاس
if ($user->deleteUser($id, 'دكتور')) {
  //  إعادة التوجيه بعد الحذف مع رسالة
  header("Location: manage_supervisors.php?deleted=1");
  exit();
} else {
  echo "<p style='color:red; text-align:center;'> فشل حذف المشرف.</p>";
}
?>