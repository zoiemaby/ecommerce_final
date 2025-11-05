<?php

require_once '../classes/category_class.php';

/**
 * Controller wrappers for Category class methods
 * These mirror the style used in your customer controller file.
 */

function add_category_ctr(string $name)
{
    $cat = new Category();
    return $cat->addCategory($name);
}

function edit_category_ctr(int $id, string $newName)
{
    $cat = new Category();
    return $cat->editCategory($id, $newName);
}

function delete_category_ctr(int $id)
{
    $cat = new Category();
    return $cat->deleteCategory($id);
}

function get_category_by_id_ctr(int $id)
{
    $cat = new Category();
    return $cat->getCategoryById($id);
}

function get_category_by_name_ctr(string $name)
{
    $cat = new Category();
    return $cat->getCategoryByName($name);
}

function list_categories_ctr(int $limit = 100, int $offset = 0)
{
    $cat = new Category();
    return $cat->listCategories($limit, $offset);
}

function category_name_exists_ctr(string $name, ?int $excludeId = null)
{
    $cat = new Category();
    return $cat->nameExists($name, $excludeId);
}

?>
