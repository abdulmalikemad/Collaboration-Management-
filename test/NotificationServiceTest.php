<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../NotificationAndCommunicationModule/NotificationManager.php';

class NotificationServiceTest extends TestCase {

    private $conn;
    private $notificationManager;

    protected function setUp(): void {
        $this->conn = $this->createMock(mysqli::class);
        $this->notificationManager = new NotificationManager($this->conn);
    }

    public function testSendTaskAssignmentNotification() {
        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->expects($this->once())
             ->method('bind_param')
             ->with('iis', 1, 100, "📌 تم تعيينك في المهمة: Test Task");
        $stmt->expects($this->once())
             ->method('execute')
             ->willReturn(true);

        $this->conn->method('prepare')->willReturn($stmt);

        $result = $this->notificationManager->sendTaskAssignmentNotification(1, 100, 'Test Task');
        $this->assertTrue($result);
    }

    public function testSendDeadlineReminder() {
        $stmt = $this->createMock(mysqli_stmt::class);
        $expectedMsg = "⏰ تذكير: غدًا هو آخر موعد لتسليم المهمة: Test Task (الموعد: 2025-06-17)";

        $stmt->expects($this->once())
             ->method('bind_param')
             ->with('iis', 1, 100, $expectedMsg);
        $stmt->expects($this->once())
             ->method('execute')
             ->willReturn(true);

        $this->conn->method('prepare')->willReturn($stmt);

        $result = $this->notificationManager->sendDeadlineReminder(1, 100, 'Test Task', '2025-06-17');
        $this->assertTrue($result);
    }

    public function testGetNotificationsForUser() {
        $stmt = $this->createMock(mysqli_stmt::class);
        $resultMock = $this->createMock(mysqli_result::class);

        $stmt->expects($this->once())
             ->method('bind_param')
             ->with('i', 1);
        $stmt->expects($this->once())
             ->method('execute')
             ->willReturn(true);
        $stmt->expects($this->once())
             ->method('get_result')
             ->willReturn($resultMock);

        $this->conn->method('prepare')->willReturn($stmt);

        $result = $this->notificationManager->getNotificationsForUser(1);
        $this->assertSame($resultMock, $result);
    }
}
