<?php
require_once 'bootstrap.php';
header('Content-Type: application/json');

if (!defined('CURRENT_USER_ID')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'send') {
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Message is empty']);
            exit;
        }

        $stmt = $dbh->prepare("INSERT INTO " . TABLE_CHAT_MESSAGES . " (sender_id, message) VALUES (:sender, :msg)");
        $stmt->bindValue(':sender', CURRENT_USER_ID, PDO::PARAM_INT);
        $stmt->bindValue(':msg', $message, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save message']);
        }
    } elseif ($action === 'fetch') {
        // Fetch last 50 messages
        $stmt = $dbh->prepare("
            SELECT c.*, u.name as sender_name 
            FROM " . TABLE_CHAT_MESSAGES . " c
            LEFT JOIN " . TABLE_USERS . " u ON c.sender_id = u.id
            ORDER BY c.created_at DESC
            LIMIT 50
        ");
        $stmt->execute();
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // We order by DESC to get latest, but we want to return them in ASC order for UI
            array_unshift($messages, [
                'id' => $row['id'],
                'sender' => $row['sender_name'],
                'message' => html_output($row['message']),
                'time' => date('g:i a', strtotime($row['created_at'])),
                'is_me' => ($row['sender_id'] == CURRENT_USER_ID)
            ]);
        }
        
        echo json_encode(['status' => 'success', 'messages' => $messages]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
