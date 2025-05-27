<?php
class ReportGenerator {
    private $conn;

    // المُنشئ - يستقبل اتصال قاعدة البيانات
    public function __construct($conn) {
        $this->conn = $conn;
    }

    //  توليد تقرير تقدم المشروع
    public function generateProjectProgressReport($projectId) {
        try {
            // احسب إجمالي المهام
            $totalTasks = $this->countTasks($projectId);

            // احسب عدد المهام المكتملة (الحالة = "مكتملة")
            $completedTasks = $this->countTasksByStatus($projectId, 'مكتملة');

            // احسب عدد الملفات المرفوعة ضمن المهام المرتبطة بالمشروع
            $fileCount = $this->countFiles($projectId);

            // احسب نسبة التقدم = (مكتملة / الكلية) × 100
            $progressRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

            // رجّع التقرير كمصفوفة بيانات
            return [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress_rate' => $progressRate . '%',
                'files_uploaded' => $fileCount
            ];
        } catch (Exception $e) {
            // في حالة حدوث خطأ، أرجع رسالة الخطأ بدل البيانات
            return [
                'error' => 'حدث خطأ أثناء توليد التقرير: ' . $e->getMessage()
            ];
        }
    }

    //  دالة لحساب إجمالي المهام في المشروع
    private function countTasks($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            return $count;
        } catch (Exception $e) {
            // في حالة فشل الاستعلام، رجّع صفر
            return 0;
        }
    }

    //  دالة لحساب عدد المهام حسب الحالة (مكتملة، قيد التنفيذ، الخ)
    private function countTasksByStatus($projectId, $status) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = ?");
            $stmt->bind_param("is", $projectId, $status);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            return $count;
        } catch (Exception $e) {
            // في حالة فشل الاستعلام، رجّع صفر
            return 0;
        }
    }

    //  دالة لحساب عدد الملفات المرتبطة بمهام هذا المشروع
    private function countFiles($projectId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM task_files
                WHERE task_id IN (
                    SELECT id FROM tasks WHERE project_id = ?
                )
            ");
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            return $count;
        } catch (Exception $e) {
            // في حالة فشل الاستعلام، رجّع صفر
            return 0;
        }
    }
}
