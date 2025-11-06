<?php
require_once __DIR__ . '/../settings/db_class.php';

class Product extends Database
{
    /** @var mysqli */
    protected $conn;

    public function __construct()
    {
        parent::__construct();
        $this->conn = $this->getConnection();
    }

    public function createProduct(array $p): array
    {
        $sql = "INSERT INTO `products`
                (product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];

        $cat   = (int)($p['product_cat'] ?? 0);
        $brand = (int)($p['product_brand'] ?? 0);
        $title = (string)($p['product_title'] ?? '');
        $price = (float)($p['product_price'] ?? 0);
        $desc  = (string)($p['product_desc'] ?? '');
        $img   = (string)($p['product_image'] ?? '');
        $kw    = (string)($p['product_keywords'] ?? '');

        $stmt->bind_param('iisdsss', $cat, $brand, $title, $price, $desc, $img, $kw);
        if (!$stmt->execute()) { $stmt->close(); return ['success'=>false,'message'=>'DB execute failed.']; }

        $id = $this->conn->insert_id;
        $stmt->close();
        return ['success'=>true,'data'=>['product_id'=>(int)$id]];
    }

    public function listProducts(int $limit = 100): array
    {
        $sql = "SELECT product_id, product_cat, product_brand, product_title,
                       product_price, product_desc, product_image, product_keywords
                FROM products
                ORDER BY product_id DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('i', $limit);
        if (!$stmt->execute()) { $stmt->close(); return ['success'=>false,'message'=>'DB execute failed.']; }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return ['success'=>true,'data'=>$rows];
    }

