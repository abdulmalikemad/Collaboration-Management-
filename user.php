<?php
class User {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // 🔹 تسجيل طالب فقط
  public function register($data) {
    try {
      $name = $data['name'];
      $studentId = $data['studentId'];
      $email = $data['email'];
      $password = $data['password'];
      $confirm = $data['confirmPassword'];
      $gender = $data['gender'];
      $role = 'طالب'; // ثابت لجميع الطلبة

      if ($password !== $confirm) {
        return "❌ كلمتا المرور غير متطابقتين";
      }

      $stmt = $this->conn->prepare("INSERT INTO users (name, student_id, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssss", $name, $studentId, $email, $password, $gender, $role);

      if ($stmt->execute()) {
        return "✅ تم التسجيل بنجاح!";
      } else {
        return "❌ فشل التسجيل: " . $stmt->error;
      }
    } catch (Exception $e) {
      return "❌ حدث خطأ: " . $e->getMessage();
    }
  }

  // 🔹 تسجيل الدخول لجميع الأدوار
  public function login($data) {
    try {
      $studentId = $data['studentId'];
      $password = $data['password'];

      $stmt = $this->conn->prepare("SELECT * FROM users WHERE student_id = ? LIMIT 1");
      $stmt->bind_param("s", $studentId);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows === 0) {
        return "❌ لا يوجد مستخدم بهذا الرقم";
      }

      $user = $result->fetch_assoc();

      if ($password !== $user['password']) {
        return "❌ كلمة المرور غير صحيحة";
      }

      $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'student_id' => $user['student_id'],
        'role' => $user['role']
      ];

      // التوجيه بناءً على الدور
      if ($user['role'] === 'طالب') {
        header("Location: student_dashboard.php");
        exit();
      } elseif ($user['role'] === 'دكتور') {
        header("Location: supervisor_dashboard.php");
        exit();
      } elseif ($user['role'] === 'ادمن') {
        header("Location: admin_dashboard.php");
        exit();
      } else {
        return "❌ دور المستخدم غير معروف.";
      }
    } catch (Exception $e) {
      return "❌ خطأ أثناء تسجيل الدخول: " . $e->getMessage();
    }
  }

  // 🔹 إضافة مشرف (فقط للأدمن)
  public function addSupervisor($data) {
    try {
      $name = $data['name'];
      $studentId = $data['studentId'];
      $email = $data['email'];
      $password = $data['password'];
      $confirm = $data['confirmPassword'];
      $gender = $data['gender'];
      $role = 'دكتور';

      if ($password !== $confirm) {
        return " كلمتا المرور غير متطابقتين";
      }

      $stmt = $this->conn->prepare("INSERT INTO users (name, student_id, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssss", $name, $studentId, $email, $password, $gender, $role);

      if ($stmt->execute()) {
        return " تم إضافة المشرف بنجاح!";
      } else {
        return " فشل في الإضافة: " . $stmt->error;
      }
    } catch (Exception $e) {
      return " خطأ أثناء إضافة المشرف: " . $e->getMessage();
    }
  }
}
?>
