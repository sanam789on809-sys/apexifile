<?php
/**
 * Notifications view page
 */

$allowed_levels = array(9, 8, 7, 0); // All logged-in users, including clients
require_once 'bootstrap.php';

$page_title = __('Notifications', 'cftp_admin');
$page_id = 'notifications';

$notifier = new \ProjectSend\Classes\InternalNotifications();

// Mark as read if requested
if (isset($_GET['mark_read'])) {
    if (isset($_GET['csrf_token']) && $_GET['csrf_token'] === getCsrfToken()) {
        $stmt = $dbh->prepare("UPDATE " . TABLE_INTERNAL_NOTIFICATIONS . " SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $stmt->bindParam(':id', $_GET['mark_read'], PDO::PARAM_INT);
        $stmt->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
        $stmt->execute();
        ps_redirect('notifications.php');
    }
}

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    if (isset($_GET['csrf_token']) && $_GET['csrf_token'] === getCsrfToken()) {
        $stmt = $dbh->prepare("UPDATE " . TABLE_INTERNAL_NOTIFICATIONS . " SET is_read = 1 WHERE user_id = :uid");
        $stmt->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
        $stmt->execute();
        ps_redirect('notifications.php');
    }
}

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php echo $page_title; ?></h2>
                <div class="ps-card-actions">
                    <a href="notifications.php?mark_all_read=1&csrf_token=<?php echo getCsrfToken(); ?>" class="btn btn-sm btn-secondary">
                        <i class="fa fa-check-double"></i> <?php _e('Mark all as read', 'cftp_admin'); ?>
                    </a>
                </div>
            </div>
            <div class="ps-card-body">
                <div class="list-group">
                    <?php
                    // Get all notifications (read and unread)
                    $stmt = $dbh->prepare("SELECT * FROM " . TABLE_INTERNAL_NOTIFICATIONS . " WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 50");
                    $stmt->bindValue(':user_id', CURRENT_USER_ID, PDO::PARAM_INT);
                    $stmt->execute();
                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($notifications)) {
                        echo '<p class="text-muted text-center my-4">' . __('You have no notifications.', 'cftp_admin') . '</p>';
                    } else {
                        foreach ($notifications as $notification) {
                            $is_read_class = $notification['is_read'] ? 'bg-light text-muted' : 'bg-white font-weight-bold';
                            $link = !empty($notification['link_url']) ? $notification['link_url'] : '#';
                            ?>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $is_read_class; ?>">
                                <div>
                                    <a href="<?php echo html_output($link); ?>" class="text-decoration-none text-dark">
                                        <?php echo html_output($notification['message']); ?>
                                    </a>
                                    <div class="small text-muted mt-1">
                                        <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                    </div>
                                </div>
                                <?php if (!$notification['is_read']): ?>
                                <div>
                                    <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>&csrf_token=<?php echo getCsrfToken(); ?>" class="btn btn-sm btn-outline-secondary" title="<?php _e('Mark as read', 'cftp_admin'); ?>">
                                        <i class="fa fa-check"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
