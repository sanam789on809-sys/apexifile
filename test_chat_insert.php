<?php
require_once 'bootstrap.php';
try {
    $_POST['action'] = 'send';
    $_POST['message'] = 'Test message';
    
    // Simulate user login
    if (!defined('CURRENT_USER_ID')) {
        define('CURRENT_USER_ID', 1);
    }
    
    $stmt = $dbh->prepare("INSERT INTO " . TABLE_CHAT_MESSAGES . " (sender_id, message) VALUES (:sender, :msg)");
    $stmt->bindValue(':sender', CURRENT_USER_ID, PDO::PARAM_INT);
    $stmt->bindValue(':msg', $_POST['message'], PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        echo "INSERT SUCCESS!\n";
    } else {
        echo "INSERT FAILED: " . print_r($stmt->errorInfo(), true) . "\n";
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
