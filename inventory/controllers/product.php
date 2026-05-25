<?php
require_once __DIR__ . '/../public/database.config.php';
class ProductController {
  private $conn;
  function __construct($server_name, $username, $password, $db_name) {
    $this->conn = new mysqli($server_name, $username, $password, $db_name);
    if ($this->conn->connect_error) { die("Connection failed: " . $this->conn->connect_error); }
  }
  function add($name, $description, $quantity, $price, $category) {
    $stmt = $this->conn->prepare("INSERT INTO products (name, description, quantity, price, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiis", $name, $description, $quantity, $price, $category);
    $result = $stmt->execute(); $stmt->close(); return $result;
  }
  function getAll() {
    $result = $this->conn->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = [];
    while ($row = $result->fetch_assoc()) { $products[] = $row; }
    return $products;
  }
  function getById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $result = $stmt->get_result(); $product = $result->fetch_assoc(); $stmt->close(); return $product;
  }
  function update($id, $name, $description, $quantity, $price, $category) {
    $stmt = $this->conn->prepare("UPDATE products SET name=?, description=?, quantity=?, price=?, category=? WHERE id=?");
    $stmt->bind_param("ssiisi", $name, $description, $quantity, $price, $category, $id);
    $result = $stmt->execute(); $stmt->close(); return $result;
  }
  function delete($id) {
    $stmt = $this->conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id); $result = $stmt->execute(); $stmt->close(); return $result;
  }
  function getTotalProducts() {
    $result = $this->conn->query("SELECT COUNT(*) as total FROM products");
    return $result->fetch_assoc()['total'];
  }
  function getLowStock($threshold = 5) {
    $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM products WHERE quantity <= ?");
    $stmt->bind_param("i", $threshold); $stmt->execute();
    $result = $stmt->get_result(); return $result->fetch_assoc()['total'];
  }
  function getTotalValue() {
    $result = $this->conn->query("SELECT SUM(quantity * price) as total FROM products");
    return $result->fetch_assoc()['total'] ?? 0;
  }
  function getCategories() {
    $result = $this->conn->query("SELECT DISTINCT category FROM products");
    $cats = [];
    while ($row = $result->fetch_assoc()) { $cats[] = $row['category']; }
    return $cats;
  }
}