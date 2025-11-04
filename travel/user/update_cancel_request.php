<?php
require_once "../classes/travel.php";
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json");

$travel = new Travel();
$input = json_decode(file_get_contents("php://input"), true);

$request_id = $input["request_id"] ?? null;
$reason = trim($input["reason"] ?? "");

if (!$request_id) {
  echo json_encode(["success" => false, "message" => "Missing request ID"]);
  exit;
}

try {
  $conn = $travel->getConnection();

  //  Ensure 'Cancelled' exists in ENUM before update (optional safety check)
  $sqlCheck = "SHOW COLUMNS FROM travel_request LIKE 'status'";
  $check = $conn->query($sqlCheck)->fetch(PDO::FETCH_ASSOC);
  if (!str_contains($check['Type'], 'Cancelled')) {
    $conn->exec("
      ALTER TABLE travel_request 
      MODIFY COLUMN status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') 
      NOT NULL DEFAULT 'Pending'
    ");
  }

  // Proceed with the update
  $sql = "UPDATE travel_request 
          SET status = 'Cancelled', remarks = :reason 
          WHERE request_id = :request_id";
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(":reason", $reason);
  $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
  $stmt->execute();

  // check if the update actually affected a row
  if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => true]);
  } else {
    echo json_encode(["success" => false, "message" => "No matching record found or already cancelled"]);
  }

} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
