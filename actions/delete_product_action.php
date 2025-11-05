<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../controllers/product_controller.php';
function json_out($a){ echo json_encode($a); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['status'=>'error','message'=>'Use POST']);

$id = (int)($_POST['product_id'] ?? 0);
if ($id <= 0) json_out(['status'=>'error','message'=>'Invalid product id.']);

$res = delete_product_ctr($id);
if (is_array($res) && !empty($res['success'])) {
    json_out(['status'=>'success','message'=>$res['message'] ?? 'Deleted']);
}
json_out(['status'=>'error','message'=>$res['message'] ?? 'Delete failed']);