<?php
/**
 * View and respond to a Support Ticket
 */
$allowed_levels = array(9, 8, 7, 0); // All staff + Clients
require_once 'bootstrap.php';

$page_title = __('View Ticket', 'cftp_admin');
$page_id = 'ticket_view';

if (empty($_GET['id'])) {
    ps_redirect('support.php');
}

$ticket = new \ProjectSend\Classes\Tickets();
if (!$ticket->get($_GET['id'])) {
    ps_redirect('support.php');
}

// Security: If client, verify they own this ticket
if (CURRENT_USER_LEVEL == 0 && $ticket->user_id != CURRENT_USER_ID) {
    exit_with_error_code(403);
}

if ($_POST) {
    if (validateCsrfToken()) {
        if (!empty($_POST['reply_message'])) {
            $ticket->addReply(CURRENT_USER_ID, $_POST['reply_message']);
            $flash->success(__('Reply added successfully.', 'cftp_admin'));
        }
        if (!empty($_POST['new_status']) && CURRENT_USER_LEVEL != 0) { // Only staff can change status
            $ticket->updateStatus($_POST['new_status']);
            $flash->success(__('Ticket status updated.', 'cftp_admin'));
        }
        ps_redirect('tickets-view.php?id=' . $ticket->id);
    }
}

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="ps-card mb-4">
            <div class="ps-card-header">
                <h3 class="ps-card-title"><?php echo html_output($ticket->subject); ?></h3>
            </div>
            <div class="ps-card-body">
                <div class="ticket-replies">
                    <?php
                    $stmt = $dbh->prepare("SELECT r.*, u.name as user_name, u.role as user_role FROM " . TABLE_TICKET_REPLIES . " r LEFT JOIN " . TABLE_USERS . " u ON r.user_id = u.id WHERE r.ticket_id = :id ORDER BY r.created_at ASC");
                    $stmt->bindValue(':id', $ticket->id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    while ($reply = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $is_staff = ($reply['user_role'] != 'Client') ? 'bg-light border-primary' : 'bg-white';
                        ?>
                        <div class="card mb-3 <?php echo $is_staff; ?>">
                            <div class="card-header d-flex justify-content-between">
                                <strong><?php echo html_output($reply['user_name']); ?></strong>
                                <small class="text-muted"><?php echo $reply['created_at']; ?></small>
                            </div>
                            <div class="card-body">
                                <?php echo nl2br(html_output($reply['message'])); ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <?php if ($ticket->status != 'Closed'): ?>
                <hr class="my-4">
                <h4><?php _e('Add Reply', 'cftp_admin'); ?></h4>
                <form action="tickets-view.php?id=<?php echo $ticket->id; ?>" method="post">
                    <?php addCsrf(); ?>
                    <div class="mb-3">
                        <textarea class="form-control" name="reply_message" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php _e('Post Reply', 'cftp_admin'); ?></button>
                </form>
                <?php else: ?>
                <div class="alert alert-info">
                    <?php _e('This ticket is closed. You cannot add new replies.', 'cftp_admin'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-4">
        <div class="ps-card">
            <div class="ps-card-header">
                <h3 class="ps-card-title"><?php _e('Ticket Details', 'cftp_admin'); ?></h3>
            </div>
            <div class="ps-card-body">
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php _e('Status', 'cftp_admin'); ?>
                        <span class="badge bg-secondary"><?php echo html_output($ticket->status); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php _e('Priority', 'cftp_admin'); ?>
                        <span><?php echo html_output($ticket->priority); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php _e('Category', 'cftp_admin'); ?>
                        <span><?php echo html_output($ticket->category); ?></span>
                    </li>
                </ul>

                <?php if (CURRENT_USER_LEVEL != 0): ?>
                <h5><?php _e('Admin Actions', 'cftp_admin'); ?></h5>
                <form action="tickets-view.php?id=<?php echo $ticket->id; ?>" method="post">
                    <?php addCsrf(); ?>
                    <div class="mb-3">
                        <select name="new_status" class="form-select">
                            <option value="Open" <?php if($ticket->status=='Open') echo 'selected'; ?>><?php _e('Open', 'cftp_admin'); ?></option>
                            <option value="In Progress" <?php if($ticket->status=='In Progress') echo 'selected'; ?>><?php _e('In Progress', 'cftp_admin'); ?></option>
                            <option value="Waiting Client" <?php if($ticket->status=='Waiting Client') echo 'selected'; ?>><?php _e('Waiting Client', 'cftp_admin'); ?></option>
                            <option value="Resolved" <?php if($ticket->status=='Resolved') echo 'selected'; ?>><?php _e('Resolved', 'cftp_admin'); ?></option>
                            <option value="Closed" <?php if($ticket->status=='Closed') echo 'selected'; ?>><?php _e('Closed', 'cftp_admin'); ?></option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning w-100"><?php _e('Update Status', 'cftp_admin'); ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
