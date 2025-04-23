<?php
class Project {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // 🟢 إنشاء مشروع جديد مع تواريخ
  public function create($title, $description, $leader_id, $supervisor_id, $start_date, $end_date) {
    try {
      $stmt = $this->conn->prepare("
        INSERT INTO projects (title, description, leader_id, supervisor_id, status, start_date, end_date)
        VALUES (?, ?, ?, ?, 'بانتظار الموافقة', ?, ?)
      ");
      $stmt->bind_param("ssiiss", $title, $description, $leader_id, $supervisor_id, $start_date, $end_date);

      if ($stmt->execute()) {
        return $this->conn->insert_id;
      }

      return false;
    } catch (Exception $e) {
      error_log("Create Project Error: " . $e->getMessage());
      return false;
    }
  }

  // 🔄 ربط أعضاء المشروع
  public function assignMembers($project_id, $member_ids) {
    try {
      $stmt = $this->conn->prepare("INSERT INTO project_members (project_id, student_id) VALUES (?, ?)");
      foreach ($member_ids as $student_id) {
        $stmt->bind_param("ii", $project_id, $student_id);
        $stmt->execute();
      }
    } catch (Exception $e) {
      error_log("Assign Members Error: " . $e->getMessage());
    }
  }

  // 🧑‍🏫 جلب جميع المشرفين
  public function getSupervisors() {
    try {
      $query = "SELECT id, name FROM users WHERE role = 'دكتور'";
      return $this->conn->query($query);
    } catch (Exception $e) {
      error_log("Get Supervisors Error: " . $e->getMessage());
      return false;
    }
  }

  // 👨‍🎓 الطلاب المتاحين عدا القائد والمرتبطين بمشاريع (بانتظار الموافقة أو تمت الموافقة)
  public function getAvailableStudents($exclude_id) {
    try {
      $query = "
        SELECT id, name FROM users
        WHERE role = 'طالب'
          AND id != ?
          AND id NOT IN (
            SELECT leader_id FROM projects
            WHERE status IN ('بانتظار الموافقة', 'تمت الموافقة')
          )
          AND id NOT IN (
            SELECT pm.student_id
            FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            WHERE p.status IN ('بانتظار الموافقة', 'تمت الموافقة')
          )
      ";
      $stmt = $this->conn->prepare($query);
      $stmt->bind_param("i", $exclude_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("Get Available Students Error: " . $e->getMessage());
      return false;
    }
  }

  // 🕒 المشاريع في انتظار موافقة مشرف
  public function getPendingProjectsForSupervisor($supervisor_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT p.*, u.name AS leader_name
        FROM projects p
        JOIN users u ON p.leader_id = u.id
        WHERE p.supervisor_id = ? AND p.status = 'بانتظار الموافقة'
      ");
      $stmt->bind_param("i", $supervisor_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("Get Pending Projects Error: " . $e->getMessage());
      return false;
    }
  }

  // ✅ المشاريع الموافق عليها لمشرف
  public function getApprovedProjectsForSupervisor($supervisor_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT p.*, u.name AS leader_name
        FROM projects p
        JOIN users u ON p.leader_id = u.id
        WHERE p.supervisor_id = ? AND p.status = 'تمت الموافقة'
      ");
      $stmt->bind_param("i", $supervisor_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("Get Approved Projects Error: " . $e->getMessage());
      return false;
    }
  }

  // 👀 جلب مشروع معيّن بالتفاصيل
  public function getProjectById($project_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT p.*, u.name AS leader_name
        FROM projects p
        JOIN users u ON p.leader_id = u.id
        WHERE p.id = ?
      ");
      $stmt->bind_param("i", $project_id);
      $stmt->execute();
      return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
      error_log("Get Project By ID Error: " . $e->getMessage());
      return null;
    }
  }
}
?>
