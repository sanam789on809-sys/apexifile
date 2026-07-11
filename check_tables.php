<?php
require 'bootstrap.php';
global $dbh;
$tables = ['tbl_tasks', 'tbl_chat_messages', 'tbl_departments', 'tbl_department_members'];
foreach($tables as $t) {
    try {
        $stmt = $dbh->query("SHOW TABLES LIKE '$t'");
        if ($stmt->rowCount() > 0) {
            echo $t . " EXISTS\n";
        } else {
            echo $t . " MISSING\n";
        }
    } catch (Exception $e) {
        echo $t . " ERROR: " . $e->getMessage() . "\n";
    }
}
