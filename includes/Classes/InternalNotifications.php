<?php
namespace ProjectSend\Classes;

use \PDO;

class InternalNotifications
{
    private $dbh;

    public function __construct()
    {
        global $dbh;
        $this->dbh = $dbh;
    }

    /**
     * Add a new notification for a specific user
     */
    public function addNotification($user_id, $message, $link_url = null)
    {
        try {
            $statement = $this->dbh->prepare("INSERT INTO " . TABLE_INTERNAL_NOTIFICATIONS . " (user_id, message, link_url, created_at) VALUES (:user_id, :message, :link_url, NOW())");
            $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $statement->bindParam(':message', $message);
            $statement->bindParam(':link_url', $link_url);
            $statement->execute();
            return $this->dbh->lastInsertId();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Get unread notifications for a specific user
     */
    public function getUnread($user_id, $limit = 5)
    {
        try {
            $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_INTERNAL_NOTIFICATIONS . " WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC LIMIT :limit");
            $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get total unread count for a specific user
     */
    public function getUnreadCount($user_id)
    {
        try {
            $statement = $this->dbh->prepare("SELECT COUNT(*) FROM " . TABLE_INTERNAL_NOTIFICATIONS . " WHERE user_id = :user_id AND is_read = 0");
            $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $statement->execute();
            return $statement->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead($notification_id, $user_id)
    {
        try {
            $statement = $this->dbh->prepare("UPDATE " . TABLE_INTERNAL_NOTIFICATIONS . " SET is_read = 1 WHERE id = :id AND user_id = :user_id");
            $statement->bindParam(':id', $notification_id, PDO::PARAM_INT);
            $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $statement->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id)
    {
        try {
            $statement = $this->dbh->prepare("UPDATE " . TABLE_INTERNAL_NOTIFICATIONS . " SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
            $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $statement->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
