<?php
require_once "database.php";

class User {
    public $user_id;
    public $first_name;
    public $middle_name;
    public $last_name;
    public $email;
    public $username;
    public $password;
    public $role = "user"; // default role

    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    // ✅ Register a new user
    public function register() {
        $conn = $this->db->connect();

        // Validate required fields
        if (empty($this->first_name) || empty($this->last_name) || empty($this->email) || empty($this->username) || empty($this->password)) {
            throw new Exception("All required fields must be filled.");
        }

        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, email, username, password, role) 
                                    VALUES (:first_name, :middle_name, :last_name, :email, :username, :password, :role)");
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":middle_name", $this->middle_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":role", $this->role);

            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Email or username already exists.");
            }
            throw new Exception($e->getMessage());
        }
    }

    // ✅ Login using email (not username)
    public function login($email, $password) {
        $conn = $this->db->connect();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user; // login successful
        } else {
            return false; // login failed
        }
    }

    // ✅ Get user by ID
    public function getUserById($user_id) {
        $conn = $this->db->connect();
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
