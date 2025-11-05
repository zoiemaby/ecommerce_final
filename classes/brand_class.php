<?php
/**
 * brand_class.php
 * A Brand management class modeled after your category_class.php style.
 *
 * Assumptions (adjust if your DB schema differs):
 *  - Table: `brands`
 *      - brand_id   INT AUTO_INCREMENT PRIMARY KEY
 *      - brand_name VARCHAR(255) NOT NULL
 *      - cat_id     INT NOT NULL  -- FK -> categories.cat_id
 *      - created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 *      - updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
 *
 *  - Table: `categories`
 *      - cat_id     INT AUTO_INCREMENT PRIMARY KEY
 *      - cat_name   VARCHAR(255) UNIQUE
 *
 * What this class covers (mapped to your frontend actions in brand.php):
 *  - Create brand (form: #brand_name + #brand_cat)                 => createBrand()
 *  - Edit brand (pencil icon)                                      => updateBrand()
 *  - Delete brand (trash icon)                                     => deleteBrand()
 *  - List brands (for the grid)                                    => listBrands(), listBrandsByCategory()
 *  - List brands grouped by category (for grouped sections)        => listBrandsGroupedByCategory()
 *  - Counts (e.g., “2 brands” per category header)                 => countByCategory()
 *  - Lookups/validation helpers                                    => getBrandById(), nameExists()
 */

require_once '../settings/db_class.php';

class Brand extends Database
{
    // --- new properties for detected names ---
    protected string $tableBrands = 'brands';
    protected string $tableCategories = 'categories';
    protected array $colsBrands = [];
    protected array $colsCategories = [];
    protected string $colBrandId = 'brand_id';
    protected string $colBrandName = 'brand_name';
    protected string $colBrandCatId = 'cat_id';
    protected string $colCatId = 'cat_id';
    protected string $colCatName = 'cat_name';

    public function __construct()
    {
        parent::__construct();
        $this->detectColumns(); // populate mapping based on DB
    }

