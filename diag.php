<?php
require_once 'bootstrap.php';
echo "<pre>";
echo "Current DB Version: " . get_option("database_version") . "\n";
echo "TABLE_TASKS exists: " . (table_exists(TABLE_TASKS) ? "YES" : "NO") . "\n";
echo "TABLE_DEPARTMENTS exists: " . (table_exists(TABLE_DEPARTMENTS) ? "YES" : "NO") . "\n";
global $updates_error_messages;
print_r($updates_error_messages ?? []);
echo "</pre>";
