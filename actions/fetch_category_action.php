<?php
// fetch_category_action.php
// Returns category data as JSON. If `id` is provided via GET, returns single category.
// Otherwise returns a paginated list. Uses controller wrappers in controllers/category_controller.php

require_once '../controllers/category_controller.php';

// Allow cross-origin requests if needed (adjust in production)
// header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        exit();
    }

    // If id provided, fetch single category
    if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
        $id = (int)$_GET['id'];
        $row = get_category_by_id_ctr($id);
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
            exit();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Category not found']);
            exit();
        }
    }

    // Optional query params: limit, offset, q (search by name)
    $limit = isset($_GET['limit']) && ctype_digit($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) && ctype_digit($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    // If a search query is provided, do simple filter on returned rows (since controller doesn't support search)
    $rows = list_categories_ctr($limit, $offset);
    if ($q !== '') {
        $qLower = mb_strtolower($q);
        $rows = array_values(array_filter($rows, function($r) use ($qLower) {
            return isset($r['category_name']) && mb_strpos(mb_strtolower($r['category_name']), $qLower) !== false;
        }));
    }

    echo json_encode(['success' => true, 'count' => count($rows), 'data' => $rows]);
    exit();

} catch (Throwable $e) {
    http_response_code(500);
    error_log('fetch_category_action error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal Server Error']);
    exit();
}

?>
