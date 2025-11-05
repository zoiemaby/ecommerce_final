<?php

/**
 * controllers/product_controller.php
 *
 * Simple thin wrappers around Product class methods.
 * No extra logic; actions should validate/normalize inputs themselves.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../classes/product_class.php';

// Singleton accessor
function get_product_model(): Product
{
    static $m = null;
    if ($m === null) {
        $m = new Product();
    }
    return $m;
}

// Map common alias keys to model's expected keys
function map_to_model_fields(array $data): array
{
    return [
        'product_title'    => trim($data['product_title'] ?? $data['title'] ?? ''),
        'product_cat'      => (int)($data['product_cat'] ?? $data['cat_id'] ?? 0),
        'product_brand'    => (int)($data['product_brand'] ?? $data['brand_id'] ?? 0),
        'product_price'    => (float)($data['product_price'] ?? $data['price'] ?? 0),
        'product_desc'     => (string)($data['product_desc'] ?? $data['description'] ?? ''),
        'product_keywords' => (string)($data['product_keywords'] ?? $data['keywords'] ?? ''),
        'product_image'    => (string)($data['product_image'] ?? ''),
    ];
}

// Map only fields actually provided in input (no defaults)
function map_partial_fields(array $data): array
{
    $out = [];
    if (array_key_exists('product_title', $data) || array_key_exists('title', $data)) {
        $out['product_title'] = trim((string)($data['product_title'] ?? $data['title']));
    }
    if (array_key_exists('product_cat', $data) || array_key_exists('cat_id', $data)) {
        $out['product_cat'] = (int)($data['product_cat'] ?? $data['cat_id']);
    }
    if (array_key_exists('product_brand', $data) || array_key_exists('brand_id', $data)) {
        $out['product_brand'] = (int)($data['product_brand'] ?? $data['brand_id']);
    }
    if (array_key_exists('product_price', $data) || array_key_exists('price', $data)) {
        // keep 0 as a valid value if explicitly provided
        $val = $data['product_price'] ?? $data['price'];
        $out['product_price'] = (float)$val;
    }
    if (array_key_exists('product_desc', $data) || array_key_exists('description', $data)) {
        $out['product_desc'] = (string)($data['product_desc'] ?? $data['description']);
    }
    if (array_key_exists('product_keywords', $data) || array_key_exists('keywords', $data)) {
        $out['product_keywords'] = (string)($data['product_keywords'] ?? $data['keywords']);
    }
    if (array_key_exists('product_image', $data)) {
        $out['product_image'] = (string)$data['product_image'];
    }
    return $out;
}

// CREATE
function add_product_ctr(array $data): array
{
    return get_product_model()->createProduct(map_to_model_fields($data));
}

// READ
function list_products_ctr(int $limit = 100): array
{
    return get_product_model()->listProducts($limit);
}

function get_product_by_id_ctr(int $product_id): array
{
    return get_product_model()->getProductById($product_id);
}

// UPDATE
function update_product_ctr(int $product_id, array $data): array
{
    // allow partial updates; only include fields that were actually provided by caller
    $partial = map_partial_fields($data);
    // Optionally skip empty strings to avoid clearing values unintentionally
    foreach ($partial as $k => $v) {
        if ($v === '' || $v === null) {
            unset($partial[$k]);
        }
    }
    if (empty($partial)) {
        return ['success' => false, 'message' => 'No fields to update'];
    }
    return get_product_model()->updateProduct($product_id, $partial);
}

// DELETE
function delete_product_ctr(int $product_id): array
{
    return get_product_model()->deleteProduct($product_id);
}

// IMAGES (single main image only)
function add_product_image_ctr(int $product_id, string $path, int $sort = 0): array
{
    return get_product_model()->addImage($product_id, $path, $sort);
}

function set_product_main_image_ctr(int $product_id, string $path, bool $force = false): array
{
    return get_product_model()->setMainImage($product_id, $path, $force);
}
