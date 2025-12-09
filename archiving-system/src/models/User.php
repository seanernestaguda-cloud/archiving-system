<?php

class User {
    private $id;
    private $username;
    private $password;

    public function __construct($id, $username, $password) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function save() {
        // Code to save user to the database
    }

    public function delete() {
        // Code to delete user from the database
    }

    public static function findById($id) {
        // Code to find a user by ID
    }

    public static function findByUsername($username) {
        // Code to find a user by username
    }
}