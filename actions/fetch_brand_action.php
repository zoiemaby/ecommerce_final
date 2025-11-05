<?php
/**
 * fetch_brand_action.php
 *
 * Fetch and return brand data for use on the Brand Management page.
 * This action file calls the Brand controller functions.
 *
 * Use case:
 *  - Populating dropdowns or listing brands on admin dashboard.
 *  - Can return all brands or grouped by category.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/brand_controller.php';

function respond($ok, $message, $data = [], $status = 200) {
  http_response_code($status);
  echo json_encode(['ok' => (bool)$ok, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

$type    = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$search  = isset($_GET['search']) ? trim((string)$_GET['search']) : null;
$brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;

try {
  if ($type === 'single') {
    if ($brandId <= 0) respond(false, 'Invalid brand ID.', [], 422);
    if (function_exists('get_brand_ctr')) {
      $row = get_brand_ctr($brandId);
      respond(true, 'OK', $row ? [$row] : []);
    }
  } else {
    // 1) controller
    if (function_exists('list_brands_ctr')) {
      $rows = list_brands_ctr(null, $search, 'name_asc');
      if (is_array($rows) && !empty($rows)) respond(true, 'OK', $rows);
    }
    // 2) Brand class
    if (class_exists('Brand')) {
      $b = new Brand();
      $rows = $b->listBrands(null, $search, 'name_asc');
      if (is_array($rows) && !empty($rows)) respond(true, 'OK', $rows);
    }
    // 3) PDO fallbacks
    if (isset($pdo) && $pdo instanceof PDO) {
      $q = null;
      $params = [];
      $candidates = [
        'SELECT brand_id, brand_name FROM brands',
        'SELECT id AS brand_id, name AS brand_name FROM brands',
      ];
      foreach ($candidates as $base) {
        $sql = $base;
        if ($search !== null && $search !== '') { $sql .= ' WHERE LOWER(brand_name) LIKE LOWER(?)'; $params = ['%'.$search.'%']; }
        $sql .= ' ORDER BY brand_name ASC';
        try {
          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);
          $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          if (!empty($rows)) respond(true, 'OK', $rows);
        } catch (Throwable $e) {
          // try next variant
        }
      }
    }
    respond(true, 'OK', []); // no rows found
  }
} catch (Throwable $e) {
  error_log('fetch_brand_action: ' . $e->getMessage());
  respond(false, 'Server error.', [], 500);
}

respond(false, 'Brand controller not available.', [], 500);
