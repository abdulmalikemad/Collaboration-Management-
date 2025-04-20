<?php
$servername = "localhost";
$username = "root";  // اسم المستخدم لقاعدة البيانات
$password = "";      // كلمة مرور قاعدة البيانات
$dbname = "cmt_system";  // اسم قاعدة البيانات

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
?>