    // helper to backtick identifiers safely
    private function id(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    // discover sensible column names for brands and categories
    private function detectColumns(): void
    {
        // brands
        $this->colsBrands = [];
        $res = $this->conn->query("DESCRIBE {$this->tableBrands}");
        if ($res) {
            while ($r = $res->fetch_assoc()) $this->colsBrands[] = $r['Field'];
            $res->close();
        } else {
            error_log("[Brand::detectColumns] DESCRIBE {$this->tableBrands} failed: " . $this->conn->error);
        }

        // categories
        $this->colsCategories = [];
        $rc = $this->conn->query("DESCRIBE {$this->tableCategories}");
        if ($rc) {
            while ($r = $rc->fetch_assoc()) $this->colsCategories[] = $r['Field'];
            $rc->close();
        } else {
            error_log("[Brand::detectColumns] DESCRIBE {$this->tableCategories} failed: " . $this->conn->error);
        }

        // heuristics for brands
        $this->colBrandId   = in_array('brand_id', $this->colsBrands) ? 'brand_id' : (in_array('id', $this->colsBrands) ? 'id' : ($this->colsBrands[0] ?? 'brand_id'));
        $this->colBrandName = in_array('brand_name', $this->colsBrands) ? 'brand_name' : (in_array('name', $this->colsBrands) ? 'name' : ($this->colsBrands[1] ?? 'brand_name'));
        $this->colBrandCatId = in_array('cat_id', $this->colsBrands) ? 'cat_id' : (in_array('category_id', $this->colsBrands) ? 'category_id' : (in_array('category', $this->colsBrands) ? 'category' : 'cat_id'));

        // heuristics for categories
        $this->colCatId   = in_array('cat_id', $this->colsCategories) ? 'cat_id' : (in_array('id', $this->colsCategories) ? 'id' : ($this->colsCategories[0] ?? 'cat_id'));
        $this->colCatName = in_array('cat_name', $this->colsCategories) ? 'cat_name' : (in_array('name', $this->colsCategories) ? 'name' : ($this->colsCategories[1] ?? 'cat_name'));
    }

    /**
     * Helper to bind parameters to mysqli_stmt using references.
     * mysqli_stmt::bind_param requires references; using call_user_func_array
     * with referenced params avoids the common pitfall.
     *
     * @param mysqli_stmt $stmt
     * @param string $types
     * @param array $params
     * @return bool
     */
    private function bindParams($stmt, string $types, array $params): bool
    {
        if ($types === '') return true;
        // build array of references
        $refs = [];
        foreach ($params as $i => $v) {
            $refs[$i] = &$params[$i];
        }
        return (bool) call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
    }

    /**
     * Check if a brand name already exists (optionally scoped to a category),
     * with optional exclusion of a given brand_id (for updates).
     *
     * @param string      $name
     * @param int|null    $catId      If provided, checks uniqueness within this category.
     * @param int|null    $excludeId  brand_id to exclude (when updating)
     * @return bool                    true if name exists (conflict), false otherwise
     */
    public function nameExists(string $name, ?int $catId = null, ?int $excludeId = null): bool
    {
        // build SQL using detected identifiers
        $bName = $this->id($this->colBrandName);
        $bTable = $this->id($this->tableBrands);
        $bCatId = $this->id($this->colBrandCatId);
        $bId = $this->id($this->colBrandId);

        $sql = "SELECT {$bId} FROM {$bTable} WHERE LOWER({$bName}) = LOWER(?)";
        $types = 's';
        $params = [$name];

        if ($catId !== null) {
            $sql   .= " AND {$bCatId} = ?";
            $types .= 'i';
            $params[] = $catId;
        }
        if ($excludeId !== null) {
            $sql   .= " AND {$bId} != ?";
            $types .= 'i';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        error_log("[Brand::nameExists] SQL: $sql | types=$types | params=" . json_encode($params));

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('[Brand::nameExists] prepare failed: ' . $this->conn->error);
            return false;
        }
        if (!$this->bindParams($stmt, $types, $params)) {
            error_log('[Brand::nameExists] bind_param failed for params: ' . json_encode($params));
            $stmt->close();
            return false;
        }
        if (!$stmt->execute()) {
            error_log('[Brand::nameExists] execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $res = $stmt->get_result();
        $exists = $res->num_rows > 0;
        $stmt->close();
        error_log('[Brand::nameExists] exists=' . ($exists ? '1' : '0'));
        return $exists;
    }

    /**
     * Create a new brand.
     *
     * @param string $name
     * @param int    $catId
     * @return array [success=>bool, id=>int|null, message=>string]
     */
    public function createBrand(string $name, ?int $catId = null): array
    {
        $catId = ($catId !== null && $catId > 0) ? $catId : null;

        if ($this->nameExists($name, $catId, null)) {
            return ['success' => false, 'id' => null, 'message' => 'Brand already exists.'];
        }

        if ($catId !== null) {
            $catTable = $this->id($this->tableCategories);
            $catIdCol = $this->id($this->colCatId);

            $catStmt = $this->conn->prepare("SELECT {$catIdCol} FROM {$catTable} WHERE {$catIdCol} = ? LIMIT 1");
            if (!$catStmt) {
                error_log('[Brand::createBrand] category check prepare failed: ' . $this->conn->error);
                return ['success' => false, 'id' => null, 'message' => 'DB error (category check).'];
            }
            $catStmt->bind_param('i', $catId);
            if (!$catStmt->execute()) {
                error_log('[Brand::createBrand] category check execute failed: ' . $catStmt->error);
                $catStmt->close();
                return ['success' => false, 'id' => null, 'message' => 'Category check failed.'];
            }
            $catRes = $catStmt->get_result();
            $catExists = $catRes->num_rows > 0;
            $catStmt->close();
            if (!$catExists) {
                return ['success' => false, 'id' => null, 'message' => 'Selected category does not exist.'];
            }
        }

        $bTable = $this->id($this->tableBrands);
        $bName = $this->id($this->colBrandName);
        $bCatId = $this->id($this->colBrandCatId);

        if ($catId !== null && in_array($this->colBrandCatId, $this->colsBrands, true)) {
            $sql = "INSERT INTO {$bTable} ({$bName}, {$bCatId}) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $err = $this->conn->error;
                error_log('[Brand::createBrand] insert prepare failed: ' . $err . ' | SQL: ' . $sql);
                return ['success' => false, 'id' => null, 'message' => 'DB prepare failed: ' . $err];
            }
            if (!$stmt->bind_param('si', $name, $catId)) {
                $e = $stmt->error;
                $stmt->close();
                error_log('[Brand::createBrand] bind_param failed: ' . $e);
                return ['success' => false, 'id' => null, 'message' => 'DB bind failed: ' . $e];
            }
        } else {
            $sql = "INSERT INTO {$bTable} ({$bName}) VALUES (?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $err = $this->conn->error;
                error_log('[Brand::createBrand] insert prepare failed: ' . $err . ' | SQL: ' . $sql);
                return ['success' => false, 'id' => null, 'message' => 'DB prepare failed: ' . $err];
            }
            if (!$stmt->bind_param('s', $name)) {
                $e = $stmt->error;
                $stmt->close();
                error_log('[Brand::createBrand] bind_param failed: ' . $e);
                return ['success' => false, 'id' => null, 'message' => 'DB bind failed: ' . $e];
            }
        }

        if (!$stmt->execute()) {
            $msg = 'Create failed: ' . $stmt->error;
            $stmt->close();
            return ['success' => false, 'id' => null, 'message' => $msg];
        }

        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $newId, 'message' => 'Brand created successfully.'];
    }

