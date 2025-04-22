<?php
class User {
  private $conn; // متغير خاص لحفظ الاتصال بقاعدة البيانات

  // 🔹 المُنشئ: يستقبل الاتصال ويحفظه في المتغير الخاص
  public function __construct($conn) {
    $this->conn = $conn;
  }

  // 🔹 تسجيل طالب جديد
  public function register($data) {
    try {
      // استخراج البيانات من النموذج
      $name = $data['name'];
      $studentId = $data['studentId'];
      $email = $data['email'];
      $password = $data['password'];
      $confirm = $data['confirmPassword'];
      $gender = $data['gender'];
      $role = 'طالب'; // الدور ثابت لجميع الطلبة

      // التحقق من تطابق كلمتي المرور
      if ($password !== $confirm) {
        return "❌ كلمتا المرور غير متطابقتين";
      }

      // إعداد استعلام إدخال المستخدم
      $stmt = $this->conn->prepare("INSERT INTO users (name, student_id, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssss", $name, $studentId, $email, $password, $gender, $role);

      // تنفيذ الاستعلام
      if ($stmt->execute()) {
        return "✅ تم التسجيل بنجاح!";
      } else {
        return "❌ فشل التسجيل: " . $stmt->error;
      }

    } catch (Exception $e) {
      // في حال حدوث خطأ غير متوقع
      return "❌ حدث خطأ: " . $e->getMessage();
    }
  }

  // 🔹 تسجيل الدخول (لجميع الأدوار: طالب، دكتور، أدمن)
  public function login($data) {
    try {
      // استخراج بيانات تسجيل الدخول
      $studentId = $data['studentId'];
      $password = $data['password'];

      // البحث عن المستخدم برقم القيد
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE student_id = ? LIMIT 1");
      $stmt->bind_param("s", $studentId);
      $stmt->execute();
      $result = $stmt->get_result();

      // التحقق إن كان المستخدم موجودًا
      if ($result->num_rows === 0) {
        return "❌ لا يوجد مستخدم بهذا الرقم";
      }

      $user = $result->fetch_assoc();

      // التحقق من تطابق كلمة المرور (⚠️ غير مشفرة!)
      if ($password !== $user['password']) {
        return "❌ كلمة المرور غير صحيحة";
      }

      // حفظ بيانات المستخدم في الجلسة
      $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'student_id' => $user['student_id'],
        'role' => $user['role']
      ];

      // التوجيه حسب الدور
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

  // 🔹 إضافة مشرف جديد (يُستخدم من قبل الأدمن)
  public function addSupervisor($data) {
    try {
      // استخراج البيانات من النموذج
      $name = $data['name'];
      $studentId = $data['studentId'];
      $email = $data['email'];
      $password = $data['password'];
      $confirm = $data['confirmPassword'];
      $gender = $data['gender'];
      $role = 'دكتور'; // الدور هنا ثابت كمشرف

      // التحقق من تطابق كلمتي المرور
      if ($password !== $confirm) {
        return "❌ كلمتا المرور غير متطابقتين";
      }

      // إعداد استعلام الإدخال
      $stmt = $this->conn->prepare("INSERT INTO users (name, student_id, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssss", $name, $studentId, $email, $password, $gender, $role);

      // تنفيذ الإدخال
      if ($stmt->execute()) {
        return "✅ تم إضافة المشرف بنجاح!";
      } else {
        return "❌ فشل في الإضافة: " . $stmt->error;
      }

    } catch (Exception $e) {
      return "❌ خطأ أثناء إضافة المشرف: " . $e->getMessage();
    }
  }
}
?>
