<?php
require_once "../classes/travel.php";
require_once "../extras/layout.php";
// session_start();

$travelObj = new Travel();


$user_id = $_SESSION["user_id"] ?? 1;

// initialize form values
$request = [
    "subject" => "",
    "destination" => "",
    "purpose" => "",
    "start_date" => "",
    "end_date" => "",
    "expenses" => [],
    "total_cost" => ""
];

// initialize error messages
$errors = [
    "subject" => "",
    "destination" => "",
    "purpose" => "",
    "start_date" => "",
    "end_date" => "",
    "expenses" => "",
];

// handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $request["subject"] = trim(htmlspecialchars($_POST["subject"]));
    $request["destination"] = trim(htmlspecialchars($_POST["destination"]));
    $request["purpose"] = trim(htmlspecialchars($_POST["purpose"]));
    $request["start_date"] = trim(htmlspecialchars($_POST["start_date"]));
    $request["end_date"] = trim(htmlspecialchars($_POST["end_date"]));

    $expense_types = $_POST["expense_type"] ?? [];
    $amounts = $_POST["amount"] ?? [];
    $expenses = [];
    $total = 0;

    for ($i = 0; $i < count($expense_types); $i++) {
        $type = trim(htmlspecialchars($expense_types[$i]));
        $amt = floatval($amounts[$i]);
        if (!empty($type) && $amt > 0) {
            $expenses[] = ["expense_type" => $type, "amount" => $amt];
            $total += $amt;
        }
    }

    $request["expenses"] = $expenses;
    $request["total_cost"] = $total;

    // VALIDATIONS
    if (empty($request["subject"])) $errors["subject"] = "Subject is required.";
    if (empty($request["destination"])) $errors["destination"] = "Destination is required.";
    if (empty($request["purpose"])) $errors["purpose"] = "Purpose is required.";
    if (empty($request["start_date"])) $errors["start_date"] = "Start date is required.";
    if (empty($request["end_date"])) $errors["end_date"] = "End date is required.";
    if (empty($request["expenses"])) $errors["expenses"] = "Add at least one expense.";

    // Date validation
    $today = date('Y-m-d');
    if (!empty($request["start_date"]) && $request["start_date"] < $today) {
        $errors["start_date"] = "Start date cannot be in the past.";
    }
    if (!empty($request["end_date"]) && $request["end_date"] < $request["start_date"]) {
        $errors["end_date"] = "End date cannot be before start date.";
    }

    // if no errors, save request
    if (empty(array_filter($errors))) {
        $travelObj->user_id = $user_id;
        $travelObj->subject = $request["subject"];
        $travelObj->destination = $request["destination"];
        $travelObj->purpose = $request["purpose"];
        $travelObj->start_date = $request["start_date"];
        $travelObj->end_date = $request["end_date"];
        $travelObj->total_cost = $request["total_cost"];

        if ($travelObj->addRequest($request["expenses"])) {
            header("Location: viewrequest.php");
            exit;
        } else {
            echo "Failed to save travel request.";
        }
    }
}
ob_start();
?>

<link rel="stylesheet" href="../css/addrequest.css">
</head>
<body>

<!-- back button -->
<!-- <a href="home.php" class="back-btn">&lt; Back</a> -->

<!-- form container -->
<div class="form-container">
  <form method="POST" style="display:flex; width:100%; gap:30px;">
    
    <!-- left side -->
    <div class="left-section">
      <h1>New Travel Request</h1>

      <label>Subject <span>*</span></label>
      <input type="text" name="subject" required value="<?= htmlspecialchars($request['subject']) ?>">
      <div class="error"><?= $errors["subject"] ?></div>

      <label>Destination <span>*</span></label>
      <input type="text" name="destination" required value="<?= htmlspecialchars($request['destination']) ?>">
      <div class="error"><?= $errors["destination"] ?></div>

      <label>Purpose <span>*</span></label>
      <textarea name="purpose" required><?= htmlspecialchars($request['purpose']) ?></textarea>
      <div class="error"><?= $errors["purpose"] ?></div>

      <label>Start Date <span>*</span></label>
      <input type="date" name="start_date" id="start_date" required 
             value="<?= htmlspecialchars($request['start_date']) ?>" min="<?= date('Y-m-d'); ?>">
      <div class="error"><?= $errors["start_date"] ?></div>

      <label>End Date <span>*</span></label>
      <input type="date" name="end_date" id="end_date" required 
             value="<?= htmlspecialchars($request['end_date']) ?>">
      <div class="error"><?= $errors["end_date"] ?></div>
    </div>

    <!-- right side -->
    <div class="right-section">
      <h1 style="visibility:hidden;">Expenses</h1>
      <label>Breakdown of Expenses <span>*</span></label>
      <div id="expense-list">
        <div class="expense-row">
          <input type="text" name="expense_type[]" placeholder="Expense Type (e.g. Hotel)" required>
          <input type="number" name="amount[]" class="amount" placeholder="Amount" step="0.01" required>
        </div>
      </div>
      <div class="error"><?= $errors["expenses"] ?></div>

      <button type="button" class="add-btn" id="add-expense">+ Add Expense</button>

      <div class="total">
        Total Cost: ₱<span id="total-cost">0.00</span>
      </div>

      <input type="submit" value="Submit Request">
    </div>
  </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const expenseList = document.getElementById("expense-list");
  const addBtn = document.getElementById("add-expense");
  const totalDisplay = document.getElementById("total-cost");
  const startDate = document.getElementById("start_date");
  const endDate = document.getElementById("end_date");

  // JS validation for end date
  startDate.addEventListener("change", () => {
    endDate.min = startDate.value;
    if (endDate.value && endDate.value < startDate.value) {
      endDate.value = "";
    }
  });

  function updateTotal() {
    let total = 0;
    document.querySelectorAll(".amount").forEach(input => {
      total += parseFloat(input.value) || 0;
    });
    totalDisplay.textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2 });
  }

  addBtn.addEventListener("click", () => {
    const div = document.createElement("div");
    div.className = "expense-row";
    div.innerHTML = `
      <input type="text" name="expense_type[]" placeholder="Expense Type (e.g. Transport)" required>
      <input type="number" name="amount[]" class="amount" placeholder="Amount" step="0.01" required>
    `;
    expenseList.appendChild(div);
  });

  expenseList.addEventListener("input", e => {
    if (e.target.classList.contains("amount")) updateTotal();
  });
});
</script>

</body>


<?php
$content = ob_get_clean(); // capture all output
renderLayout("Add Travel Request", $content); // pass it into the layout
?>