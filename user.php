<?php
class User {
  private $conn; // متغير خاص لحفظ الاتصال بقاعدة البيانات

  // مُنشئ الكلاس يستقبل الاتصال ويخزنه
  public function __construct($conn) {
    $this->conn = $conn;
  }

  // دالة تسجيل المستخدم (بدون تشفير كلمة المرور)
  public function register($data) {
    try {
      // استخراج البيانات من المصفوفة
      $name = $data['name'];
      $studentId = $data['studentId'];
      $email = $data['email'];
      $password = $data['password'];
      $confirm = $data['confirmPassword'];
      $gender = $data['gender'];

      // تحديد الدور بناءً على رقم القيد
      if (str_starts_with($studentId, '1')) {
        $role = 'دكتور';
      } elseif (str_starts_with($studentId, '2')) {
        $role = 'طالب';
      } else {
        return " رقم القيد غير صالح لتحديد الدور";
      }

      // التحقق من تطابق كلمتي المرور
      if ($password !== $confirm) {
        return " كلمتا المرور غير متطابقتين";
      }

     
      $stmt = $this->conn->prepare("INSERT INTO users (name, student_id, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssss", $name, $studentId, $email, $password, $gender, $role);

      // تنفيذ العملية والتحقق من نجاحها
      if ($stmt->execute()) {
        return " تم التسجيل بنجاح!";
      } else {
        return " فشل التسجيل: " . $stmt->error;
      }

    } catch (Exception $e) {
      // في حال حدوث خطأ استثنائي
      return " حدث خطأ: " . $e->getMessage();
    }
  }

  // دالة تسجيل الدخول (بدون تحقق من التشفير)
  public function login($data) {
    try {
      // جلب رقم القيد وكلمة المرور من المستخدم
      $studentId = $data['studentId'];
      $password = $data['password'];

      // استعلام للحصول على المستخدم بناءً على رقم القيد
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE student_id = ? LIMIT 1");
      $stmt->bind_param("s", $studentId);
      $stmt->execute();
      $result = $stmt->get_result();

      // إذا لم يوجد المستخدم
      if ($result->num_rows === 0) {
        return " لا يوجد مستخدم بهذا الرقم";
      }

      // استخراج بيانات المستخدم
      $user = $result->fetch_assoc();

      // التحقق من تطابق كلمة المرور
      if ($password !== $user['password']) {
        return " كلمة المرور غير صحيحة";
      }

      // تخزين بيانات المستخدم في الجلسة
      $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'student_id' => $user['student_id'],
        'role' => $user['role']
      ];

      // إعادة توجيه المستخدم حسب دوره
      if ($user['role'] === 'طالب') {
        header("Location: student_dashboard.php");
        exit();
      } elseif ($user['role'] === 'دكتور') {
        header("Location: supervisor_dashboard.php");
        exit();
      } else {
        return " رقم القيد غير صالح لتحديد الدور.";
      }

    } catch (Exception $e) {
      // في حال حدوث خطأ أثناء تسجيل الدخول
      return " خطأ أثناء تسجيل الدخول: " . $e->getMessage();
    }
  }
}
?>
