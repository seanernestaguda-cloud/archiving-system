<?php
session_start(); // Start the session
include 'connection.php'; // Ensure this file is included to access the database

$error_message = ""; // Initialize error message variable
$show_success_modal = false; // Flag to show success modal

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Check if the user is the default admin
    if ($user === 'admin' && $pass === 'admin') {
        // Allow default admin to log in without verification check
        $_SESSION['username'] = $user;
        $show_success_modal = true;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();


    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Check if the password matches
        if (password_verify($pass, $row['password'])) {
            // Check if the user is an admin
            if ($row['user_type'] !== 'admin') {
                $error_message = "You are not authorized to access the admin panel.";
            }
            // Check if the user is verified
            else if ($row['status'] == 'verified') {
                // Set session and redirect to dashboard
                $_SESSION['username'] = $user;
                $show_success_modal = true;
            } else {
                // Display message if not verified
                $error_message = "Your account is not verified yet. Please contact an admin.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="adminloginstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <title>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM - Login Form</title>
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: left;
            max-width: 400px;
            width: 100%;
        }

        .modal-content h2 {
            margin: 0 0 15px;
            font-size: 15px;
        }

        .modal-content button {
            padding: 10px 20px;
            background: #003D73;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background: #011e38;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="info-container">
            <h2>BUREAU OF FIRE PROTECTION REPORT ARCHIVING SYSTEM</h2>
            <div class="images-row">
                <img src="../bfp.png" alt="BFP Logo">
                <img src="../matalam.png" alt="Matalam Logo">
            </div>
        </div>
        <div class="form-container">
            <form action="adminlogin.php" method="POST">
                <div class="form_header">
                    <!-- Circle containing user icon -->
                    <div class="icon-circle">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h2>Admin</h2>
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
            <i class="fa-regular fa-circle-check"></i>
            <h2>Success!</h2>
            <p id="successMessage"></p>
        </div>
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

        <?php if ($error_message): ?>
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

        <?php if ($show_success_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('successMessage').textContent = "Login successful! Redirecting to dashboard...";
                document.getElementById('successModal').style.display = 'block';
                setTimeout(function() {
                    window.location.href = "admindashboard.php";
                }, 1000);
            });
        <?php endif; ?>
    </script>
</body>

</html>