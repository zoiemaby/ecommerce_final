<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../controllers/product_controller.php';

function out($arr){ echo json_encode($arr); exit; }

try {
    $id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    if ($id <= 0) out(['status' => 'error', 'message' => 'Invalid product id']);

    $res = get_product_by_id_ctr($id);
    if (!is_array($res) || empty($res['success'])) {
        out(['status' => 'error', 'message' => $res['message'] ?? 'Product not found']);
    }
    $row = $res['data'] ?? null;
    if (!$row) out(['status' => 'error', 'message' => 'Product not found']);
    out(['status' => 'success', 'data' => $row]);
} catch (Throwable $e) {
    out(['status' => 'error', 'message' => $e->getMessage()]);
}