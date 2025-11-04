<?php
session_start();
require_once "../classes/travel.php";

// Restrict to admin only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../main/login.php");
  exit;
}

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
  session_destroy();
  header("Location: ../main/login.php");
  exit;
}

$travelObj = new Travel();

// Filters
$status = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';

// Fetch all requests
$requests = $travelObj->getAllRequestsByStatus($status);

// --- Search Filter ---
if (!empty($search)) {
  $search = strtolower($search);
  $requests = array_filter($requests, function($req) use ($search) {
    return str_contains(strtolower($req['subject']), $search)
        || str_contains(strtolower($req['destination']), $search)
        || str_contains(strtolower($req['purpose']), $search)
        || str_contains(strtolower($req['username']), $search);
  });
}

// --- Sort ---
if ($sort === 'date') {
    usort($requests, fn($a, $b) => strtotime($a['start_date']) <=> strtotime($b['start_date']));
} elseif ($sort === 'cost') {
    usort($requests, fn($a, $b) => $b['total_cost'] <=> $a['total_cost']);
}

// Keep sorting and search params when switching tabs
function buildQuery($status, $search, $sort) {
    $params = [];
    if (!empty($search)) $params[] = "search=" . urlencode($search);
    if (!empty($sort)) $params[] = "sort=" . urlencode($sort);
    $params[] = "status=$status";
    return "?" . implode("&", $params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Requests - Travel Request Approval System</title>
  <link rel="stylesheet" href="../css/home.css">
  <style>
    * { font-family: "Poppins", sans-serif; box-sizing: border-box; }
    body { margin: 0; background-color: #f8f8f8; display: flex; height: 100vh; overflow: hidden; }
    .content { flex: 1; display: flex; flex-direction: column; background: #fff; height: 100vh; width: 100%; overflow: hidden; }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #800000;
      padding: 10px 10px;
      color: white;
      border-bottom: 1px solid #5a0000;
      width: 100%;
      margin: 0;
      position: relative;
      box-sizing: border-box;
    }

    .search-input { flex: 0 0 170px; padding: 8px 14px; border-radius: 20px; border: none; outline: none; background: white; color: #333; font-size: 0.9rem; }
    .sort-select { padding: 8px 12px; border-radius: 10px; border: none; outline: none; font-weight: 500; }
    .apply-btn { background-color: #a30000; color: white; border: none; padding: 8px 16px; font-weight: 600; border-radius: 10px; cursor: pointer; transition: background 0.3s; }
    .apply-btn:hover { background-color: #c20000; }

    .status-tabs { display: flex; gap: 10px; }
    .status-tabs a {
      padding: 8px 18px;
      border-radius: 30px;
      background-color: #5b0a0a;
      color: white;
      text-decoration: none;
      font-weight: 600;
      transition: background 0.3s, transform 0.2s;
    }
    .status-tabs a.active { background-color: #d32f2f; }
    .status-tabs a:hover { background-color: #b71c1c; transform: translateY(-1px); }

    .requests-list { flex: 1; overflow-y: auto; background-color: #fff; color: #000; width: 100%; font-size: 0.9rem; }
    .request-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 15px 25px; text-decoration: none; color: inherit; }
    .request-row:nth-child(even) { background-color: #fafafa; }
    .request-row:hover { background-color: #fff2f2; }

    .request-info { display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start; }
    .request-info h2 { margin: 0; line-height: 1.2; font-size: 1rem; color: #000; text-align: left; }
    .request-info p { margin: 2px 0 0; font-size: 0.85rem; color: #000; text-align: left; }

    .status-badge { padding: 6px 16px; border-radius: 9999px; color: white; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
    .status-badge.pending { background-color: #ff8000; }
    .status-badge.approved { background-color: #2ecc71; }
    .status-badge.rejected { background-color: #e74c3c; }

    .no-requests { text-align: center; color: #777; padding: 2rem; font-style: italic; }
    .requests-list::-webkit-scrollbar { width: 8px; }
    .requests-list::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 4px; }
    .requests-list::-webkit-scrollbar-thumb:hover { background-color: #aaa; }
    .searching { display: flex; align-items: center; gap: 10px; }

    /* Profile Dropdown */
    .profile-wrapper { position: relative; }
    .profile-icon { cursor: pointer;width: 35px;
    height: 35px; border-radius: 50%; overflow: hidden; }
    .profile-icon img { width: 100%; height: 100%; object-fit: cover; }
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 50px;
      right: 0;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      min-width: 210px;
      z-index: 1000;
    }
    .dropdown-menu a, .dropdown-menu button {
      display: block;
      width: 100%;
      padding: 10px 20px;
      text-decoration: none;
      color: #333;
      background: none;
      border: none;
      text-align: left;
      cursor: pointer;
      font-size: 0.9rem;
    }
    .dropdown-menu a:hover, .dropdown-menu button:hover { background-color: #f2f2f2; }
  </style>
</head>
<body>

  <div class="sidebar" id="sidebar">
    <div class="icon" id="menuBtn">
      <img src="../assets/yumburger.png" alt="Menu" />
      <span class="text">Menu</span>
    </div>
    <a href="admin.php" class="icon">
      <img src="../assets/home1.png" alt="Home" />
      <span class="text">Home</span>
    </a>
    <a href="admin-requests.php" class="icon active">
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
      <form method="GET" class="topbar">
        <div class="searching">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search..." class="search-input">
          <select name="sort" class="sort-select">
            <option value="">Sort by</option>
            <option value="date" <?= $sort=='date'?'selected':'' ?>>Travel Date (earliest first)</option>
            <option value="cost" <?= $sort=='cost'?'selected':'' ?>>Total Cost</option>
          </select>
          <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
          <button type="submit" class="apply-btn">Apply</button>
        </div>

        <div class="status-tabs">
          <a href="?status=pending" class="<?= $status=='pending'?'active':'' ?>">Pending</a>
          <a href="?status=approved" class="<?= $status=='approved'?'active':'' ?>">Approved</a>
          <a href="?status=rejected" class="<?= $status=='rejected'?'active':'' ?>">Rejected</a>
        </div>
      </form>

     <div class="requests-list">
    <?php if (empty($requests)): ?>
        <p class="no-requests">No <?= ucfirst($status) ?> requests found.</p>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
            <a href="viewrequest.php?id=<?= $req['request_id'] ?>" class="request-row">
                <div class="request-info">
                    <h2 class="subject"><?= htmlspecialchars($req['subject']) ?></h2>
                    <p class="preview">
                        <span class="light-text"><?= htmlspecialchars($req['destination']) ?></span> •
                        Requested by <strong><?= htmlspecialchars($req['username'] ?? $req['user_name']) ?></strong>
                    </p>
                    <p class="preview text-gray-600">
                        Travel: <?= date('M d, Y', strtotime($req['start_date'])) ?> – <?= date('M d, Y', strtotime($req['end_date'])) ?>
                    </p>
                </div>
                <div class="status-badge <?= strtolower($status) ?>">
                    <?= strtoupper($status) ?>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
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

    profileBtn.addEventListener('click', () => {
      dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function (e) {
      if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.style.display = 'none';
      }
    });
  </script>
</body>
</html>
