<?php

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function loginUser($username, $password) {
        // Logic to authenticate user
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set user session
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }

    public function logoutUser() {
        // Logic to log out user
        session_start();
        session_unset();
        session_destroy();
    }

    public function checkUserAccess($requiredRole) {
        // Logic to check user access rights
        if (isset($_SESSION['user_id'])) {
            $query = "SELECT role FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user['role'] === $requiredRole;
        }
        return false;
    }
}