<?php
/**
 * Support Ticketing System
 */

$allowed_levels = array(9, 8, 7, 0); // All staff + Clients
require_once 'bootstrap.php';

$page_title = __('Support Tickets', 'cftp_admin');
$page_id = 'support';

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php echo $page_title; ?></h2>
                <div class="ps-card-actions">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="fa fa-plus"></i> <?php _e('Create Ticket', 'cftp_admin'); ?>
                    </button>
                </div>
            </div>
            <div class="ps-card-body">
                <table id="tickets_table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php _e('Subject', 'cftp_admin'); ?></th>
                            <th><?php _e('Category', 'cftp_admin'); ?></th>
                            <th><?php _e('Priority', 'cftp_admin'); ?></th>
                            <th><?php _e('Status', 'cftp_admin'); ?></th>
                            <th><?php _e('Actions', 'cftp_admin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch tickets logic here
                        $query = "SELECT t.*, u.name as user_name FROM " . TABLE_TICKETS . " t LEFT JOIN " . TABLE_USERS . " u ON t.user_id = u.id";
                        // If client, only show their tickets
                        if (CURRENT_USER_LEVEL == 0) {
                            $query .= " WHERE t.user_id = :user_id";
                        }
                        $query .= " ORDER BY t.created_at DESC";
                        
                        $stmt = $dbh->prepare($query);
                        if (CURRENT_USER_LEVEL == 0) {
                            $stmt->bindValue(':user_id', CURRENT_USER_ID, PDO::PARAM_INT);
                        }
                        $stmt->execute();
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . html_output($row['subject']) . '</td>';
                            echo '<td>' . html_output($row['category']) . '</td>';
                            echo '<td>' . html_output($row['priority']) . '</td>';
                            echo '<td><span class="badge bg-secondary">' . html_output($row['status']) . '</span></td>';
                            echo '<td>
                                    <a href="tickets-view.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                  </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php _e('Create Support Ticket', 'cftp_admin'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="support.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="subject" class="form-label"><?php _e('Subject', 'cftp_admin'); ?></label>
                <input type="text" class="form-control" id="subject" name="subject" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label"><?php _e('Category', 'cftp_admin'); ?></label>
                <select class="form-select" id="category" name="category">
                    <option value="General"><?php _e('General Inquiry', 'cftp_admin'); ?></option>
                    <option value="Technical"><?php _e('Technical Support', 'cftp_admin'); ?></option>
                    <option value="Billing"><?php _e('Billing', 'cftp_admin'); ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label"><?php _e('Priority', 'cftp_admin'); ?></label>
                <select class="form-select" id="priority" name="priority">
                    <option value="Low"><?php _e('Low', 'cftp_admin'); ?></option>
                    <option value="Medium" selected><?php _e('Medium', 'cftp_admin'); ?></option>
                    <option value="High"><?php _e('High', 'cftp_admin'); ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label"><?php _e('Message', 'cftp_admin'); ?></label>
                <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
            </div>
            <div class="modal-footer px-0 pb-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'cftp_admin'); ?></button>
                <button type="submit" class="btn btn-primary" name="create_ticket"><?php _e('Submit Ticket', 'cftp_admin'); ?></button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
