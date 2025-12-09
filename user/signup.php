<?php
// Include database connection
include('connection.php');

// Fetch departments from the database
$departmentQuery = "SELECT * FROM departments";
$departmentResult = $conn->query($departmentQuery);

// Initialize message variables
$successMsg = '';
$errorMsg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and trim to remove extra spaces
    $firstName = htmlspecialchars(trim($_POST['first_name'] ?? ''));
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    // Check if required fields are empty   
    if (empty($firstName) || empty($lastName) || empty($username) || empty($password) || empty($confirmPassword)) {
        $errorMsg = "Please fill in all required fields.";
    } elseif (strtolower($username) === 'admin') {
        // Prevent 'admin' username
        $errorMsg = "The username 'admin' cannot be used.";
    } elseif ($password !== $confirmPassword) {
        // Check if passwords match
        $errorMsg = "Passwords do not match.";
    } else {
        // Check if username already exists
        $checkQuery = "SELECT * FROM users WHERE username = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $errorMsg = "Username is already taken. Please choose a different one.";
        } else {
            // Hash the password before storing
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Prepare and bind the SQL statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, birthday, address, department, contact, username, gender, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $firstName, $middleName, $lastName, $birthday, $address, $department, $contact, $username, $gender, $hashedPassword);

            // Execute the statement
            if ($stmt->execute()) {
                $successMsg = "Registration successful! Redirecting to login...";
            } else {
                $errorMsg = "Error: " . $stmt->error;
            }

            // Close the insert statement
            $stmt->close();
        }

        // Close the check statement only once, after all usage
        $checkStmt->close();
    }

    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="signupstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <title>Sign Up</title>
</head>
<body>
    <div class="container">
        <form action="signup.php" method="post">
            <div class="form_header">
                <h2>Registration</h2>
            </div>
            <hr>
            <div class="name-container"> 
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="middle_name" placeholder="Middle Name">
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>
            <input type="text" id="dateInput" name="birthday" placeholder="Birthday" onfocus="(this.type='date')" onblur="(this.type='text')" required>
            <input type="text" name="address" placeholder="Address" required>

            <!-- Department dropdown populated dynamically -->
            <select name="department" required>
                <option value="">Department</option>
                <?php
                // Populate the departments dynamically
                if ($departmentResult->num_rows > 0) {
                    while ($row = $departmentResult->fetch_assoc()) {
                        echo "<option value='" . $row['departments'] . "'>" . $row['departments'] . "</option>";
                    }
                } else {
                    echo "<option value=''>No departments available</option>";
                }
                ?>
            </select>

            <input type="text" name="contact" placeholder="Contact No." required>
            <input type="text" name="username" placeholder="Username or Email" required>
            <select name="gender" required>
                <option value="">Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span id="togglePassword" class="toggle-password"><i class="fa fa-eye-slash"></i></span>
            </div>
            <div class="password-container">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <span id="toggleConfirmPassword" class="toggle-password"><i class="fa fa-eye-slash"></i></span>
            </div>
            <div class = "form-actions">
            <button type="submit">Register</button>
            <a href="../index.php"> Back to Home </a>
            </div>
        </form>
    </div>

<div id="successModal" class="success-modal" style="display: none;">
    <div class="success-modal-content">
        <i class="fa-regular fa-circle-check"></i>
        <h2>Success!</h2>
        <p id="successMessage"></p>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="success-modal" style="display: none;">
    <div class="success-modal-content">
        <i class="fa-regular fa-circle-xmark" style="color: #dc3545; font-size: 2.5rem; margin-bottom: 1rem;"></i>
        <h2 style="color: #dc3545;">Error!</h2>
        <p id="errorMessage"></p>
    </div>
</div>
</body>
</html>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const passwordEyeIcon = togglePassword.querySelector('i');
    
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPassword = document.getElementById('confirm_password');
        const confirmPasswordEyeIcon = toggleConfirmPassword.querySelector('i');
    
        // Function to toggle password visibility and icon
        function toggleVisibility(field, icon) {
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
            
            // Toggle eye icon
            icon.classList.toggle('fa-eye-slash');
            icon.classList.toggle('fa-eye');
        }
    
        // Event listener for Password field
        togglePassword.addEventListener('click', () => {
            toggleVisibility(password, passwordEyeIcon);
        });
    
        // Event listener for Confirm Password field
        toggleConfirmPassword.addEventListener('click', () => {
            toggleVisibility(confirmPassword, confirmPasswordEyeIcon);
        });
    });

  <?php if (!empty($successMsg)): ?>
    document.getElementById('successMessage').textContent = "<?php echo $successMsg; ?>";
    document.getElementById('successModal').style.display = 'block';
    setTimeout(function() {
        window.location.href = "userlogin.php";
    }, 2000);
<?php elseif (!empty($errorMsg)): ?>
    document.getElementById('errorMessage').textContent = "<?php echo $errorMsg; ?>";
    document.getElementById('errorModal').style.display = 'block';
    setTimeout(function() {
        document.getElementById('errorModal').style.display = 'none';
    }, 2000);
<?php endif; ?>
    </script>
</script>
