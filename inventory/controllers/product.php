<?php
require_once __DIR__ . '/../public/database.config.php';

class ProductController {
    private $conn;

    function __construct($server_name, $username, $password, $db_name) {
        $this->conn = new mysqli($server_name, $username, $password, $db_name);
        if ($this->conn->connect_error) { die("Connection failed: " . $this->conn->connect_error); }
    }

    function add($name, $description, $quantity, $category) {
        $stmt = $this->conn->prepare("INSERT INTO products (name, description, quantity, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $description, $quantity, $category);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    function getAll() {
        // Exclude __cat_placeholder__ rows everywhere
        $result = $this->conn->query("SELECT * FROM products WHERE name != '__cat_placeholder__' ORDER BY created_at DESC");
        $products = [];
        while ($row = $result->fetch_assoc()) { $products[] = $row; }
        return $products;
    }

    function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $product;
    }

    function update($id, $name, $description, $quantity, $category) {
        $stmt = $this->conn->prepare("UPDATE products SET name=?, description=?, quantity=?, category=? WHERE id=?");
        $stmt->bind_param("ssisi", $name, $description, $quantity, $category, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    function getTotalProducts() {
        // Exclude placeholders
        $result = $this->conn->query("SELECT COUNT(*) as total FROM products WHERE name != '__cat_placeholder__'");
        return $result->fetch_assoc()['total'];
    }

    function getLowStock($threshold = 5) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM products WHERE quantity <= ? AND name != '__cat_placeholder__'");
        $stmt->bind_param("i", $threshold);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];
        $stmt->close();
        return $count;
    }

    function getCategories() {
        // Read from dedicated categories table
        $result = $this->conn->query("SELECT name FROM categories ORDER BY name ASC");
        $cats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) { $cats[] = $row['name']; }
        }
        // Fallback: if categories table is empty, get distinct from products
        if (empty($cats)) {
            $r2 = $this->conn->query("SELECT DISTINCT category FROM products WHERE name != '__cat_placeholder__' AND category != '' ORDER BY category ASC");
            if ($r2) { while ($row = $r2->fetch_assoc()) { $cats[] = $row['category']; } }
        }
        return $cats;
    }
}