<?php
require_once "database.php";

class Travel {
    public $request_id = "";
    public $user_id = "";
    public $subject = "";
    public $purpose = "";
    public $destination = "";
    public $start_date = "";
    public $end_date = "";
    public $total_cost = "";
    public $status = "Pending";

    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    // GET DATABASE CONNECTION
    public function getConnection() {
        return $this->db->connect();
    }

    // ADD NEW REQUEST
    public function addRequest($expenses = []) {
        $conn = $this->db->connect();

        // CHECK REQUIRED FIELDS
        if (
            empty($this->subject) ||
            empty($this->destination) ||
            empty($this->purpose) ||
            empty($this->start_date) ||
            empty($this->end_date)
        ) {
            throw new Exception("All fields are required.");
        }

        // VALIDATE DATES
        $today = date('Y-m-d');
        if (strtotime($this->start_date) < strtotime($today)) {
            throw new Exception("Start date cannot be in the past.");
        }
        if (strtotime($this->end_date) < strtotime($this->start_date)) {
            throw new Exception("End date cannot be before start date.");
        }

        // INSERT REQUEST
        $sql = "INSERT INTO travel_request 
                (user_id, subject, destination, purpose, start_date, end_date, total_cost, status, created_at)
                VALUES 
                (:user_id, :subject, :destination, :purpose, :start_date, :end_date, :total_cost, :status, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":subject", $this->subject);
        $stmt->bindParam(":destination", $this->destination);
        $stmt->bindParam(":purpose", $this->purpose);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":total_cost", $this->total_cost);
        $stmt->bindParam(":status", $this->status);

