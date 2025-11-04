<?php
require_once "../extras/layout.php";
require_once "../classes/database.php";

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
  header("Location: ../main/login.php");
  exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'] ?? null;
$user = [];

if ($user_id) {
  $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, username, password FROM users WHERE id = :id");
  $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $first_name = trim($_POST["first_name"]);
  $middle_name = trim($_POST["middle_name"]);
  $last_name = trim($_POST["last_name"]);
  $email = trim($_POST["email"]);
  $username = trim($_POST["username"]);
  $reenter_password = trim($_POST["reenter_password"]);
  $new_password = trim($_POST["new_password"]);
  $confirm_password = trim($_POST["confirm_password"]);

  if (empty($first_name) || empty($last_name) || empty($email) || empty($username)) {
    $message = "All fields except passwords are required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Invalid email format.";
  } elseif (!empty($new_password) || !empty($confirm_password) || !empty($reenter_password)) {
    // User wants to change password
    if (!password_verify($reenter_password, $user["password"])) {
      $message = "Wrong password.";
    } elseif ($new_password !== $confirm_password) {
      $message = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
      $message = "Password must be at least 6 characters long.";
    } else {
      $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
      $sql = "UPDATE users 
              SET first_name=:first_name, middle_name=:middle_name, last_name=:last_name, 
                  email=:email, username=:username, password=:password 
              WHERE id=:id";
      $stmt = $conn->prepare($sql);
      $updateSuccess = $stmt->execute([
        ":first_name" => $first_name,
        ":middle_name" => $middle_name,
        ":last_name" => $last_name,
        ":email" => $email,
        ":username" => $username,
        ":password" => $hashedPassword,
        ":id" => $user_id
      ]);

      if ($updateSuccess) {
        header("Location: profile.php?update=success");
        exit;
      }
    }
  } else {
    // No password change
    $sql = "UPDATE users 
            SET first_name=:first_name, middle_name=:middle_name, last_name=:last_name, 
                email=:email, username=:username 
            WHERE id=:id";
    $stmt = $conn->prepare($sql);
    $updateSuccess = $stmt->execute([
      ":first_name" => $first_name,
      ":middle_name" => $middle_name,
      ":last_name" => $last_name,
      ":email" => $email,
      ":username" => $username,
      ":id" => $user_id
    ]);

    if ($updateSuccess) {
      header("Location: profile.php?update=success");
      exit;
    }
  }
}

ob_start();
?>
<style>
body {
  font-family: 'Poppins', sans-serif;
  background-color: #f8fafc;
}

.profile-container {
  max-width: 900px;
  margin: 3rem auto;
  background: white;
  border-radius: 1rem;
  padding: 1.5rem 3rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.profile-title {
  font-size: 2rem;
  font-weight: 700;
  text-align: center;
  color: #1e293b;
  margin-bottom: 2rem;
}

form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem 3rem;
}

.field-group {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

label {
  width: 40%;
  font-weight: 600;
  color: #1e293b;
  text-align: left;
}

input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 1rem;
  background-color: #f8fafc;
}

input:focus {
  outline: none;
  border-color: #800000;
  background-color: #fff;
}

.error-message, .success-message {
  grid-column: span 2;
  text-align: center;
  font-weight: 500;
}

.error-message {
  color: red;
}

.success-message {
  color: green;
}

.actions {
  grid-column: span 2;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
}

.btn {
  padding: 0.6rem 1.5rem;
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: 0.3s;
}

.btn-save {
  background-color: #800000;
}

.btn-save:hover {
  background-color: #a30000;
}

.btn-cancel {
  background-color: #475569;
}

.btn-cancel:hover {
  background-color: #334155;
}

@media (max-width: 768px) {
  form {
    grid-template-columns: 1fr;
  }
  .field-group {
    flex-direction: column;
    align-items: stretch;
  }
  label {
    width: 100%;
  }
}
</style>

<div class="profile-container">
  <h2 class="profile-title">Edit Profile</h2>

  <form method="POST">
    <?php if (!empty($message)): ?>
      <div class="<?= strpos($message, 'successfully') !== false ? 'success-message' : 'error-message' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="field-group">
      <label>First Name</label>
      <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
    </div>

    <div class="field-group">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
    </div>

    <div class="field-group">
      <label>Middle Name</label>
      <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
    </div>

    <div class="field-group">
      <label>Re-enter Password (current)</label>
      <input type="password" name="reenter_password" placeholder="Enter your current password">
    </div>

    <div class="field-group">
      <label>Last Name</label>
      <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
    </div>

    <div class="field-group">
      <label>New Password</label>
      <input type="password" name="new_password" placeholder="Optional">
    </div>

    <div class="field-group">
      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
    </div>

    <div class="field-group">
      <label>Confirm Password</label>
      <input type="password" name="confirm_password" placeholder="Optional">
    </div>

    <div class="actions">
      <button type="submit" class="btn btn-save">Save Changes</button>
      <a href="profile.php" class="btn btn-cancel">Cancel</a>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
renderLayout("Edit Profile", $content);
?>