    /**
     * Update an existing brand (name and/or category).
     *
     * @param int    $brandId
     * @param string $name
     * @param int    $catId
     * @return array [success=>bool, message=>string]
     */
    public function updateBrand(int $brandId, string $name, ?int $catId = null): array
    {
        $existing = $this->getBrandRow($brandId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Brand not found.'];
        }

        $catId = ($catId !== null && $catId > 0) ? $catId : null;
        if ($catId === null && array_key_exists($this->colBrandCatId, $existing)) {
            $catVal = $existing[$this->colBrandCatId];
            $catId = ($catVal !== null && (int) $catVal > 0) ? (int) $catVal : null;
        }

        if ($catId !== null) {
            $catTable = $this->id($this->tableCategories);
            $catIdCol = $this->id($this->colCatId);
            $catStmt = $this->conn->prepare("SELECT {$catIdCol} FROM {$catTable} WHERE {$catIdCol} = ? LIMIT 1");
            if (!$catStmt) {
                return ['success' => false, 'message' => 'DB error (category check).'];
            }
            $catStmt->bind_param('i', $catId);
            if (!$catStmt->execute()) {
                $catStmt->close();
                return ['success' => false, 'message' => 'Category check failed.'];
            }
            $catExists = $catStmt->get_result()->num_rows > 0;
            $catStmt->close();
            if (!$catExists) {
                return ['success' => false, 'message' => 'Selected category does not exist.'];
            }
        }

        if ($this->nameExists($name, $catId, $brandId)) {
            return ['success' => false, 'message' => 'Another brand with this name already exists.'];
        }

        $bTable = $this->id($this->tableBrands);
        $bName = $this->id($this->colBrandName);
        $bCatId = $this->id($this->colBrandCatId);
        $bId = $this->id($this->colBrandId);

        if ($catId !== null && in_array($this->colBrandCatId, $this->colsBrands, true)) {
            $stmt = $this->conn->prepare("UPDATE {$bTable} SET {$bName} = ?, {$bCatId} = ? WHERE {$bId} = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'DB prepare failed.'];
            }
            $stmt->bind_param('sii', $name, $catId, $brandId);
        } else {
            $stmt = $this->conn->prepare("UPDATE {$bTable} SET {$bName} = ? WHERE {$bId} = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'DB prepare failed.'];
            }
            $stmt->bind_param('si', $name, $brandId);
        }

        if (!$stmt->execute()) {
            $msg = 'Update failed: ' . $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => $msg];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Brand updated successfully.'];
    }

