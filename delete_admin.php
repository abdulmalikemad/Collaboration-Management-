<?php
session_start();
require_once 'Database.php';
require_once 'User.php';

// ✅ التحقق من تسجيل الدخول وصلاحية الأدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ادمن') {
  header("Location: login.php");
  exit();
}

//  التحقق من وجود المعرف في الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("❌ معرف غير صالح");
}

$id = intval($_GET['id']);

$db = new Database();
$conn = $db->connect();
$user = new User($conn);

//  تنفيذ الحذف
if ($user->deleteUser($id, 'ادمن')) {
  header("Location: manage_admins.php?msg=deleted");
  exit();
} else {
  die("❌ فشل حذف الأدمن");
}