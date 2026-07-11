<?php
/**
 * Tasks AJAX Endpoint
 */
$allowed_levels = array(9, 8, 7); // Admin, Department Head, Employee
ob_start();
require_once 'bootstrap.php';

$buffered_output = ob_get_clean();
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_status'])) {
    if (!validateCsrfToken()) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    $task_id = (int)$_POST['task_id'];
    $new_status = $_POST['status'];
    
    $valid_statuses = ['Pending', 'In Progress', 'Waiting Review', 'Completed'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }

    // Only assignees and department heads can update status. For simplicity, we check if they are the assignee or admin/head.
    $stmt_check = $dbh->prepare("SELECT assigner_id FROM " . TABLE_TASKS . " WHERE id = :id AND assignee_id = :uid");
    $stmt_check->bindParam(':id', $task_id, PDO::PARAM_INT);
    $stmt_check->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
    $stmt_check->execute();
    
    // Note: If they are a system admin or department head, they might be moving someone else's task.
    // For now, let's keep it simple: you can move your own tasks. If you're a System Admin, you can move any task.
    $is_admin = current_role_in(['System Administrator']);
    
    if ($stmt_check->rowCount() > 0 || $is_admin) {
        if ($is_admin) {
            $stmt_update = $dbh->prepare("UPDATE " . TABLE_TASKS . " SET status = :status WHERE id = :id");
        } else {
            $stmt_update = $dbh->prepare("UPDATE " . TABLE_TASKS . " SET status = :status WHERE id = :id AND assignee_id = :uid");
            $stmt_update->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
        }
        $stmt_update->bindParam(':status', $new_status);
        $stmt_update->bindParam(':id', $task_id, PDO::PARAM_INT);
        
        if ($stmt_update->execute()) {
            
            // Fetch assigner to notify them if someone else changed it
            if (!$is_admin) {
                $assigner_id = $stmt_check->fetchColumn();
                $notifier = new \ProjectSend\Classes\InternalNotifications();
                $notifier->addNotification($assigner_id, __('Task status updated to ', 'cftp_admin') . $new_status, 'tasks.php');
            }

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Permission denied. You can only move your own tasks.']);
    }
}
