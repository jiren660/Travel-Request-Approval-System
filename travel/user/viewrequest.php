<?php
require_once "../extras/layout.php";
require_once "../classes/travel.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
  header("Location: ../main/login.php");
  exit;
}

$travelObj = new Travel();
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;
$fromPage = $_GET['from'] ?? null; // Capture back page from query string
$prevIdQuery = $_GET['prev'] ?? null;
$nextIdQuery = $_GET['next'] ?? null;

if (!$id) {
  header("Location: requests.php");
  exit;
}

$request = $travelObj->getRequestById($id);
if (!$request || $request['user_id'] != $user_id) {
  echo "<p class='text-center text-gray-600 mt-10'>Request not found or unauthorized.</p>";
  exit;
}
$conn = $travelObj->getConnection();

// Check if travel date has already passed and not yet cancelled/rejected
$currentDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime($request['start_date']));

if (strtotime($startDate) < strtotime($currentDate) && 
    !in_array(strtolower($request['status']), ['cancelled', 'rejected', 'completed'])) {
    
    $remarks = "Past travel date, no response from admin.";
    $status = "Cancelled";

    // Update status in database
    $updateSql = "UPDATE travel_request 
                  SET status = :status, remarks = :remarks 
                  WHERE request_id = :id";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':status', $status);
    $updateStmt->bindParam(':remarks', $remarks);
    $updateStmt->bindParam(':id', $id);
    $updateStmt->execute();

    // Reflect changes in the current $request variable so UI updates instantly
    $request['status'] = $status;
    $request['remarks'] = $remarks;
}

$expenses = $travelObj->getExpenses($id);

// Use prev/next IDs from query if available
$prevId = $prevIdQuery;
$nextId = $nextIdQuery;

