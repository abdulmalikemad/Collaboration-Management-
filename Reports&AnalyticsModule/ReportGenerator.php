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

}