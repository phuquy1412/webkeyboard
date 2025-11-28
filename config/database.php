<?php
class Database {
   private $host = "sql100.infinityfree.com";    // MySQL host từ InfinityFree
    private $db_name = "if0_40520566_banphim";   // Tên database
    private $username = "if0_40520566";          // Username
    private $password = "3vswWyKzJfk";  // Mật khẩu vPanel của đại ca
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Kết nối database thất bại. Vui lòng liên hệ quản trị viên.");
        }
        return $this->conn;
    }
}
?>