// Fallback: fetch IDs by status for default navigation
if (!$prevId && !$nextId) {
    $statusLower = strtolower(trim($request['status']));
    $sql = "SELECT request_id FROM travel_request 
            WHERE user_id = :user_id AND LOWER(status) = :status 
            ORDER BY request_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':status', $statusLower, PDO::PARAM_STR);
    $stmt->execute();
    $allIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $currentIndex = array_search($id, $allIds);
    $nextId = ($currentIndex !== false && $currentIndex < count($allIds) - 1) ? $allIds[$currentIndex + 1] : null;
    $prevId = ($currentIndex > 0) ? $allIds[$currentIndex - 1] : null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>View Request</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex justify-center items-center min-h-screen bg-[#f8f8f8] p-6">

<main class="bg-white rounded-xl shadow-lg border border-[#b88686] p-8 w-full max-w-4xl mx-auto">

  <h1 class="text-2xl font-semibold text-[#800000] mb-3 text-left">
    <?= htmlspecialchars($request['subject']) ?>
  </h1>
  <p class="text-gray-500 text-sm mb-6 text-left">
    Submitted on <?= date('F d, Y h:i A', strtotime($request['created_at'])) ?>
  </p>

  <div class="grid grid-cols-2 gap-4 text-gray-800">
    <p><strong>Destination:</strong> <?= htmlspecialchars($request['destination']) ?></p>
    <p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']) ?></p>
    <p><strong>Start Date:</strong> <?= date('F d, Y', strtotime($request['start_date'])) ?></p>
    <p><strong>End Date:</strong> <?= date('F d, Y', strtotime($request['end_date'])) ?></p>
    <p><strong>Total Cost:</strong> ₱<?= number_format($request['total_cost'], 2) ?></p>

    <p><strong>Status:</strong> 
      <span id="status-badge" class="px-3 py-1 rounded text-white <?= 
        strtolower($request['status']) == 'approved' ? 'bg-green-600' : 
        (strtolower($request['status']) == 'rejected' ? 'bg-red-600' : 
        (strtolower($request['status']) == 'cancelled' ? 'bg-gray-600' : 'bg-orange-600')) 
      ?>">
        <?= ucfirst($request['status']) ?>
      </span>
    </p>

    <?php if (strtolower($request['status']) === 'approved' && !empty($request['approved_budget'])): ?>
      <p><strong>Approved Budget:</strong> ₱<?= number_format($request['approved_budget'], 2) ?></p>
    <?php endif; ?>
  </div>

  <?php if (!empty($request['remarks']) && strtolower($request['status']) !== 'pending'): ?>
    <div class="mt-4 p-3 border rounded bg-gray-50">
      <strong>Remarks:</strong>
      <p class="text-gray-700 mt-1"><?= nl2br(htmlspecialchars($request['remarks'])) ?></p>
    </div>
  <?php endif; ?>

  <h2 class="text-xl font-semibold text-[#800000] mt-8 mb-3">Breakdown of Costs</h2>
  <?php if (!empty($expenses)): ?>
    <div class="overflow-x-auto">
      <table class="w-full border border-gray-200 rounded-lg">
        <thead class="bg-[#800000] text-white">
          <tr>
            <th class="py-2 px-4 text-left">Expense Type</th>
            <th class="py-2 px-4 text-right">Amount (₱)</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php $total = 0; foreach ($expenses as $expense): $total += $expense['amount']; ?>
            <tr class="border-t">
              <td class="py-2 px-4"><?= htmlspecialchars($expense['expense_type'] ?? '-') ?></td>
              <td class="py-2 px-4 text-right"><?= number_format($expense['amount'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="font-semibold bg-gray-100 border-t">
            <td class="py-2 px-4 text-right">Total</td>
            <td class="py-2 px-4 text-right">₱<?= number_format($total, 2) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-gray-600 italic mt-2">No expenses recorded for this request.</p>
  <?php endif; ?>

  <div class="mt-6 flex flex-wrap gap-3" id="action-buttons">
    <?php if (strtolower($request['status']) === 'pending'): ?>
      <a href="updaterequest.php?id=<?= $request['request_id'] ?>" 
         class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Edit Request
      </a>
    <?php endif; ?>

    <?php if (in_array(strtolower($request['status']), ['pending', 'approved'])): ?>
      <button id="cancelBtn" 
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
        Cancel Request
      </button>
    <?php endif; ?>
  </div>

  <!-- Cancel Reason Modal -->
<div id="cancel-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
    <h3 class="text-lg font-semibold text-gray-800 mb-3">Cancel Request</h3>
    <label class="block text-gray-700 font-medium mb-2">Reason (optional)</label>
    <textarea id="cancel-reason" class="w-full border p-2 rounded mb-3" rows="2" placeholder="Enter your reason (optional)..."></textarea>
    <div class="flex justify-end gap-3">
      <button id="nextCancel" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Next</button>
      <button id="closeCancel" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Close</button>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-sm text-center">
    <p class="text-gray-800 font-medium mb-4">Are you sure you want to cancel this request?</p>
    <div class="flex justify-center gap-4">
      <button id="yesCancel" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Yes, Cancel</button>
      <button id="noCancel" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">No</button>
    </div>
  </div>
</div>

<div class="mt-8 flex justify-between items-center w-full">
  <?php if ($prevId): ?>
    <a href="viewrequest.php?id=<?= $prevId ?>&from=<?= urlencode($fromPage) ?>" 
       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
      ← Previous
    </a>
  <?php else: ?>
    <span></span>
  <?php endif; ?>

  <a href="<?= 'requests.php' ?>" 
   class="text-[#800000] font-medium hover:underline">
    Back to Requests
  </a>

  <?php if ($nextId): ?>
    <a href="viewrequest.php?id=<?= $nextId ?>&from=<?= urlencode($fromPage) ?>" 
       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
      Next →
    </a>
  <?php else: ?>
    <span></span>
  <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const cancelBtn = document.getElementById("cancelBtn");
  const cancelModal = document.getElementById("cancel-modal");
  const confirmModal = document.getElementById("confirm-modal");
  const nextBtn = document.getElementById("nextCancel");
  const closeBtn = document.getElementById("closeCancel");
  const yesBtn = document.getElementById("yesCancel");
  const noBtn = document.getElementById("noCancel");
  const reasonInput = document.getElementById("cancel-reason");
  const statusBadge = document.getElementById("status-badge");

  // Show reason modal
  cancelBtn?.addEventListener("click", () => {
    cancelModal.classList.remove("hidden");
  });

  // Close reason modal
  closeBtn?.addEventListener("click", () => {
    cancelModal.classList.add("hidden");
  });

  // Proceed to confirmation modal
  nextBtn?.addEventListener("click", () => {
    cancelModal.classList.add("hidden");
    confirmModal.classList.remove("hidden");
  });

  // Go back / cancel confirmation
  noBtn?.addEventListener("click", () => {
    confirmModal.classList.add("hidden");
  });

  // Confirm cancellation
  yesBtn?.addEventListener("click", async () => {
    const reason = reasonInput.value.trim();
    const requestId = <?= json_encode($id) ?>;

    try {
      const res = await fetch("update_cancel_request.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ request_id: requestId, reason })
      });
      const data = await res.json();

      if (data.success) {
        confirmModal.classList.add("hidden");
        cancelBtn.disabled = true;
        cancelBtn.classList.add("opacity-50", "cursor-not-allowed");
        statusBadge.textContent = "Cancelled";
        statusBadge.className = "px-3 py-1 rounded text-white bg-gray-600";
        alert("Request successfully cancelled!");
      } else {
        alert("Failed to cancel request.");
      }
    } catch (err) {
      alert("An error occurred while cancelling the request.");
      console.error(err);
    }
  });
});
</script>
