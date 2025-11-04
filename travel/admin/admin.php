<?php
session_start();

// Prevent caching (for Back button after logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (empty($_SESSION['user_role'])) {
    header("Location: ../main/login.php");
    exit();
}

// Only allow admin to access
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/home.php");
    exit();
}

// Logout logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../main/login.php");
    exit();
}

$username = $_SESSION['user_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin - Travel Request Approval System</title>
<link rel="stylesheet" href="../css/home.css">
<style>
  .dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 40px;
    background-color: #ffffff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 100;
    width: 200px;
  }

  .dropdown-menu a,
  .dropdown-menu button {
    display: block;
    width: 100%;
    padding: 10px 15px;
    text-align: left;
    background: none;
    border: none;
    outline: none;
    cursor: pointer;
    color: #333;
    font-size: 14px;
    text-decoration: none;
    transition: background 0.2s;
  }

  .dropdown-menu a:hover,
  .dropdown-menu button:hover {
    background-color: #f0f0f0;
  }

  .profile-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }

  .profile-icon img {
    width: 35px;
    height: 35px;
    cursor: pointer;
    border-radius: 50%;
  }

  h2.welcome {
    font-size: 1.8rem;
    text-align: center;
    margin-bottom: 2rem;
  }
  .buttons {
    margin-bottom: 2rem;
  }
  
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <div class="icon" id="menuBtn">
    <img src="../assets/yumburger.png" alt="Menu" />
    <span class="text">Menu</span>
  </div>
  <div class="icon">
    <img src="../assets/home1.png" alt="Home" />
    <span class="text">Home</span>
  </div>
  <a href="admin-requests.php" class="icon">
    <img src="../assets/view.png" alt="Requests" />
    <span class="text">Requests</span>
  </a>
  <!-- <a href="admin-users.php" class="icon">
    <img src="../assets/users.png" alt="Users" />
    <span class="text">Manage Users</span>
  </a> -->
</div>

<div class="main">
  <header>
    <h1>Admin | Travel Request Approval System</h1>
    <div class="profile-wrapper">
      <div class="profile-icon" id="profileBtn">
        <img src="../assets/user.png" alt="Profile" />
      </div>

      <!-- Dropdown Menu -->
      <div class="dropdown-menu" id="dropdownMenu">
        <a href="profile.php">View Profile</a>
        <a href="cancelled_requests.php">View Cancelled Requests</a>
        <!-- <a href="admin-users.php">Manage Users</a> -->
        <form method="POST" id="logoutForm">
          <button type="submit" name="logout">Logout</button>
        </form>
      </div>
    </div>
  </header>

  <div class="content">
    <h2 class="welcome">Welcome, <?= htmlspecialchars($username) ?>!</h2>
    <div class="buttons">
      <a href="admin-requests.php" style="color:white;"><button>View Travel Requests</button></a>
      <!-- <a href="admin-users.php" style="color:white;"><button>Manage Users</button></a> -->
    </div>
  </div>
</div>

<script>
  const menuBtn = document.getElementById('menuBtn');
  const sidebar = document.getElementById('sidebar');
  const profileBtn = document.getElementById('profileBtn');
  const dropdownMenu = document.getElementById('dropdownMenu');

  menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
  });

  profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
  });

  document.addEventListener('click', (e) => {
    if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
      dropdownMenu.style.display = 'none';
    }
  });
</script>

</body>
</html>
