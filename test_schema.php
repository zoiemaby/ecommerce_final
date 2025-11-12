<?php
// Test script to check orders table schema
require_once 'settings/db_class.php';

$db = new Database();
$conn = $db->getConnection();

$sql = "SHOW COLUMNS FROM orders";
$result = $conn->query($sql);

echo "<h2>Orders Table Schema:</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}

echo "</table>";

// Check for invoice_amt
$sql2 = "SHOW COLUMNS FROM orders LIKE 'invoice_amt'";
$result2 = $conn->query($sql2);
echo "<p>Has invoice_amt column: " . ($result2->num_rows > 0 ? "YES" : "NO") . "</p>";

// Check for invoice_no
$sql3 = "SHOW COLUMNS FROM orders LIKE 'invoice_no'";
$result3 = $conn->query($sql3);
echo "<p>Has invoice_no column: " . ($result3->num_rows > 0 ? "YES" : "NO") . "</p>";

// Check orderdetails schema
$sql4 = "SHOW COLUMNS FROM orderdetails";
$result4 = $conn->query($sql4);

echo "<h2>OrderDetails Table Schema:</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while($row = $result4->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}

echo "</table>";
?>
