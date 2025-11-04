<?php
session_start();
require_once "../classes/Database.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $error = "Please fill all required fields.";
    } else {
        // ✅ Check if admin credentials
        if ($email === "wmsu@admin.com" && $password === "admin123") {
            $_SESSION['user_id'] = 0;
            $_SESSION['user_name'] = "Admin";
            $_SESSION['user_role'] = "admin";
            header("Location: ../admin/admin.php");
            exit;
        }

        // ✅ Otherwise, check database for regular users
        try {
            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role'];
                header("Location: ../user/home.php");
                exit;
            } else {
                $error = "Incorrect email or password.";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Travel Request Approval System</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
body { background-color: #650909; display: flex; align-items: center; height: 100vh; }
.logo { margin-left: 100px; }
.logo img { width: 300px; height: auto; }
.login-container { background-color: #ffffff; border-radius: 20px; padding: 40px; width: 340px; text-align: center; margin: 0 99px; position: relative; }
.login-container img { width: 90px; margin-bottom: 15px; }
.login-container h2 { color: #5b0a0a; font-size: 20px; font-weight: 700; margin-bottom: 25px; }
.error-message { color: red; font-size: 13px; margin-bottom: 10px; animation: shake 0.3s ease; display: <?php echo $error ? 'block' : 'none'; ?>; }
@keyframes shake { 0% { transform: translateX(0); } 25% { transform: translateX(-4px); } 50% { transform: translateX(4px); } 75% { transform: translateX(-4px); } 100% { transform: translateX(0); } }
.form-group { text-align: left; margin-bottom: 18px; }
label { color: #5b0a0a; font-weight: 500; display: block; margin-bottom: 5px; font-size: 14px; }
input { width: 100%; padding: 10px 15px; border: none; outline: none; border-radius: 25px; background-color: #e4e4e4; font-size: 14px; }
.btn-login { width: 100%; padding: 12px 0; background-color: #5b0a0a; color: #fff; border: none; border-radius: 25px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 5px; transition: 0.3s; }
.btn-login:hover { background-color: #7a1111; }
.forgot { text-align: right; margin-top: 10px; }
.forgot a { color: #5b0a0a; font-size: 12px; text-decoration: none; transition: 0.3s; }
.create-account { text-align: left; margin-top: 10px; }
.create-account a { color: #5b0a0a; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.3s; }
.forgot a:hover, .create-account a:hover { text-decoration: underline; }
@media (max-width: 768px) { body { flex-direction: column; justify-content: center; } .logo { margin: 0; } .logo img { width: 200px; } .login-container { margin-top: 20px; } }
</style>
</head>
<body>

<div class="logo">
    <img src="../assets/logo.png" alt="Travel Request Approval System Logo">
</div>

<div class="login-container">
    <img src="../assets/wmsulogo.png" alt="WMSU Logo">
    <h2>LOGIN</h2>

    <div id="error" class="error-message">
        <?php echo $error; ?>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?php echo $_POST['email'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="forgot">
            <a href="#">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login">Login</button>

        <div class="create-account">
            <a href="register.php">Create an Account</a>
        </div>
    </form>
</div>

</body>
</html>
