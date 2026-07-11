<?php
$_POST['action'] = 'fetch';
ob_start();
try {
    require 'chat-ajax.php';
} catch (Throwable $e) {
    echo "CAUGHT EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
$output = ob_get_clean();
echo "OUTPUT: \n" . $output;
