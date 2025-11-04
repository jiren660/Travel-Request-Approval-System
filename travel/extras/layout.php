<?php
// user_layout.php — reusable layout for all user pages
session_start();

// Prevent caching (for Back button after logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
  session_destroy();
  header("Location: ../main/login.php");
  exit;
}

function renderLayout($page_title, $content) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../extras/layout.css">
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
      width: 180px;
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
  </style>
</head>
<body>

  <div class="sidebar" id="sidebar">
    <div class="icon" id="menuBtn">
      <img src="../assets/yumburger.png" alt="Menu" />
      <span class="text">Menu</span>
    </div>

    <a href="../user/home.php" class="icon">
      <img src="../assets/home1.png" alt="Home" />
      <span class="text">Home</span>
    </a>

    <a href="../user/viewrequest.php" class="icon">
      <img src="../assets/view.png" alt="Requests" />
      <span class="text">Requests</span>
    </a>

    <a href="../user/addrequest.php" class="icon">
      <img src="../assets/add.png" alt="New Request" />
      <span class="text">New Request</span>
    </a>
  </div>

  <div class="main">
    <header>
      <h1>Travel Request Approval System</h1>
      <div class="profile-wrapper">
        <div class="profile-icon" id="profileBtn">
          <img src="../assets/user.png" alt="Profile" />
        </div>

        <!-- Dropdown Menu -->
        <div class="dropdown-menu" id="dropdownMenu">
          <a href="../user/profile.php">View Profile</a>
          <a href="../user/cancelled_requests.php">Cancelled Requests</a>
          <form method="POST" id="logoutForm">
            <button type="submit" name="logout">Logout</button>
          </form>
        </div>
      </div>
    </header>

    <div class="content">
      <?= $content ?>
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
<?php
}
?>
