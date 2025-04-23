<?php
session_start();
require_once 'Database.php';
require_once 'User.php';

//  التحقق من أن المستخدم أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

//  التحقق من وجود ID صالح
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("❌ معرف غير صالح");
}

$id = intval($_GET['id']);

$db = new Database();
$conn = $db->connect();
$user = new User($conn);

//  تنفيذ عملية الحذف
if ($user->deleteUser($id, 'طالب')) {
  header("Location: manage_students.php?msg=deleted");
  exit();
} else {
  die(" فشل في حذف الطالب");
}
