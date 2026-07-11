<?php
define('IS_INSTALL', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'bootstrap.php';

try {
    require 'includes/upgrades/2026071103.php';
    echo "Done Upgrade\n";
} catch (Throwable $e) {
    echo "FATAL: " . $e->getMessage();
}