    private function getBrandRow(int $brandId): ?array
    {
        $bTable = $this->id($this->tableBrands);
        $bId = $this->id($this->colBrandId);

        $stmt = $this->conn->prepare("SELECT * FROM {$bTable} WHERE {$bId} = ? LIMIT 1");
        if (!$stmt) {
            error_log('[Brand::getBrandRow] prepare failed: ' . $this->conn->error);
            return null;
        }
        $stmt->bind_param('i', $brandId);
        if (!$stmt->execute()) {
            error_log('[Brand::getBrandRow] execute failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    /**
     * Delete a brand by id.
     *
     * @param int $brandId
     * @return array [success=>bool, message=>string]
     */
    public function deleteBrand(int $brandId): array
    {
        $bTable = $this->id($this->tableBrands);
        $bId = $this->id($this->colBrandId);
        $stmt = $this->conn->prepare("DELETE FROM {$bTable} WHERE {$bId} = ?");
        if (!$stmt) return ['success' => false, 'message' => 'DB prepare failed.'];
        $stmt->bind_param('i', $brandId);
        if (!$stmt->execute()) { $msg = 'Delete failed: ' . $stmt->error; $stmt->close(); return ['success' => false, 'message' => $msg]; }
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected < 1) return ['success' => false, 'message' => 'Brand not found or already deleted.'];
        return ['success' => true, 'message' => 'Brand deleted successfully.'];
    }

    /**
     * Get a brand by id.
     *
     * @param int $brandId
     * @return array|null
     */
    public function getBrandById(int $brandId): ?array
    {
        $table = $this->id($this->tableBrands);
        $colId = $this->id($this->colBrandId);

        $sql = "SELECT * FROM {$table} WHERE {$colId} = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('[Brand::getBrandById] prepare failed: ' . $this->conn->error);
            return null;
        }
        $stmt->bind_param('i', $brandId);
        if (!$stmt->execute()) {
            error_log('[Brand::getBrandById] execute failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    public function getBrandByName(string $name, ?int $catId = null): ?array
    {
        $table = $this->id($this->tableBrands);
        $colName = $this->id($this->colBrandName);

        $sql = "SELECT * FROM {$table} WHERE LOWER({$colName}) = LOWER(?) LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('s', $name);
        if (!$stmt->execute()) {
            error_log('[Brand::getBrandByName] execute failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    /**
     * List brands with optional filters and sorting (for the grid).
     *
     * @param int|null    $catId       Only brands in this category
     * @param string|null $search      Case-insensitive search on brand_name
     * @param string      $orderBy     One of: name_asc, name_desc, created_desc, created_asc
     * @return array
     */
    public function listBrands(?int $catId = null, ?string $search = null, string $orderBy = 'name_asc'): array
    {
        $bTable = $this->id($this->tableBrands);
        $cTable = $this->id($this->tableCategories);
        $bName  = $this->id($this->colBrandName);
        $catIdCol = $this->id($this->colBrandCatId);

        $hasCatColumn = is_array($this->colsBrands ?? null)
            && in_array($this->colBrandCatId, $this->colsBrands, true);

        $where  = [];
        $types  = '';
        $params = [];

        if ($catId !== null && $hasCatColumn) {
            $where[] = "b.{$catIdCol} = ?";
            $types  .= 'i';
            $params[] = $catId;
        }

        if ($search !== null && $search !== '') {
            $where[] = "LOWER(b.{$bName}) LIKE LOWER(?)";
            $types  .= 's';
            $params[] = '%' . $search . '%';
        }

        $orderSql = match ($orderBy) {
            'name_desc'    => "ORDER BY b.{$bName} DESC",
            'created_desc' => 'ORDER BY b.created_at DESC',
            'created_asc'  => 'ORDER BY b.created_at ASC',
            default        => "ORDER BY b.{$bName} ASC",
        };

        if ($hasCatColumn && $this->tableCategories) {
            $sql = "SELECT b.*, c.{$this->id($this->colCatName)} AS cat_name
                    FROM {$bTable} b
                    LEFT JOIN {$cTable} c ON c.{$this->id($this->colCatId)} = b.{$catIdCol}";
        } else {
            $sql = "SELECT b.* FROM {$bTable} b";
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ' . $orderSql;

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('listBrands prepare failed: ' . $this->conn->error . " | SQL: {$sql}");
            return [];
        }

        if ($types !== '') {
            if (!$this->bindParams($stmt, $types, $params)) {
                error_log('listBrands bind_param failed');
                $stmt->close();
                return [];
            }
        }

        if (!$stmt->execute()) {
            error_log('listBrands execute failed: ' . $stmt->error);
            $stmt->close();
            return [];
        }

        $res  = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * List brands by specific category id.
     *
     * @param int $catId
     * @return array
     */
    public function listBrandsByCategory(int $catId): array
    {
        return $this->listBrands($catId, null, 'name_asc');
    }

    /**
     * Return brands grouped by category.
     * Shape:
     * [
     *   ['cat_id'=>1,'cat_name'=>'Footwear','brands'=> [ {...}, {...} ], 'count'=>2],
     *   ['cat_id'=>2,'cat_name'=>'Apparel','brands'=> [ ... ], 'count'=>N],
     * ]
     *
     * @return array
     */
    public function listBrandsGroupedByCategory(): array
    {
        // Use detected columns to query categories and brands
        $catTable = $this->id($this->tableCategories);
        $bTable   = $this->id($this->tableBrands);
        $catIdCol = $this->id($this->colCatId);
        $catNameCol = $this->id($this->colCatName);

        $cats = [];
        if ($rsCats = $this->conn->query("SELECT {$catIdCol}, {$catNameCol} FROM {$catTable} ORDER BY {$catNameCol} ASC")) {
            while ($c = $rsCats->fetch_assoc()) $cats[] = $c;
            $rsCats->close();
        } else {
            error_log('listBrandsGroupedByCategory categories query failed: ' . $this->conn->error);
        }

        $allBrands = [];
        $bCatId = $this->id($this->colBrandCatId);
        $bName = $this->id($this->colBrandName);
        $catNameColSel = $this->id($this->colCatName);

        $bSql = "SELECT b.*, c.{$catNameColSel} AS cat_name
                 FROM {$bTable} b
                 JOIN {$catTable} c ON c.{$catIdCol} = b.{$bCatId}
                 ORDER BY c.{$catNameColSel} ASC, b.{$bName} ASC";
        if ($rsB = $this->conn->query($bSql)) {
            while ($b = $rsB->fetch_assoc()) $allBrands[] = $b;
            $rsB->close();
        } else {
            error_log('listBrandsGroupedByCategory brands query failed: ' . $this->conn->error);
        }

        $groups = [];
        foreach ($cats as $c) {
            $bucket = array_values(array_filter($allBrands, fn($b) => intval($b[$this->colBrandCatId]) === intval($c[$this->colCatId])));
            $groups[] = [
                'cat_id'   => intval($c[$this->colCatId]),
                'cat_name' => $c[$this->colCatName],
                'brands'   => $bucket,
                'count'    => count($bucket),
            ];
        }
        return $groups;
    }

    /**
     * Count brands in a category.
     *
     * @param int $catId
     * @return int
     */
    public function countByCategory(int $catId): int
    {
        $bTable = $this->id($this->tableBrands);
        $bCatId = $this->id($this->colBrandCatId);
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS n FROM {$bTable} WHERE {$bCatId} = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $catId);
        if (!$stmt->execute()) { $stmt->close(); return 0; }
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return intval($res['n'] ?? 0);
    }
}
