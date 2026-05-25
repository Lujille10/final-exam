<?php
require_once __DIR__ . '/../public/database.config.php';
class AccountController {
  private $conn;
  function __construct($server_name, $username, $password, $db_name) {
    $this->conn = new mysqli($server_name, $username, $password, $db_name);
    if ($this->conn->connect_error) { die("Connection failed: " . $this->conn->connect_error); }
  }
  function register($username, $password) {
    $stmt = $this->conn->prepare("SELECT id FROM accounts WHERE username = ?");
    $stmt->bind_param("s", $username); $stmt->execute(); $stmt->store_result();
    if ($stmt->num_rows > 0) { return false; }
    $stmt->close();
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $this->conn->prepare("INSERT INTO accounts (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed);
    $result = $stmt->execute(); $stmt->close(); return $result;
  }
  function login($username, $password) {
    $stmt = $this->conn->prepare("SELECT id, username, password FROM accounts WHERE username = ?");
    $stmt->bind_param("s", $username); $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id']; $_SESSION['username'] = $row['username'];
        $stmt->close(); return true;
      }
    }
    $stmt->close(); return false;
  }
  function logout() { session_destroy(); header("Location: /inventory/index.php"); exit(); }
  function update($id, $username, $password) {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $this->conn->prepare("UPDATE accounts SET username=?, password=? WHERE id=?");
    $stmt->bind_param("ssi", $username, $hashed, $id);
    $result = $stmt->execute(); $stmt->close(); return $result;
  }
  function delete($id) {
    $stmt = $this->conn->prepare("DELETE FROM accounts WHERE id=?");
    $stmt->bind_param("i", $id); $result = $stmt->execute(); $stmt->close(); return $result;
  }
}