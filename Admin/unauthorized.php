<?php
// unauthorized.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; margin: 0; padding: 0; }
        .container { max-width: 400px; margin: 100px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; text-align: center; }
        h1 { color: #c0392b; margin-bottom: 16px; }
        p { color: #555; margin-bottom: 24px; }
        a { color: #003D73; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Unauthorized</h1>
        <p>You do not have permission to access this page.</p>
        <a href="../index.html">Return to Home</a>
    </div>
</body>
</html>
