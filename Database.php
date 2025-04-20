<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'Database.php';

// إنشاء كائن من فئة Database
$db = new Database();

// الاتصال بقاعدة البيانات
$conn = $db->connect();

// إجراء استعلام على قاعدة البيانات
$query = "SELECT * FROM users";
$stmt = $conn->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// عرض النتائج
foreach ($results as $row) {
    echo $row['name'] . " - " . $row['email'] . "<br>";
}

// إغلاق الاتصال
$db->disconnect();
?>
