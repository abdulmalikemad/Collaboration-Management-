<?php
class Database {
  private $host = "localhost";
  private $dbname = "cmt";
  private $user = "root";
  private $pass = "";

  public function connect() {
    try {
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
      if ($conn->connect_error) {
        throw new Exception("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
      }
      return $conn;
    } catch (Exception $e) {
      error_log("Database Connection Error: " . $e->getMessage()); // سجل الخطأ في ملف logs
      die("⚠️ حدث خطأ أثناء الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقًا.");
    }
  }
}
