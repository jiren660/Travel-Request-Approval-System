<?php 
require_once "../classes/travel.php";
session_start();

// Restrict to admin only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../main/login.php");
    exit;
}

$travelObj = new Travel();
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: admin-requests.php");
    exit;
}

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $remarks = $_POST['remarks'] ?? null;

    // Prevent negative or invalid budgets
    $approved_budget = isset($_POST['approved_budget']) ? floatval($_POST['approved_budget']) : 0;
    if ($approved_budget < 0) $approved_budget = 0;

    $admin_id = $_SESSION['user_id']; // currently logged-in admin ID

    if ($action === 'approve') {
    $travelObj->approveRequest($id, $approved_budget, $remarks, $admin_id);
    } elseif ($action === 'reject') {
    $travelObj->rejectRequest($id, $remarks, $admin_id);
    }


    header("Location: viewrequest.php?id=$id");
    exit;
}

// Fetch current request
$request = $travelObj->getRequestById($id);
if (!$request) {
    echo "<p class='text-center text-gray-600 mt-10'>Request not found.</p>";
    exit;
}

// Fetch expenses
$expenses = $travelObj->getExpenses($id);

$status = strtolower(trim($request['status']));
$allIds = $travelObj->getAllRequestIdsByStatus($status);

$currentIndex = array_search($id, $allIds);
$prevId = ($currentIndex > 0) ? $allIds[$currentIndex - 1] : null;
$nextId = ($currentIndex !== false && $currentIndex < count($allIds) - 1) ? $allIds[$currentIndex + 1] : null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Request</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex justify-center items-center min-h-screen bg-[#800000] p-6">

<main class="bg-white rounded-xl shadow-lg border border-[#b88686] flex flex-row w-full max-w-5xl mx-auto">

  <!-- MAIN CONTENT SECTION -->
  <section class="<?= strtolower(trim($request['status'])) === 'pending' ? 'w-3/4 border-r border-gray-200' : 'w-full' ?> p-8 overflow-y-auto">

    <h1 class="text-2xl font-semibold text-[#800000] mb-3"><?= htmlspecialchars($request['subject']) ?></h1>

    <!-- Submitted Info -->
    <p class="text-gray-500 text-sm mb-1">
      Submitted on <?= date('F d, Y h:i A', strtotime($request['created_at'])) ?>
    </p>
    <p class="text-gray-700 text-sm font-semibold mb-6">
      by 
      <a href="user.php?id=<?= htmlspecialchars($request['user_id']) ?>" 
         class="text-[#800000] hover:underline">
         <?= htmlspecialchars($request['username']) ?>
      </a>
    </p>

    <div class="grid grid-cols-2 gap-4 text-gray-800">
      <p><strong>Destination:</strong> <?= htmlspecialchars($request['destination']) ?></p>
      <p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']) ?></p>
      <p><strong>Start Date:</strong> <?= date('F d, Y', strtotime($request['start_date'])) ?></p>
      <p><strong>End Date:</strong> <?= date('F d, Y', strtotime($request['end_date'])) ?></p>
      <p><strong>Total Cost:</strong> ₱<?= number_format($request['total_cost'], 2) ?></p>

      <?php if ($request['approved_budget'] !== null): ?>
        <p><strong>Approved Budget:</strong> ₱<?= number_format($request['approved_budget'], 2) ?></p>
      <?php endif; ?>

      <p><strong>Status:</strong> 
        <span class="px-3 py-1 rounded text-white <?= 
          strtolower($request['status'])=='approved' ? 'bg-green-600' : 
          (strtolower($request['status'])=='rejected' ? 'bg-red-600' : 'bg-yellow-600') ?>">
          <?= ucfirst($request['status']) ?>
        </span>
      </p>
    </div>

    <?php if (!empty($request['remarks'])): ?>
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
          <?php 
          $total = 0;
          foreach ($expenses as $expense): 
            $total += $expense['amount'];
          ?>
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

    <!-- Navigation Buttons -->
    <div class="mt-8 flex justify-between items-center">
      <?php if ($prevId): ?>
        <a href="viewrequest.php?id=<?= $prevId ?>" 
           class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
          ← Previous
        </a>
      <?php else: ?><span></span><?php endif; ?>

      <a href="<?= 'admin-requests.php' ?>" 
   class="text-[#800000] font-medium hover:underline">
  Back to Requests
</a>


      <?php if ($nextId): ?>
        <a href="viewrequest.php?id=<?= $nextId ?>" 
           class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
          Next →
        </a>
      <?php else: ?><span></span><?php endif; ?>
    </div>

  </section>

  <!-- SIDEBAR (only visible if pending) -->
  <?php if (strtolower(trim($request['status'])) === 'pending'): ?>
  <aside class="w-1/4 p-6 bg-gray-100 text-black flex flex-col justify-between border-l border-gray-300">

    <form method="POST" class="space-y-5">
      <!-- Budget Required -->
      <div>
        <label class="block font-semibold mb-2">Budget Required:</label>
        <p id="budgetRequired" class="px-3 py-2 rounded text-black bg-transparent">
          ₱<?= number_format($request['total_cost'], 2) ?>
        </p>
      </div>

      <!-- Approved Budget + Full Button -->
      <div id="budgetSection">
        <label for="approved_budget" class="block font-semibold mb-2">Approved Budget:</label>
        <div class="flex gap-2">
          <input type="number" step="0.01" min="0" id="approved_budget" name="approved_budget"
                 class="w-full p-2 rounded text-black border border-gray-300 focus:ring-2 focus:ring-[#b22222]"
                 placeholder="Enter approved amount">
          <button type="button" id="fullBudgetBtn"
                  class="px-3 py-2 bg-[#b22222] hover:bg-[#a11e1e] text-white rounded transition">
            Full
          </button>
        </div>
      </div>

      <!-- Remarks (Optional) -->
      <div>
        <label for="remarks" class="block font-semibold mb-2">Remarks (Optional):</label>
        <textarea name="remarks" id="remarks" rows="4"
                  class="w-full p-2 rounded text-black border border-gray-300 focus:ring-2 focus:ring-[#b22222]"
                  placeholder="Add remarks..."></textarea>
      </div>

      <!-- Approve / Reject Buttons -->
      <div class="flex flex-col gap-3 mt-4">
        <button type="submit" name="action" value="approve"
                class="w-full bg-green-600 py-2 rounded hover:bg-green-700 font-semibold text-white"
                id="approveBtn">
          Approve
        </button>
        <button type="submit" name="action" value="reject"
                class="w-full bg-red-600 py-2 rounded hover:bg-red-700 font-semibold text-white"
                id="rejectBtn">
          Reject
        </button>
      </div>
    </form>
  </aside>
  <?php endif; ?>

</main>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const fullBtn = document.getElementById("fullBudgetBtn");
    const approvedInput = document.getElementById("approved_budget");
    const total = <?= json_encode($request['total_cost']) ?>;

    // Fill full amount
    fullBtn?.addEventListener("click", () => {
      approvedInput.value = parseFloat(total).toFixed(2);
    });

    // Prevent negative
    approvedInput?.addEventListener("input", () => {
      if (approvedInput.value < 0) {
        approvedInput.value = "";
        alert("Budget cannot be negative!");
      }
    });
  });
</script>

</body>
</html>
