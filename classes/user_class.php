<?php
require_once '../settings/db_class.php';

class Customer extends Database
{
    protected $conn;

    public function __construct()
    {
        parent::__construct();

        if (isset($this->conn) && $this->conn instanceof mysqli) {
            $this->conn = $this->conn; 
        } else {
            if (method_exists($this, 'getConnection')) {
                $this->conn = $this->getConnection();
            } else {
                global $conn;
                if (isset($conn) && $conn instanceof mysqli) {
                    $this->conn = $conn;
                } else {
                    throw new Exception('Database connection not available. Update customer_class.php to match your DB wrapper.');
                }
            }
        }
    }
        public function emailExists($email)
    {
        $sql = 'SELECT customer_id FROM customer WHERE customer_email = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('emailExists prepare failed: ' . $this->conn->error);
            return false;
        }
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            error_log('emailExists execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $res = $stmt->get_result();
        $exists = $res->num_rows > 0;
        $stmt->close();
        return $exists;
    }


    public function addCustomer($name, $email, $hashedPassword, $country, $city, $contact, $role = 1, $image = null)
    {
        if ($this->emailExists($email)) {
            return false;
        }

        $sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, customer_image, user_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('addCustomer prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('sssssssi', $name, $email, $hashedPassword, $country, $city, $contact, $image, $role);

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return (int)$insertId;
        } else {
            error_log('addCustomer execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    public function loginCustomer($email, $password)
    {
    // Sanity check
    if (empty($email) || empty($password)) {
        return false;
    }

    // Fetch the customer row by email
    $sql = "SELECT customer_id, customer_name, customer_email, customer_pass, 
                   customer_country, customer_city, customer_contact, 
                   customer_image, user_role
            FROM customer 
            WHERE customer_email = ?";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        error_log('loginCustomer prepare failed: ' . $this->conn->error);
        return false;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // No such email
        return false;
    }

    // Verify password against the stored hash
    if (!password_verify($password, $user['customer_pass'])) {
        return false;
    }

    // Optional: set session variables
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id']    = $user['customer_id'];
    $_SESSION['user_email'] = $user['customer_email'];
    $_SESSION['user_name']  = $user['customer_name'];
    $_SESSION['user_role']  = $user['user_role'];
    $_SESSION['user_image'] = $user['customer_image'];

    return $user; // return user data on success
}


    public function editCustomer($id, array $fieldsArray)
    {
        if (empty($fieldsArray)) return false;

        $allowed = ['customer_name', 'customer_email', 'customer_pass', 'customer_country', 'customer_city', 'customer_contact', 'customer_image', 'user_role'];
        $sets = [];
        $types = '';
        $values = [];

        foreach ($fieldsArray as $k => $v) {
            if (!in_array($k, $allowed)) continue;
            $sets[] = "$k = ?";
            $values[] = $v;
            if (in_array($k, ['user_role'])) $types .= 'i';
            else $types .= 's';
        }

        if (empty($sets)) return false;

        $sql = 'UPDATE customer SET ' . implode(', ', $sets) . ' WHERE customer_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('editCustomer prepare failed: ' . $this->conn->error);
            return false;
        }

        $types .= 'i';
        $values[] = (int)$id;

        $bind_names[] = $types;
        for ($i = 0; $i < count($values); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $values[$i];
            $bind_names[] = &$$bind_name;
        }

        call_user_func_array([$stmt, 'bind_param'], $bind_names);

        $res = $stmt->execute();
        if (!$res) error_log('editCustomer execute failed: ' . $stmt->error);
        $stmt->close();
        return (bool)$res;
    }

    public function deleteCustomer($id)
    {
        $sql = 'DELETE FROM customer WHERE customer_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('deleteCustomer prepare failed: ' . $this->conn->error);
            return false;
        }
        $stmt->bind_param('i', $id);
        $res = $stmt->execute();
        if (!$res) error_log('deleteCustomer execute failed: ' . $stmt->error);
        $stmt->close();
        return (bool)$res;
    }


    public function getCustomerByEmail($email)
    {
        $sql = 'SELECT * FROM customer WHERE customer_email = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('getCustomerByEmail prepare failed: ' . $this->conn->error);
            return null;
        }
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            error_log('getCustomerByEmail execute failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getCustomerById($id)
    {
        $sql = 'SELECT * FROM customer WHERE customer_id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('getCustomerById prepare failed: ' . $this->conn->error);
            return null;
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            error_log('getCustomerById execute failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function listCustomers($limit = 100, $offset = 0)
    {
        $sql = 'SELECT * FROM customer ORDER BY customer_id DESC LIMIT ? OFFSET ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('listCustomers prepare failed: ' . $this->conn->error);
            return [];
        }
        $stmt->bind_param('ii', $limit, $offset);
        if (!$stmt->execute()) {
            error_log('listCustomers execute failed: ' . $stmt->error);
            $stmt->close();
            return [];
        }
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
        return $rows;
    }


    public function authenticateCustomer($email, $password)
    {
    // Sanity
    if (empty($email) || empty($password)) {
        return false;
    }

    // Use existing helper to fetch the full customer row
    $row = $this->getCustomerByEmail($email);
    if (empty($row)) {
        // no such user
        return false;
    }

    // Expect DB column name customer_pass (per your add/edit methods)
    $stored = isset($row['customer_pass']) ? $row['customer_pass'] : null;
    if (empty($stored)) {
        // no password stored for this user - treat as auth failure
        error_log("authenticateCustomer: no password stored for email {$email}");
        return false;
    }

    // First try password_verify() - secure hashed passwords
    // if (password_verify($password, $stored)) {
    //     // Optional: if you want to rehash weaker hashes to current algo, do it here
    //     if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
    //         try {
    //             $newHash = password_hash($password, PASSWORD_DEFAULT);
    //             // Update DB safely (only update password column)
    //             $updateSql = 'UPDATE customer SET customer_pass = ? WHERE customer_id = ?';
    //             $stmt = $this->conn->prepare($updateSql);
    //             if ($stmt) {
    //                 $stmt->bind_param('si', $newHash, $row['customer_id']);
    //                 $stmt->execute();
    //                 $stmt->close();
    //             }
    //         } catch (Throwable $e) {
    //             // Non-fatal: log and continue
    //             error_log('authenticateCustomer: failed to rehash password: ' . $e->getMessage());
    //         }
    //     }

    //     // Remove password from returned row for safety
    //     unset($row['customer_pass']);
    //     return $row;
    // }

    // Fallback: some installations might have legacy plaintext passwords.
    // We try a direct comparison *only if* password_verify failed. If you do
    // not have plaintext passwords in DB, remove this block.
    if (hash_equals($stored, $password)) {
        // Immediately upgrade to hashed password in DB for security
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateSql = 'UPDATE customer SET customer_pass = ? WHERE customer_id = ?';
            $stmt = $this->conn->prepare($updateSql);
            if ($stmt) {
                $stmt->bind_param('si', $newHash, $row['customer_id']);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('authenticateCustomer: failed to upgrade plaintext password: ' . $e->getMessage());
        }

        unset($row['customer_pass']);
        return $row;
    }

    // Not verified
    return false;
    }
}

