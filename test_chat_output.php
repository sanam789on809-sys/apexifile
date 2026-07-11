<?php
require 'bootstrap.php';
$url = 'http://localhost/chat-ajax.php'; // or use local server if possible
// We can just include it in an output buffer to see what it does!
ob_start();
$_POST['action'] = 'fetch';
try {
    require 'chat-ajax.php';
} catch (Throwable $e) {
    echo "CAUGHT IN SCRIPT: " . $e->getMessage();
}
$output = ob_get_clean();
echo "OUTPUT WAS:\n" . $output;