        if ($stmt->execute()) {
            $request_id = $conn->lastInsertId();

            // ADD EXPENSES IF ANY
            if (!empty($expenses)) {
                foreach ($expenses as $exp) {
                    $type = $exp["expense_type"] ?? null;
                    $amount = $exp["amount"] ?? 0;
                    if ($type && $amount > 0) {
                        $this->addExpense($conn, $request_id, $type, $amount);
                    }
                }
            }
            return true;
        }
        return false;
    }

    // ADD EXPENSE RECORD
    public function addExpense($conn, $request_id, $expense_type, $amount) {
        $sql = "INSERT INTO travel_expense (request_id, expense_type, amount)
                VALUES (:request_id, :expense_type, :amount)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
        $stmt->bindParam(":expense_type", $expense_type);
        $stmt->bindParam(":amount", $amount);
        return $stmt->execute();
    }

    // GET SINGLE REQUEST
    public function getRequestById($id) {
    $conn = $this->db->connect();
    $sql = "SELECT tr.*, u.username, u.department
            FROM travel_request tr
            JOIN users u ON tr.user_id = u.id
            WHERE tr.request_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        $request['expenses'] = $this->getExpenses($id);
    }
    return $request;
}


    // GET EXPENSES BY REQUEST ID
    public function getExpenses($request_id) {
        $conn = $this->db->connect();
        $sql = "SELECT * FROM travel_expense WHERE request_id = :request_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ADMIN VIEW ALL REQUESTS
    public function viewAllRequests() {
        $conn = $this->db->connect();
        $sql = "SELECT tr.*, u.first_name, u.last_name
                FROM travel_request tr
                JOIN users u ON tr.user_id = u.id
                ORDER BY 
                    CASE tr.status 
                        WHEN 'Pending' THEN 1
                        WHEN 'Approved' THEN 2
                        WHEN 'Rejected' THEN 3
                    END,
                    tr.request_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ADMIN FILTER BY STATUS
    public function getAllRequestsByStatus($status) {
        $conn = $this->db->connect();
        $sql = "SELECT tr.*, u.username 
                FROM travel_request tr 
                JOIN users u ON tr.user_id = u.id 
                WHERE tr.status = :status 
                ORDER BY tr.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":status", $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // APPROVE REQUEST
    public function approveRequest($request_id, $approved_budget = null, $remarks = null) {
        $conn = $this->db->connect();
        $sql = "UPDATE travel_request 
                SET status = 'Approved', approved_budget = :approved_budget, remarks = :remarks 
                WHERE request_id = :request_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":approved_budget", $approved_budget ?: null, $approved_budget ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(":remarks", $remarks ?: null, $remarks ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // REJECT REQUEST
    public function rejectRequest($request_id, $remarks = null) {
        $conn = $this->db->connect();
        $sql = "UPDATE travel_request 
                SET status = 'Rejected', remarks = :remarks 
                WHERE request_id = :request_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":remarks", $remarks ?: null, $remarks ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // UPDATE REQUEST (USER CAN EDIT IF STILL PENDING)
    public function updateRequest($request_id, $data = []) {
    $conn = $this->db->connect();
    $conn->beginTransaction();

    try {
        $subject = $data['subject'] ?? '';
        $destination = $data['destination'] ?? '';
        $purpose = $data['purpose'] ?? '';
        $start_date = $data['start_date'] ?? '';
        $end_date = $data['end_date'] ?? '';
        $total_cost = $data['total_cost'] ?? 0;
        $expenses = $data['expenses'] ?? [];

        // Update main travel request
        $sql = "UPDATE travel_request 
                SET subject = :subject, 
                    destination = :destination, 
                    purpose = :purpose, 
                    start_date = :start_date, 
                    end_date = :end_date, 
                    total_cost = :total_cost
                WHERE request_id = :request_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":subject", $subject);
        $stmt->bindParam(":destination", $destination);
        $stmt->bindParam(":purpose", $purpose);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":total_cost", $total_cost);
        $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
        $stmt->execute();

        // Delete old expenses
        $del = $conn->prepare("DELETE FROM travel_expense WHERE request_id = :request_id");
        $del->bindParam(":request_id", $request_id, PDO::PARAM_INT);
        $del->execute();

        // Insert new expenses
        if (!empty($expenses) && is_array($expenses)) {
            $ins = $conn->prepare("INSERT INTO travel_expense (request_id, expense_type, amount)
                                   VALUES (:request_id, :expense_type, :amount)");
            foreach ($expenses as $exp) {
                if (is_array($exp) && isset($exp['expense_type'], $exp['amount'])) {
                    $ins->bindParam(":request_id", $request_id, PDO::PARAM_INT);
                    $ins->bindParam(":expense_type", $exp['expense_type']);
                    $ins->bindParam(":amount", $exp['amount']);
                    $ins->execute();
                }
            }
        }

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Failed to update travel request: " . $e->getMessage());
        return false;
    }
}



    // DELETE PENDING REQUEST
    public function deleteRequest($request_id) {
        $conn = $this->db->connect();
        $sql = "DELETE FROM travel_request WHERE request_id = :request_id AND status = 'Pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // GET USER REQUESTS
    public function getUserRequests($user_id) {
        $conn = $this->db->connect();
        $sql = "SELECT * FROM travel_request WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($requests as &$r) {
            $r["expenses"] = $this->getExpenses($r["request_id"]);
        }
        return $requests;
    }

    // GET ALL REQUEST IDS
    public function getAllRequestIds() {
        $conn = $this->db->connect();
        $sql = "SELECT request_id FROM travel_request ORDER BY request_id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // GET REQUEST IDS BY STATUS
    public function getAllRequestIdsByStatus($status) {
        $conn = $this->db->connect();
        $sql = "SELECT request_id FROM travel_request WHERE LOWER(status) = :status ORDER BY request_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":status", $status, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getExpensesByRequestId($request_id) {
    try {
        $conn = $this->getConnection();
        $sql = "SELECT * FROM travel_expense WHERE request_id = :request_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching expenses by request ID: " . $e->getMessage());
        return [];
    }
}
   public function getRequestsByStatus($user_id, $status) {
    $conn = $this->db->connect();
    $sql = "SELECT * FROM travel_request 
            WHERE user_id = :user_id 
            AND LOWER(status) = LOWER(:status)
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":status", $status, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



}
?>
