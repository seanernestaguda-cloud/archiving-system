<?php

class UserController {
    private $userModel;
    private $authModel;

    public function __construct() {
        $this->userModel = new User();
        $this->authModel = new Auth();
    }

    public function login($username, $password) {
        if ($this->authModel->loginUser($username, $password)) {
            // Redirect to user dashboard or home page
            header("Location: /user/profile.php");
            exit();
        } else {
            // Handle login failure
            return "Invalid username or password.";
        }
    }

    public function register($username, $password) {
        if ($this->userModel->createUser($username, $password)) {
            // Redirect to login page after successful registration
            header("Location: /user/login.php");
            exit();
        } else {
            // Handle registration failure
            return "Registration failed. Please try again.";
        }
    }

    public function logout() {
        $this->authModel->logoutUser();
        // Redirect to login page after logout
        header("Location: /user/login.php");
        exit();
    }

    public function getUserProfile($userId) {
        return $this->userModel->getUserById($userId);
    }
}