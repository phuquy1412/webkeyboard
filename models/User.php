<?php
/**
 * Lớp User: Quản lý người dùng và xác thực
 */
class User {
    private $conn;
    private $table = "users";

    public $id;
    public $email;
    public $password;
    public $full_name;
    public $phone;
    public $address;
    public $is_admin;

    /**
     * Constructor
     * @param $db PDO database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo người dùng mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
            SET
                full_name = :full_name,
                email = :email,
                password = :password,
                phone = :phone,
                address = :address";

        $stmt = $this->conn->prepare($query);

        // Làm sạch dữ liệu
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));

        // Hash mật khẩu
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Gán dữ liệu vào statement
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Kiểm tra email đã tồn tại chưa
     * @return bool
     */
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->email]);

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Tìm user theo email
     * @return PDOStatement
     */
    public function findByEmail() {
        $query = "SELECT * FROM " . $this->table . " WHERE email = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->email]);

        return $stmt;
    }

    /**
     * Tìm user theo ID
     * @param $id int User ID
     * @return array|false
     */
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    /**
     * Cập nhật mật khẩu
     * @param $email string User email
     * @param $password string New password
     * @return bool
     */
    public function updatePassword($email, $password) {
        $query = "UPDATE " . $this->table . "
            SET
                password = :password
            WHERE
                email = :email";

        $stmt = $this->conn->prepare($query);

        // Hash mật khẩu
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Gán dữ liệu
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':email', $email);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>