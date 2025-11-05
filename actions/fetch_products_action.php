<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../controllers/product_controller.php';

function out($arr){ echo json_encode($arr); exit; }

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    $result = list_products_ctr($limit);
    
    if (!is_array($result) || empty($result['success'])) {
        out(['status' => 'error', 'message' => $result['message'] ?? 'Failed to fetch products']);
    }
    
    $rows = $result['data'] ?? [];
    out(['status' => 'success', 'data' => $rows]);
} catch (Throwable $e) {
    out(['status' => 'error', 'message' => $e->getMessage()]);
}