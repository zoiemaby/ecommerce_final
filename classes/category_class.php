<?php
/**
 * category_class.php
 * A simple Category management class that extends your Database wrapper.
 * Assumptions (adjust if your DB schema differs):
 *  - Table name: `category`
 *  - Columns: `category_id` (INT AUTO_INCREMENT PRIMARY KEY),
 *             `category_name` (VARCHAR, UNIQUE),
 *             `created_at` (optional TIMESTAMP),
 *             `updated_at` (optional TIMESTAMP)
 *
 * Usage: require this file and instantiate: $cat = new Category();
 */

require_once '../settings/db_class.php';


class Category extends Database
{
    protected $conn;

    public function __construct()
    {
        parent::__construct();

        // Attempt to locate a mysqli connection provided by your DB wrapper
        if (method_exists($this, 'getConnection')) {
            $this->conn = $this->getConnection();
        } elseif (isset($this->conn) && $this->conn instanceof mysqli) {
            // already set by parent
        } else {
            global $conn;
            if (isset($conn) && $conn instanceof mysqli) {
                $this->conn = $conn;
            } else {
                throw new Exception('Database connection not available. Update category_class.php to match your DB wrapper.');
            }
        }
    }

    /**
     * Check if a category name already exists (case-insensitive)
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
    $sql = 'SELECT cat_id FROM categories WHERE LOWER(cat_name) = LOWER(?)';
        if ($excludeId !== null) {
            $sql .= ' AND cat_id != ? LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log('nameExists prepare failed: ' . $this->conn->error);
                return true; // treat as exists to be safe
            }
            $stmt->bind_param('si', $name, $excludeId);
        } else {
            $sql .= ' LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log('nameExists prepare failed: ' . $this->conn->error);
                return true;
            }
            $stmt->bind_param('s', $name);
        }

        if (!$stmt->execute()) {
            error_log('nameExists execute failed: ' . $stmt->error);
            $stmt->close();
            return true;
        }

        $res = $stmt->get_result();
        $exists = $res->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Add a new category. Returns insert id on success, false on failure.
     * Enforces unique category_name.
     */
    public function addCategory(string $name)
    {
        $name = trim($name);
        if ($name === '') return false;

        if ($this->nameExists($name)) {
            // name already exists
            return false;
        }

    $sql = 'INSERT INTO categories (cat_name) VALUES (?)';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('addCategory prepare failed: ' . $this->conn->error);
            return false;
        }

    $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return (int)$insertId;
        } else {
            error_log('addCategory execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Get a category by ID. Returns assoc array or null.
     */
    public function getCategoryById(int $id): ?array
    {
    $sql = 'SELECT * FROM categories WHERE cat_id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('getCategoryById prepare failed: ' . $this->conn->error);
            return null;
        }
    $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            error_log('getCategoryById execute failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Get a category by name. Returns assoc array or null.
     */
    public function getCategoryByName(string $name): ?array
    {
    $sql = 'SELECT * FROM categories WHERE LOWER(cat_name) = LOWER(?) LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('getCategoryByName prepare failed: ' . $this->conn->error);
            return null;
        }
    $stmt->bind_param('s', $name);
        if (!$stmt->execute()) {
            error_log('getCategoryByName execute failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Edit category name. Returns true on success, false on failure.
     * Ensures uniqueness of the new name (ignoring the current category id).
     */
    public function editCategory(int $id, string $newName): bool
    {
        $newName = trim($newName);
        if ($newName === '') return false;

        // Check if the name already exists for another category
        if ($this->nameExists($newName, $id)) {
            return false;
        }

    $sql = 'UPDATE categories SET cat_name = ? WHERE cat_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('editCategory prepare failed: ' . $this->conn->error);
            return false;
        }
    $stmt->bind_param('si', $newName, $id);
        $res = $stmt->execute();
        if (!$res) error_log('editCategory execute failed: ' . $stmt->error);
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Delete a category by id. Returns true on success.
     */
    public function deleteCategory(int $id): bool
    {
    $sql = 'DELETE FROM categories WHERE cat_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('deleteCategory prepare failed: ' . $this->conn->error);
            return false;
        }
    $stmt->bind_param('i', $id);
        $res = $stmt->execute();
        if (!$res) error_log('deleteCategory execute failed: ' . $stmt->error);
        $stmt->close();
        return (bool)$res;
    }

    /**
     * List categories with optional pagination. Returns array of rows.
     */
    public function listCategories(int $limit = 100, int $offset = 0): array
    {
    $sql = 'SELECT * FROM categories ORDER BY cat_name ASC LIMIT ? OFFSET ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('listCategories prepare failed: ' . $this->conn->error);
            return [];
        }
    $stmt->bind_param('ii', $limit, $offset);
        if (!$stmt->execute()) {
            error_log('listCategories execute failed: ' . $stmt->error);
            $stmt->close();
            return [];
        }
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
        return $rows;
    }
}

?>
