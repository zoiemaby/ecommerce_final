<?php
/**
 * brand_controller.php
 *
 * Lightweight controller wrappers for the Brand class,
 * aligned to the style of your sample category_controller.php.
 *
 * Each *_ctr() function instantiates Brand and forwards the call.
 */

require_once __DIR__ . '/../classes/brand_class.php';

/**
 * Create a new brand.
 * @param string   $name   Brand name
 * @param int|null $catId  Optional category ID
 * @return array [success=>bool, id=>int|null, message=>string]
 */
function add_brand_ctr(string $name, ?int $catId = null)
{
    try {
        $b = new Brand();

        $cleanCatId = ($catId !== null && $catId > 0) ? $catId : null;

        $isDuplicate = false;
        if (method_exists($b, 'nameExists')) {
            $isDuplicate = (bool) $b->nameExists($name, $cleanCatId);
        }

        if ($isDuplicate) {
            return ['success' => false, 'id' => null, 'message' => 'Brand already exists.'];
        }

        if (method_exists($b, 'createBrand')) {
            return $b->createBrand($name, $cleanCatId);
        }

        if (isset($b->conn) && $b->conn instanceof mysqli) {
            if ($cleanCatId !== null) {
                $stmt = $b->conn->prepare('INSERT INTO brands (brand_name, cat_id) VALUES (?, ?)');
                if (!$stmt) {
                    return ['success' => false, 'id' => null, 'message' => 'DB prepare failed: ' . $b->conn->error];
                }
                $stmt->bind_param('si', $name, $cleanCatId);
            } else {
                $stmt = $b->conn->prepare('INSERT INTO brands (brand_name) VALUES (?)');
                if (!$stmt) {
                    return ['success' => false, 'id' => null, 'message' => 'DB prepare failed: ' . $b->conn->error];
                }
                $stmt->bind_param('s', $name);
            }

            if ($stmt->execute()) {
                $id = $stmt->insert_id ?: ($b->conn->insert_id ?? null);
                $stmt->close();
                return ['success' => true, 'id' => $id, 'message' => 'Brand created successfully.'];
            }

            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'id' => null, 'message' => 'DB insert failed: ' . $err];
        }

        return ['success' => false, 'id' => null, 'message' => 'No DB connection available.'];
    } catch (Throwable $e) {
        return ['success' => false, 'id' => null, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Update an existing brand (name and optional category).
 * @param int      $brandId
 * @param string   $newName
 * @param int|null $catId
 * @return array [success=>bool, message=>string]
 */
function edit_brand_ctr(int $brandId, string $newName, ?int $catId = null)
{
    $b = new Brand();
    return $b->updateBrand($brandId, $newName, $catId);
}

/**
 * Delete a brand.
 * @param int $brandId
 * @return array [success=>bool, message=>string]
 */
function delete_brand_ctr(int $brandId)
{
    $b = new Brand();
    return $b->deleteBrand($brandId);
}

/**
 * Get a brand by ID.
 * @param int $brandId
 * @return array|null
 */
function get_brand_ctr(int $brandId)
{
    try {
        $b = new Brand();
        return $b->getBrandById($brandId);
    } catch (Throwable $e) {
        error_log('get_brand_ctr: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get a brand by name (optionally within a category).
 * @param string   $name
 * @param int|null $catId
 * @return array|null
 */
function get_brand_by_name_ctr(string $name, ?int $catId = null)
{
    $b = new Brand();
    return $b->getBrandByName($name, $catId);
}

/**
 * Check if a brand name already exists (optionally scoped to category),
 * with optional exclusion of a brand_id (during updates).
 * @param string      $name
 * @param int|null    $catId
 * @param int|null    $excludeId
 * @return bool
 */
function brand_name_exists_ctr(string $name, ?int $catId = null, ?int $excludeId = null)
{
    try {
        $b = new Brand();
        if (method_exists($b, 'nameExists')) {
            return $b->nameExists($name, $catId, $excludeId);
        }
        // Fallback direct check
        if (isset($b->conn) && $b->conn instanceof mysqli) {
            $sql = 'SELECT brand_id FROM brands WHERE LOWER(brand_name)=LOWER(?)';
            $types = 's';
            $params = [$name];
            if ($catId !== null) {
                $sql .= ' AND cat_id = ?';
                $types .= 'i';
                $params[] = $catId;
            }
            if ($excludeId !== null) {
                $sql .= ' AND brand_id != ?';
                $types .= 'i';
                $params[] = $excludeId;
            }
            $sql .= ' LIMIT 1';
            $stmt = $b->conn->prepare($sql);
            if (!$stmt) return true; // fail-safe
            // bind params by reference
            $refs = [];
            foreach ($params as $k => $v) $refs[$k] = &$params[$k];
            array_unshift($refs, $types);
            call_user_func_array([$stmt, 'bind_param'], $refs);
            if (!$stmt->execute()) { $stmt->close(); return true; }
            $res = $stmt->get_result();
            $exists = $res->num_rows > 0;
            $stmt->close();
            return $exists;
        }
        return true;
    } catch (Throwable $e) {
        return true;
    }
}

/**
 * List brands (no category required). Falls back to raw queries if needed.
 */
function list_brands_ctr(?int $catId = null, ?string $search = null, string $orderBy = 'name_asc')
{
    try {
        // Prefer the Brand class if available
        if (class_exists('Brand')) {
            $b = new Brand();
            $rows = $b->listBrands($catId, $search, $orderBy);
            if (is_array($rows) && count($rows)) {
                return $rows;
            }

            // Fallback using the same connection (supports both column conventions)
            if (isset($b->conn) && $b->conn instanceof mysqli) {
                $variants = [
                    'SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC',
                    'SELECT id AS brand_id, name AS brand_name FROM brands ORDER BY name ASC',
                ];
                foreach ($variants as $sql) {
                    if ($stmt = $b->conn->prepare($sql)) {
                        if ($stmt->execute()) {
                            $res = $stmt->get_result();
                            $out = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                            $stmt->close();
                            if (!empty($out)) return $out;
                        } else {
                            $stmt->close();
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {
        error_log('list_brands_ctr exception: ' . $e->getMessage());
    }
    return [];
}

/**
 * List brands by a specific category.
 * @param int $catId
 * @return array
 */
function list_brands_by_category_ctr(int $catId)
{
    $b = new Brand();
    return $b->listBrandsByCategory($catId);
}

/**
 * List brands grouped by category.
 * Returns an array of groups with:
 *   ['cat_id'=>..., 'cat_name'=>..., 'brands'=>[...], 'count'=>N]
 * @return array
 */
function list_brands_grouped_ctr()
{
    $b = new Brand();
    return $b->listBrandsGroupedByCategory();
}

/**
 * Count brands in a given category.
 * @param int $catId
 * @return int
 */
function count_brands_by_category_ctr(int $catId)
{
    $b = new Brand();
    return $b->countByCategory($catId);
}

?>