    // Set the main image if empty (or force replace)
    public function setMainImage(int $product_id, string $path, bool $force = false): array
    {
        if ($path === '') return ['success'=>false,'message'=>'Empty path'];
        if ($force) {
            $sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
        } else {
            $sql = "UPDATE products SET product_image = ? WHERE product_id = ? AND (product_image IS NULL OR product_image = '')";
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('si', $path, $product_id);
        $ok = $stmt->execute();
        $stmt->close();
        return ['success'=>$ok, 'message'=>$ok ? 'OK' : 'No change'];
    }

    public function addImage(int $product_id, string $path, int $sort = 0): array
    {
        // Since there's no product_images table, update the main product_image field
        $sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'DB prepare failed.'];
        }
        $stmt->bind_param('si', $path, $product_id);
        if (!$stmt->execute()) {
            $msg = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => $msg ?: 'DB execute failed.'];
        }
        $stmt->close();
        return ['success' => true, 'id' => $product_id];
    }

    public function getProductById(int $product_id): array
    {
        $sql = "SELECT product_id, product_cat, product_brand, product_title,
                       product_price, product_desc, product_image, product_keywords
                FROM products WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('i', $product_id);
        if (!$stmt->execute()) { $stmt->close(); return ['success'=>false,'message'=>'DB execute failed.']; }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return ['success'=>false,'message'=>'Product not found'];
        return ['success'=>true,'data'=>$row];
    }

    public function updateProduct(int $product_id, array $data): array
    {
        $fields = [];
        $types = '';
        $values = [];

        if (isset($data['product_title']) || isset($data['title'])) {
            $fields[] = 'product_title = ?';
            $types .= 's';
            $values[] = (string)($data['product_title'] ?? $data['title']);
        }
        if (isset($data['product_cat']) || isset($data['cat_id'])) {
            $fields[] = 'product_cat = ?';
            $types .= 'i';
            $values[] = (int)($data['product_cat'] ?? $data['cat_id']);
        }
        if (isset($data['product_brand']) || isset($data['brand_id'])) {
            $fields[] = 'product_brand = ?';
            $types .= 'i';
            $values[] = (int)($data['product_brand'] ?? $data['brand_id']);
        }
        if (isset($data['product_price']) || isset($data['price'])) {
            $fields[] = 'product_price = ?';
            $types .= 'd';
            $values[] = (float)($data['product_price'] ?? $data['price']);
        }
        if (isset($data['product_desc']) || isset($data['description'])) {
            $fields[] = 'product_desc = ?';
            $types .= 's';
            $values[] = (string)($data['product_desc'] ?? $data['description']);
        }
        if (isset($data['product_keywords']) || isset($data['keywords'])) {
            $fields[] = 'product_keywords = ?';
            $types .= 's';
            $values[] = (string)($data['product_keywords'] ?? $data['keywords']);
        }
        if (isset($data['product_image'])) {
            $fields[] = 'product_image = ?';
            $types .= 's';
            $values[] = (string)$data['product_image'];
        }

        if (empty($fields)) {
            return ['success'=>false,'message'=>'No fields to update'];
        }

        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE product_id = ?";
        $types .= 'i';
        $values[] = $product_id;

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];

        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            $msg = $stmt->error;
            $stmt->close();
            return ['success'=>false,'message'=>$msg ?: 'Update failed'];
        }
        $stmt->close();
        return ['success'=>true,'message'=>'Product updated'];
    }

    public function deleteProduct(int $product_id): array
    {
        // Get image path first to delete file
        $get = $this->getProductById($product_id);
        $imagePath = null;
        if (!empty($get['success']) && !empty($get['data']['product_image'])) {
            $imagePath = $get['data']['product_image'];
        }

        $sql = "DELETE FROM products WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('i', $product_id);
        if (!$stmt->execute()) {
            $msg = $stmt->error;
            $stmt->close();
            return ['success'=>false,'message'=>$msg ?: 'Delete failed'];
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        // Delete image files if present
        if ($imagePath && $affected > 0) {
            $fullPath = __DIR__ . '/../' . $imagePath;
            if (file_exists($fullPath)) {
                @unlink($fullPath);
                // Try to remove directory if empty
                $dir = dirname($fullPath);
                if (is_dir($dir) && count(scandir($dir)) === 2) { // only . and ..
                    @rmdir($dir);
                }
            }
        }

        return $affected > 0
            ? ['success'=>true,'message'=>'Product deleted']
            : ['success'=>false,'message'=>'Product not found'];
    }

    /**
     * View all products with optional pagination
     * @param int $limit Number of products per page
     * @param int $offset Starting position
     * @return array
     */
    public function view_all_products(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                ORDER BY p.product_id DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('ii', $limit, $offset);
        if (!$stmt->execute()) { 
            $stmt->close(); 
            return ['success'=>false,'message'=>'DB execute failed.']; 
        }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM products";
        $countResult = $this->conn->query($countSql);
        $total = $countResult ? $countResult->fetch_assoc()['total'] : 0;

        return ['success'=>true,'data'=>$rows,'total'=>(int)$total];
    }

    /**
     * Search products by query string (searches title, description, keywords)
     * @param string $query Search term
     * @param int $limit Number of results
     * @param int $offset Starting position
     * @return array
     */
    public function search_products(string $query, int $limit = 100, int $offset = 0): array
    {
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_title LIKE ? 
                   OR p.product_desc LIKE ? 
                   OR p.product_keywords LIKE ?
                ORDER BY p.product_id DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('sssii', $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
        if (!$stmt->execute()) { 
            $stmt->close(); 
            return ['success'=>false,'message'=>'DB execute failed.']; 
        }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM products p 
                     WHERE p.product_title LIKE ? 
                        OR p.product_desc LIKE ? 
                        OR p.product_keywords LIKE ?";
        $countStmt = $this->conn->prepare($countSql);
        $total = 0;
        if ($countStmt) {
            $countStmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
            if ($countStmt->execute()) {
                $total = $countStmt->get_result()->fetch_assoc()['total'];
            }
            $countStmt->close();
        }

        return ['success'=>true,'data'=>$rows,'total'=>(int)$total,'query'=>$query];
    }

    /**
     * Filter products by category
     * @param int $cat_id Category ID
     * @param int $limit Number of results
     * @param int $offset Starting position
     * @return array
     */
    public function filter_products_by_category(int $cat_id, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = ?
                ORDER BY p.product_id DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('iii', $cat_id, $limit, $offset);
        if (!$stmt->execute()) { 
            $stmt->close(); 
            return ['success'=>false,'message'=>'DB execute failed.']; 
        }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM products WHERE product_cat = ?";
        $countStmt = $this->conn->prepare($countSql);
        $total = 0;
        if ($countStmt) {
            $countStmt->bind_param('i', $cat_id);
            if ($countStmt->execute()) {
                $total = $countStmt->get_result()->fetch_assoc()['total'];
            }
            $countStmt->close();
        }

        return ['success'=>true,'data'=>$rows,'total'=>(int)$total,'category_id'=>$cat_id];
    }

    /**
     * Filter products by brand
     * @param int $brand_id Brand ID
     * @param int $limit Number of results
     * @param int $offset Starting position
     * @return array
     */
    public function filter_products_by_brand(int $brand_id, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = ?
                ORDER BY p.product_id DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('iii', $brand_id, $limit, $offset);
        if (!$stmt->execute()) { 
            $stmt->close(); 
            return ['success'=>false,'message'=>'DB execute failed.']; 
        }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM products WHERE product_brand = ?";
        $countStmt = $this->conn->prepare($countSql);
        $total = 0;
        if ($countStmt) {
            $countStmt->bind_param('i', $brand_id);
            if ($countStmt->execute()) {
                $total = $countStmt->get_result()->fetch_assoc()['total'];
            }
            $countStmt->close();
        }

        return ['success'=>true,'data'=>$rows,'total'=>(int)$total,'brand_id'=>$brand_id];
    }

    /**
     * View single product with full details
     * @param int $id Product ID
     * @return array
     */
    public function view_single_product(int $id): array
    {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title,
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success'=>false,'message'=>'DB prepare failed.'];
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) { 
            $stmt->close(); 
            return ['success'=>false,'message'=>'DB execute failed.']; 
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return ['success'=>false,'message'=>'Product not found'];
        return ['success'=>true,'data'=>$row];
    }
}