<?php
class User {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // ✅ إضافة طالب
  public function addStudent($data) {
    return $this->insertUser($data, 'طالب');
  }

  // ✅ إضافة مشرف
  public function addSupervisor($data) {
    return $this->insertUser($data, 'دكتور');
  }

  // ✅ إضافة أدمن
  public function addAdmin($data) {
    return $this->insertUser($data, 'ادمن');
  }

  // 💡 دالة عامة لإعادة الاستخدام في الإضافة
  private function insertUser($data, $role) {
    try {
      $name = $data['name'];
      $studentId = $data['studentId'];
      $email = $data['email'];
      $password = $data['password'];
      $confirm = $data['confirmPassword'];
      $gender = $data['gender'];

      if ($password !== $confirm) {
        return "❌ كلمتا المرور غير متطابقتين";
      }

      $stmt = $this->conn->prepare("INSERT INTO users (name, student_id, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssss", $name, $studentId, $email, $password, $gender, $role);

      if ($stmt->execute()) {
        return "✅ تم إضافة المستخدم بنجاح!";
      } else {
        return "❌ فشل في الإضافة: " . $stmt->error;
      }

    } catch (Exception $e) {
      return "❌ خطأ أثناء الإضافة: " . $e->getMessage();
    }
  }

  // ✅ تعديل بيانات مستخدم
  public function updateUser($id, $data, $role) {
    try {
      $name = $data['name'];
      $studentId = $data['student_id'];
      $email = $data['email'];
      $gender = $data['gender'];

      $stmt = $this->conn->prepare("UPDATE users SET name = ?, student_id = ?, email = ?, gender = ? WHERE id = ? AND role = ?");
      $stmt->bind_param("ssssis", $name, $studentId, $email, $gender, $id, $role);

      return $stmt->execute();

    } catch (Exception $e) {
      error_log("❌ Error while updating user: " . $e->getMessage());
      return false;
    }
  }

  // ✅ حذف مستخدم
  public function deleteUser($id, $role) {
    try {
      $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ? AND role = ?");
      $stmt->bind_param("is", $id, $role);
      return $stmt->execute();
    } catch (Exception $e) {
      error_log("❌ Error while deleting user: " . $e->getMessage());
      return false;
    }
  }

  // ✅ تسجيل الدخول
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

      switch ($user['role']) {
        case 'طالب':
          header("Location: student_dashboard.php"); break;
        case 'دكتور':
          header("Location: supervisor_dashboard.php"); break;
        case 'ادمن':
          header("Location: admin_dashboard.php"); break;
        default:
          return "❌ دور المستخدم غير معروف.";
      }

      exit();

    } catch (Exception $e) {
      return "❌ خطأ أثناء تسجيل الدخول: " . $e->getMessage();
    }
  }

  // ✅ جلب كل الأدمن من قاعدة البيانات
  public function getAllAdmins($search = '') {
    try {
      if (!empty($search)) {
        $like = '%' . $search . '%';
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE role = 'ادمن' AND (name LIKE ? OR student_id LIKE ?)");
        $stmt->bind_param("ss", $like, $like);
      } else {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE role = 'ادمن'");
      }

      $stmt->execute();
      return $stmt->get_result();

    } catch (Exception $e) {
      error_log("❌ Error in getAllAdmins: " . $e->getMessage());
      return false;
    }
  }
  // ✅ جلب كل الطلاب من قاعدة البيانات
public function getAllStudents($search = '') {
  try {
    if (!empty($search)) {
      $like = '%' . $search . '%';
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE role = 'طالب' AND (name LIKE ? OR student_id LIKE ?)");
      $stmt->bind_param("ss", $like, $like);
    } else {
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE role = 'طالب'");
    }

    $stmt->execute();
    return $stmt->get_result();

  } catch (Exception $e) {
    error_log("❌ Error in getAllStudents: " . $e->getMessage());
    return false;
  }
}
// ✅ جلب مستخدم حسب ID والدور
public function getUserByIdAndRole($id, $role) {
  try {
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? AND role = ?");
    $stmt->bind_param("is", $id, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      return null;
    }

    return $result->fetch_assoc();

  } catch (Exception $e) {
    error_log("❌ Error in getUserByIdAndRole: " . $e->getMessage());
    return false;
  }
}

}

?>
