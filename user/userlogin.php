<?php
session_start();
$error_message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type']; // success or error
    $error_message = $message;
    if (isset($_SESSION['redirect_to_dashboard'])) {
        $redirect_to_dashboard = true;
        unset($_SESSION['redirect_to_dashboard']);
    } else {
        $redirect_to_dashboard = false;
    }
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

include 'connection.php'; // Adjust the path to your connection file as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user by username and status
    $query = "SELECT * FROM users WHERE username = '$username' AND status = 'verified'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Prevent admin user type from logging in here
        if ($user['user_type'] === 'admin') {
            $_SESSION['message'] = "User not found";
            $_SESSION['message_type'] = "error";
            header("Location: userlogin.php");
            exit();
        }

        // Verify hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['id'] = $user['id'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['message'] = "Login successful! Redirecting to dashboard...   ";
            $_SESSION['message_type'] = "success";
            $_SESSION['redirect_to_dashboard'] = true;
            header("Location: userlogin.php");
            exit();
        }
    }
    $_SESSION['message'] = "Invalid login credentials or account not verified.";
    $_SESSION['message_type'] = "error";
    header("Location: userlogin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="employeeloginstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <title>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM - Login Form</title>

</head>
<body>
<!-- Notification Modal -->
<div id="notificationModal" class="notification-modal">
    <div class="notification-content <?php echo isset($messageType) ? $messageType : ''; ?>">
        <span id="notification-message">
        <i class="fa-solid fa-circle-exclamation"></i>
            <h3>
                <?php
                if (isset($message)) {
                    echo htmlspecialchars($message);
                }
                ?>
            </h3>
        </span>
    </div>
</div>

<div class="main-container">
<div class="info-container">
    <h2>BUREAU OF FIRE PROTECTION REPORT ARCHIVING SYSTEM</h2>
    <div class="images-row">
        <img src="../bfp.png" alt="BFP Logo">
        <img src="../matalam.png" alt="Matalam Logo">
    </div>
    </div>
    <div class="form-container">
        <form action="userlogin.php" method="POST">
            <div class="form_header">
                <!-- Circle containing user icon -->
                <div class="icon-circle">
                    <i class="fas fa-user user-icon"></i>
                </div>
                <h2>User</h2>
            </div>
            <div class="input-container">
                <!-- <label for="username"> Username </label> -->
                <input type="text" id="username" name="username" required="required" placeholder="Username">
            </div>
            <div class="input-container">
                <!-- <label for="password"> Password </label> -->
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" required="required" placeholder="Password">
                    <span id="togglePassword" class="eye-icon"><i class="fas fa-eye-slash"></i></span>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">LOGIN</button>
            </div>
            <div class="links-container">
                <a href="/archiving system/index.php">Back to Home</a>
                <div class="sign-up-container">
                    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="errorModal" class="success-modal">
    <div class="success-modal-content">
        <i class="fa-solid fa-triangle-exclamation" style="color:red;font-size:2rem;"></i>
        <h2>Invalid Action</h2>
        <hr>
        <p id="errorMessage"></p>
    </div>
</div>
     
        <div id="successModal" class="success-modal">
    <div class="success-modal-content">
        <i class="fa-regular fa-circle-check"></i> <h2>Success!</h2>
        <p id="successMessage"></p>
    </div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', () => {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        eyeIcon.classList.toggle('fa-eye-slash');
        eyeIcon.classList.toggle('fa-eye');
    });
});

<?php if (isset($redirect_to_dashboard) && $redirect_to_dashboard): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('successMessage').textContent = "<?php echo htmlspecialchars($message); ?>";
    document.getElementById('successModal').style.display = 'block';

    setTimeout(function() {
        window.location.href = "userdashboard.php";
    }, 2000);

    document.getElementById('closeSuccessModal')?.addEventListener('click', function() {
        document.getElementById('successModal').style.display = 'none';
        window.location.href = "userdashboard.php";
    });
    window.onclick = function(event) {
        if (event.target === document.getElementById('successModal')) {
            document.getElementById('successModal').style.display = 'none';
            window.location.href = "userdashboard.php";
        }
    };
});
<?php elseif ($error_message): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('errorMessage').textContent = "<?php echo $error_message; ?>";
    document.getElementById('errorModal').style.display = 'block';

    // Auto-close modal after 2 seconds
    setTimeout(function() {
        document.getElementById('errorModal').style.display = 'none';
    }, 2000);

    // Optional: manual close handlers (if you add a close button)
    document.getElementById('closeErrorModal')?.addEventListener('click', function() {
        document.getElementById('errorModal').style.display = 'none';
    });
    window.onclick = function(event) {
        if (event.target === document.getElementById('errorModal')) {
            document.getElementById('errorModal').style.display = 'none';
        }
    };
});
<?php endif; ?>
</script>
</body>
</html>
