<?php

require_once '../classes/customer_class.php';


function register_customer_ctr($name, $email, $hashedPassword, $country, $city, $contact, $role = 2, $image = null)
{
    $cust = new Customer();
    return $cust->addCustomer($name, $email, $hashedPassword, $country, $city, $contact, $role, $image);
}
function login_customer_ctr($email, $password)
{
    $cust = new Customer();
    return $cust->loginCustomer($email, $password);
}


function edit_customer_ctr($id, $fieldsArray)
{
    $cust = new Customer();
    return $cust->editCustomer($id, $fieldsArray);
}

function delete_customer_ctr($id)
{
    $cust = new Customer();
    return $cust->deleteCustomer($id);
}

function get_customer_by_email_ctr($email)
{
    $cust = new Customer();
    return $cust->getCustomerByEmail($email);
}


function get_customer_by_id_ctr($id)
{
    $cust = new Customer();
    return $cust->getCustomerById($id);
}


function list_customers_ctr($limit = 100, $offset = 0)
{
    $cust = new Customer();
    return $cust->listCustomers($limit, $offset);
}


function customer_email_exists_ctr($email)
{
    $cust = new Customer();
    return $cust->emailExists($email);
}

?>
