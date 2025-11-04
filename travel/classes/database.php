<?php

class Database{

    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "travel_request_approval";

    protected $conn;

    public function connect(){
        $this->conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username,
        $this->password);

        return $this->conn;
    }

}