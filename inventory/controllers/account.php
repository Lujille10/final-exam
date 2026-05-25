<?php
require_once __DIR__ . '/../public/database.config.php';

class AccountController {
    private $conn;

    function __construct($server_name, $username, $password, $db_name) {
        $this->conn = new mysqli($server_name, $username, $password, $db_name);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // ── REGISTER ─────────────────────────────────────────────────────────
    function register($username, $password, $email = '', $full_name = '') {
        // Check duplicate username
        $stmt = $this->conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) { $stmt->close(); return 'username_taken'; }
        $stmt->close();

        // Check duplicate email (only if provided)
        if (!empty($email)) {
            $stmt = $this->conn->prepare("SELECT id FROM accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) { $stmt->close(); return 'email_taken'; }
            $stmt->close();
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare(
            "INSERT INTO accounts (username, password, email, full_name) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $username, $hashed, $email, $full_name);
        $result = $stmt->execute();
        $stmt->close();
        return $result ? true : false;
    }

    // ── LOGIN ─────────────────────────────────────────────────────────────
    // Accepts username OR email; stores role in session
    function login($identifier, $password) {
        $stmt = $this->conn->prepare(
            "SELECT id, username, password, role FROM accounts
             WHERE username = ? OR (email != '' AND email = ?)
             LIMIT 1"
        );
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['username']  = $row['username'];
                $_SESSION['role']      = $row['role'] ?? 'Staff'; // ← store role
                $stmt->close();
                return true;
            }
        }
        $stmt->close();
        return false;
    }

    function logout() {
        session_destroy();
        header("Location: /index.php");
        exit();
    }

    function update($id, $username, $password) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare(
            "UPDATE accounts SET username=?, password=? WHERE id=?"
        );
        $stmt->bind_param("ssi", $username, $hashed, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM accounts WHERE id=?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}