<?php
require_once 'bootstrap.php';
try {
    $stmt = $dbh->query("SELECT * FROM " . TABLE_CHAT_MESSAGES . " LIMIT 1");
    echo "TABLE EXISTS!\n";
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
