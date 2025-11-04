<?php
require_once "../extras/layout.php";
require_once "../classes/travel.php";

$travelObj = new Travel();
$user_id = $_SESSION["user_id"] ?? 1;

$status = 'cancelled';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';

$requests = $travelObj->getRequestsByStatus($user_id, $status);

// Search filter
if (!empty($search)) {
    $search = strtolower($search);
    $requests = array_filter($requests, function($req) use ($search) {
        return str_contains(strtolower($req['subject']), $search) ||
               str_contains(strtolower($req['destination']), $search) ||
               str_contains(strtolower($req['purpose']), $search);
    });
}

// Sorting
if ($sort === 'date') {
    usort($requests, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
} elseif ($sort === 'cost') {
    usort($requests, fn($a, $b) => $b['total_cost'] <=> $a['total_cost']);
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
                placeholder="Search cancelled requests..." 
                class="search-input"
            >

            <select name="sort" class="sort-select">
                <option value="">Sort by</option>
                <option value="date" <?= $sort=='date'?'selected':'' ?>>Date</option>
                <option value="cost" <?= $sort=='cost'?'selected':'' ?>>Total Cost</option>
            </select>

            <button type="submit" class="apply-btn">Apply</button>
        </div>
    </form>

    <div class="requests-list">
        <?php if (empty($requests)): ?>
            <p class="no-requests">No cancelled requests found.</p>
        <?php else: ?>
            <?php foreach ($requests as $req): ?>
                <a href="viewrequest.php?id=<?= $req['request_id'] ?>" class="request-row">
                    <div class="request-info">
                        <div class="left-info">
                            <h2 class="subject"><?= htmlspecialchars($req['subject']) ?></h2>
                            <span class="destination"><?= htmlspecialchars($req['destination']) ?></span>
                        </div>
                        <div class="status-badge cancelled">
                            CANCELLED
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.request-info {
    display: flex;
    justify-content: space-between;
    flex-direction: row;
    gap: 150vh;
    align-items: start;
}

.status-badge.cancelled {
    z-index: 1;
    background-color: #797979ff; 
    color: white;
    font-weight: bold;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    text-transform: uppercase;
    white-space: nowrap;
}
</style>

<?php
$content = ob_get_clean();
renderLayout("Cancelled Requests", $content);
?>
