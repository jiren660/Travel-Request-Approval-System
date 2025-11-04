<?php
require_once "../extras/layout.php";
require_once "../classes/database.php";

// Redirect if not logged in or wrong role
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
  header("Location: ../main/login.php");
  exit();
}

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'] ?? null;
$user = [];

if (isset($_GET['update']) && $_GET['update'] === 'success') {
    echo "<script>alert('Profile updated successfully!');</script>";
}


if ($user_id) {
  $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, username, department FROM users WHERE id = :id");
  $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
  padding: 3rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.profile-title {
  font-size: 2rem;
  font-weight: 700;
  text-align: center;
  color: #1e293b;
  margin-bottom: 2rem;
}

.profile-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem 2rem;
}

.profile-item {
  display: flex;
  align-items: center;
}

.profile-item label {
  width: 140px;
  font-weight: 600;
  color: #1e293b;
  margin-right: 1rem;
  text-align: left;
}

.profile-item .value-box {
  background-color: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 0.75rem;
  padding: 10px 15px;
  font-size: 1.1rem;
  color: #334155;
  text-align: left;
  flex: 1;
}

.profile-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2.5rem;
}

.btn {
  display: inline-block;
  padding: 0.6rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
}

.btn-edit {
  background-color: #800000;
  color: white;
}

.btn-edit:hover {
  background-color: #a30000;
}

.btn-pass {
  background-color: #475569;
  color: white;
}

.btn-pass:hover {
  background-color: #334155;
}
</style>

<div class="profile-container">
  <h2 class="profile-title">My Profile</h2>

  <div class="profile-grid">
    <div class="profile-item">
      <label>First Name</label>
      <div class="value-box"><?= htmlspecialchars($user["first_name"] ?? "") ?></div>
    </div>

    <div class="profile-item">
      <label>Middle Name</label>
      <div class="value-box"><?= htmlspecialchars($user["middle_name"] ?? "") ?></div>
    </div>

    <div class="profile-item">
      <label>Last Name</label>
      <div class="value-box"><?= htmlspecialchars($user["last_name"] ?? "") ?></div>
    </div>

    <div class="profile-item">
      <label>Email</label>
      <div class="value-box"><?= htmlspecialchars($user["email"] ?? "") ?></div>
    </div>

    <div class="profile-item">
      <label>Username</label>
      <div class="value-box"><?= htmlspecialchars($user["username"] ?? "") ?></div>
    </div>

    <div class="profile-item">
      <label>Department</label>
      <div class="value-box"><?= htmlspecialchars($user["department"] ?? "") ?></div>
    </div>

  </div>

  <div class="profile-actions">
    <a href="edit_profile.php" class="btn btn-edit">Edit Profile</a>
  </div>
</div>

<?php
$content = ob_get_clean();
renderLayout("View Profile", $content);
?>
