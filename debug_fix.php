<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Admin Panel Debugger</h1>";

// 1. Check DB Connection
echo "<h3>1. Checking Database Connection...</h3>";
require_once '../config/db.php';
if ($pdo) {
    echo "<span style='color:green'>Connected successfully.</span><br>";
} else {
    die("<span style='color:red'>Connection Failed.</span>");
}

// 2. Check Tables
echo "<h3>2. Checking Required Tables...</h3>";
$tables = ['admins', 'staff_roles'];
foreach ($tables as $table) {
    try {
        $check = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "Table `$table`: <span style='color:green'>EXISTS</span><br>";
    } catch (Exception $e) {
        echo "Table `$table`: <span style='color:red'>MISSING</span> - You must run `fix_admin_tables.sql`!<br>";
    }
}

// 3. Check Admins Columns
echo "<h3>3. Checking Admin Columns...</h3>";
try {
    $stmt = $pdo->query("DESCRIBE admins");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required = ['email', 'role_id', 'name'];
    foreach ($required as $req) {
        if (in_array($req, $columns)) {
            echo "Column `$req`: <span style='color:green'>EXISTS</span><br>";
        } else {
            echo "Column `$req`: <span style='color:red'>MISSING</span> - You must run `fix_admin_tables.sql`!<br>";
        }
    }
} catch (Exception $e) {
    echo "Error checking columns: " . $e->getMessage();
}

// 4. Check Session
echo "<h3>4. Session Status...</h3>";
session_start();
echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'Not Set') . "<br>";
echo "Role ID: " . ($_SESSION['admin_role_id'] ?? 'Not Set') . "<br>";

echo "<br><br><a href='logout.php' style='padding:10px; background:red; color:white; text-decoration:none;'>FORCE LOGOUT (Clear Session)</a>";
?>