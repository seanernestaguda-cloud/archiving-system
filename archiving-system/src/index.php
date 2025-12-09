<?php
require_once 'config/database.php';
require_once 'controllers/fireReportsController.php';
require_once 'controllers/searchController.php';
require_once 'controllers/userController.php';

$fireReportsController = new FireReportsController();
$searchController = new SearchController();
$userController = new UserController();

// Simple routing mechanism
$requestUri = $_SERVER['REQUEST_URI'];

if (strpos($requestUri, '/fire-reports') === 0) {
    // Handle fire reports
    include 'views/fireReports/index.php';
} elseif (strpos($requestUri, '/search') === 0) {
    // Handle search
    include 'views/search/index.php';
} elseif (strpos($requestUri, '/user/login') === 0) {
    // Handle user login
    include 'views/user/login.php';
} elseif (strpos($requestUri, '/user/register') === 0) {
    // Handle user registration
    include 'views/user/register.php';
} elseif (strpos($requestUri, '/user/profile') === 0) {
    // Handle user profile
    include 'views/user/profile.php';
} else {
    // Default to fire reports
    include 'views/fireReports/index.php';
}
?>