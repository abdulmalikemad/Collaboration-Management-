<?php
include 'database_connection.php';
// تضمين ملف الاتصال بقاعدة البيانات
include 'database_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // جمع البيانات المدخلة في النموذج
    $name = $_POST['name'];
    $studentId = $_POST['studentId'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $gender = $_POST['gender'];

    // التحقق من صحة البيانات المدخلة
    if (empty($name) || empty($studentId) || empty($email) || empty($password) || empty($confirmPassword) || empty($gender)) {
        echo "جميع الحقول مطلوبة.";
        exit;
    }

    if ($password !== $confirmPassword) {
        echo "كلمات المرور غير متطابقة.";
        exit;
    }

    // هنا يمكنك تشفير كلمة المرور
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // إدخال البيانات في قاعدة البيانات
    $sql = "INSERT INTO users (name, studentId, email, password, gender) 
            VALUES ('$name', '$studentId', '$email', '$hashedPassword', '$gender')";

    if ($conn->query($sql) === TRUE) {
        echo "تم إنشاء الحساب بنجاح!";
    } else {
        echo "خطأ في إنشاء الحساب: " . $conn->error;
    }

    // غلق الاتصال
    $conn->close();
}
?>
