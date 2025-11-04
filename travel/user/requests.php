<?php
require_once "../extras/layout.php";
require_once "../classes/travel.php";

$travelObj = new Travel();
$user_id = $_SESSION["user_id"] ?? 1;

$status = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';

// Get requests from database
$requests = $travelObj->getRequestsByStatus($user_id, $status);

// 🔍 Search filter
if (!empty($search)) {
    $search = strtolower($search);
    $requests = array_filter($requests, function($req) use ($search) {
        return str_contains(strtolower($req['subject']), $search) ||
               str_contains(strtolower($req['destination']), $search) ||
               str_contains(strtolower($req['purpose']), $search);
    });
}

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

ob_start();
?>

<link rel="stylesheet" href="../css/requests.css">

<div class="content">
    <form method="GET" class="topbar">
        <div class="searching">
            <input 
                type="text" 
                name="search" 
                value="<?= htmlspecialchars($search) ?>" 
                placeholder="Search requests..." 
                class="search-input"
            >

            <select name="sort" class="sort-select">
                <option value="">Sort by</option>
                <option value="date" <?= $sort=='date'?'selected':'' ?>>Travel Date (earliest first)</option>
                <option value="cost" <?= $sort=='cost'?'selected':'' ?>>Total Cost</option>
            </select>

            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <button type="submit" class="apply-btn">Apply</button>
        </div>

        <div class="status-tabs">
            <a href="<?= buildQuery('pending', $search, $sort) ?>" class="<?= $status=='pending'?'active':'' ?>">Pending</a>
            <a href="<?= buildQuery('approved', $search, $sort) ?>" class="<?= $status=='approved'?'active':'' ?>">Approved</a>
            <a href="<?= buildQuery('rejected', $search, $sort) ?>" class="<?= $status=='rejected'?'active':'' ?>">Rejected</a>
        </div>
    </form>

    <div class="requests-list">
        <?php if (empty($requests)): ?>
            <p class="no-requests">No <?= ucfirst($status) ?> requests found.</p>
        <?php else: ?>
            <?php foreach ($requests as $index => $req): ?>
                <?php
                    // Back page depending on status
                    $backPage = ($status === 'pending') ? 'requests.php' 
                              : ($status === 'approved' ? 'approved_requests.php' 
                              : 'rejected_requests.php');

                    // Next/Prev IDs in current sorted order
                    $prevId = $index > 0 ? $requests[$index - 1]['request_id'] : null;
                    $nextId = $index < count($requests) - 1 ? $requests[$index + 1]['request_id'] : null;
                ?>
                <a href="viewrequest.php?id=<?= $req['request_id'] ?>&from=<?= urlencode($backPage) ?>&prev=<?= $prevId ?>&next=<?= $nextId ?>" class="request-row">
                    <div class="request-info">
                        <div class="left-info">
                            <h2 class="subject"><?= htmlspecialchars($req['subject']) ?></h2>
                            <span class="destination"><?= htmlspecialchars($req['destination']) ?></span>
                            <small class="date">Travel Start: <?= htmlspecialchars(date('M d, Y', strtotime($req['start_date']))) ?></small>
                        </div>
                    </div>
                    <div class="status-badge <?= strtolower($status) ?>">
                        <?= strtoupper($status) ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Left-align everything in request row */
.left-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.subject {
    text-align: left;
    margin: 0 0 4px 0;
}
.destination, .date {
    text-align: left;
    font-size: 0.9rem;
    color: #555;
}
</style>

<?php
$content = ob_get_clean();
renderLayout("Requests", $content);
?>
