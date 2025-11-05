<?php

class Database
{
    /** @var mysqli|null */
    protected $conn = null;


    public function __construct($credPath = null)
    {
        // Load DB constants from credentials file (no __DIR__ approach per requirement)
        require_once 'db_cred.php';

        if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
            throw new Exception(
                'Database credentials not found. Make sure db_cred.php is available and defines DB_HOST, DB_USER, DB_PASS, DB_NAME.'
            );
        }

        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }
    }
// ...existing code...


    public function getConnection()
    {
        return $this->conn;
    }

   
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
