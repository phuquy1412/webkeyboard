<?php
/**
 * Lớp Product: Quản lý sản phẩm
 */
class Product {
    private $conn;
    private $table = "products";

    public $id;
    public $name;
    public $price;
    public $old_price;
    public $image_url;
    public $category;
    public $switch_type;
    public $description;
    public $stock;

    /**
     * Constructor
     * @param $db PDO database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Lấy tất cả sản phẩm theo thứ tự mới nhất
     * @return PDOStatement
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Lấy sản phẩm theo ID
     * @param $id int Product ID
     * @return array|null Product data
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm theo danh mục
     * @param $category string Danh mục sản phẩm
     * @return PDOStatement
     */
    public function getByCategory($category) {
        $query = "SELECT * FROM " . $this->table . " WHERE category = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Tạo sản phẩm mới
     * @return bool True on success, false on failure
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " SET name=:name, price=:price, old_price=:old_price, image_url=:image_url, category=:category, switch_type=:switch_type, description=:description, stock=:stock";
        $stmt = $this->conn->prepare($query);

        // Làm sạch dữ liệu
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->old_price = htmlspecialchars(strip_tags($this->old_price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->switch_type = htmlspecialchars(strip_tags($this->switch_type));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->stock = htmlspecialchars(strip_tags($this->stock));

        // Gán dữ liệu
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":old_price", $this->old_price);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":switch_type", $this->switch_type);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":stock", $this->stock);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Cập nhật sản phẩm
     * @return bool True on success, false on failure
     */
    public function update() {
        $query = "UPDATE " . $this->table . " SET name=:name, price=:price, old_price=:old_price, image_url=:image_url, category=:category, switch_type=:switch_type, description=:description, stock=:stock WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        // Làm sạch dữ liệu
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->old_price = htmlspecialchars(strip_tags($this->old_price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->switch_type = htmlspecialchars(strip_tags($this->switch_type));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Gán dữ liệu
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":old_price", $this->old_price);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":switch_type", $this->switch_type);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Xóa sản phẩm
     * @return bool True on success, false on failure
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        // Làm sạch dữ liệu
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Gán dữ liệu
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>