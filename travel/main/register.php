<?php
session_start();
require_once "../classes/Database.php";

$feedback = "";
$feedback_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['firstName']);
    $middle_name = trim($_POST['middleName']);
    $last_name = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $department = trim($_POST['department']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // PHP backend validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($department) || empty($password) || empty($confirmPassword)) {
        $feedback = "Please fill all required fields.";
        $feedback_type = "error";
    } elseif ($password !== $confirmPassword) {
        $feedback = "Passwords do not match.";
        $feedback_type = "error";
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users 
                (first_name, middle_name, last_name, email, username, department, password, role) 
                VALUES (:first_name, :middle_name, :last_name, :email, :username, :department, :password, 'user')");

            $stmt->bindParam(":first_name", $first_name);
            $stmt->bindParam(":middle_name", $middle_name);
            $stmt->bindParam(":last_name", $last_name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":department", $department);
            $stmt->bindParam(":password", $hashed_password);

            $stmt->execute();

            $feedback = "Registration Successful!";
            $feedback_type = "success";
            $_POST = []; // clear form fields

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $feedback = "Email or username already exists.";
            } else {
                $feedback = "Database error: " . $e->getMessage();
            }
            $feedback_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Travel Request Approval System</title>
<style>
body {
    font-family: "Poppins", sans-serif;
    margin: 0;
    padding: 0;
    background: #650909;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.form-container {
    background: #ffffff;
    padding: 30px 50px;
    border-radius: 20px;
    max-width: 900px;
    width: 60%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    color: #4a0a0a;
    position: relative;
}

h1 {
    font-weight: 600;
    color: #5b0a0a;
    margin-bottom: 30px;
    text-align: left;
}

form {
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
}

.left-section,
.right-section {
    flex: 1;
    min-width: 350px;
}

label {
    font-weight: 500;
    margin-top: 10px;
    display: block;
    color: #5b0a0a;
}

input[type="text"],
input[type="email"],
input[type="password"],
select {
    width: 100%;
    padding: 10px 12px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    background-color: #fffaf9;
}

input[type="submit"] {
    width: 100%;
    padding: 12px;
    background-color: #800000;
    color: #fff;
    font-size: 15px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    margin-top: 20px;
    cursor: pointer;
    transition: 0.3s;
}

input[type="submit"]:hover {
    background-color: #a30000;
}

/* feedback */
.feedback {
    text-align: center;
    padding: 12px 20px;
    border-radius: 20px;
    font-weight: 600;
    color: #fff;
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 999;
}
.feedback.success { background-color: #28a745; }
.feedback.error { background-color: #dc3545; }

/* login link */
.login-link {
    text-align: center;
    font-size: 14px;
    color: #5b0a0a;
    margin-top: 20px;
}

.login-link a {
    color: #800000;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
}

.login-link a:hover {
    color: #a30000;
}

@media (max-width: 768px){
    form { flex-direction: column; gap: 20px; }
}
</style>
</head>
<body>

<div class="form-container">
    <h1>Create an Account</h1>

    <div id="feedback" class="feedback <?php echo $feedback_type; ?>" <?php if($feedback){echo 'style="display:block"';} ?>>
        <?php echo $feedback; ?>
    </div>

    <form id="registerForm" method="POST" action="">
        <div class="left-section">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" required value="<?php echo $_POST['firstName'] ?? '' ?>">

            <label for="middleName">Middle Name</label>
            <input type="text" id="middleName" name="middleName" value="<?php echo $_POST['middleName'] ?? '' ?>">

            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" required value="<?php echo $_POST['lastName'] ?? '' ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?php echo $_POST['email'] ?? '' ?>">

            <label for="department">Department</label>
            <select id="department" name="department" required>
                <option value="">Select Department</option>
                <option value="ACT" <?php if(($_POST['department'] ?? '') === 'ACT') echo 'selected'; ?>>ACT</option>
                <option value="BSCS" <?php if(($_POST['department'] ?? '') === 'BSCS') echo 'selected'; ?>>BSCS</option>
                <option value="BSIT" <?php if(($_POST['department'] ?? '') === 'BSIT') echo 'selected'; ?>>BSIT</option>
            </select>
        </div>

        <div class="right-section">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required value="<?php echo $_POST['username'] ?? '' ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <label for="confirmPassword">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required>

            <input type="submit" value="Register">
        </div>
    </form>

    <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<script>
// Basic JS validation (client-side)
document.getElementById('registerForm').addEventListener('submit', function(e){
    const fields = ['firstName', 'lastName', 'email', 'username', 'password', 'confirmPassword', 'department'];
    let feedbackText = '';

    for (let id of fields) {
        if (!document.getElementById(id).value.trim()) {
            feedbackText = "Please fill all required fields.";
            break;
        }
    }

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!feedbackText && !email.includes('@')) {
        feedbackText = "Invalid email format.";
    } else if (!feedbackText && password !== confirmPassword) {
        feedbackText = "Passwords do not match.";
    }

    if (feedbackText) {
        e.preventDefault();
        const feedback = document.getElementById('feedback');
        feedback.innerText = feedbackText;
        feedback.className = 'feedback error';
        feedback.style.display = 'block';
        setTimeout(() => feedback.style.display = 'none', 3000);
    }
});
</script>

</body>
</html>
