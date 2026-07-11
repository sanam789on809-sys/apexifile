<?php
require_once 'bootstrap.php';
$stmt = $dbh->prepare("UPDATE " . TABLE_OPTIONS . " SET value = 'material-drive' WHERE name = 'selected_clients_template'");
$stmt->execute();
echo "Updated.";
