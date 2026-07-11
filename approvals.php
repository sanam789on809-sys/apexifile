<?php
/**
 * Approvals Workflow System
 */

$allowed_levels = array(9, 8, 7); // All staff
require_once 'bootstrap.php';

$page_title = __('Approval Workflows', 'cftp_admin');
$page_id = 'approvals';

// Process actions
if ($_POST && isset($_POST['action'])) {
    if (validateCsrfToken()) {
        if ($_POST['action'] == 'request_approval') {
            $approval = new \ProjectSend\Classes\Approvals();
            $approval->set([
                'requester_id' => CURRENT_USER_ID,
                'file_id' => $_POST['file_id'],
                'target_department_id' => $_POST['department_id'],
                'reason' => $_POST['reason']
            ]);
            $result = $approval->requestApproval();
            if ($result['status'] === 'success') {
                $flash->success($result['message']);
                
                // Notify department heads
                $stmt = $dbh->prepare("SELECT user_id FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE department_id = :dept_id AND is_head = 1");
                $stmt->bindParam(':dept_id', $_POST['department_id'], PDO::PARAM_INT);
                $stmt->execute();
                $notifier = new \ProjectSend\Classes\InternalNotifications();
                while ($head = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $notifier->addNotification($head['user_id'], __('New approval request received', 'cftp_admin'), 'approvals.php');
                }
            } else {
                $flash->error($result['message']);
            }
        } elseif ($_POST['action'] == 'process_approval') {
            $approval = new \ProjectSend\Classes\Approvals($_POST['approval_id']);
            if ($approval->id) {
                if (!\ProjectSend\Classes\Departments::isDepartmentHead($approval->target_department_id, CURRENT_USER_ID)) {
                    $flash->error(__('You are not a department head for the target department.', 'cftp_admin'));
                } else {
                    $success = $approval->process($_POST['status'], CURRENT_USER_ID, $_POST['comments']);
                    if ($success) {
                        $flash->success(__('Approval processed successfully.', 'cftp_admin'));
                        
                        // Notify requester
                        $notifier = new \ProjectSend\Classes\InternalNotifications();
                        $status_str = ($_POST['status'] == 'Approved') ? __('approved', 'cftp_admin') : __('rejected', 'cftp_admin');
                        $notifier->addNotification($approval->requester_id, sprintf(__('Your approval request was %s', 'cftp_admin'), $status_str), 'approvals.php');
                    } else {
                        $flash->error(__('Error processing approval.', 'cftp_admin'));
                    }
                }
            }
        }
        ps_redirect('approvals.php');
    }
}

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="row">
    <!-- Pending Approvals (For Dept Heads) -->
    <div class="col-12 mb-4">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php _e('Incoming Requests (Needs your approval)', 'cftp_admin'); ?></h2>
            </div>
            <div class="ps-card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php _e('Requester', 'cftp_admin'); ?></th>
                            <th><?php _e('File ID', 'cftp_admin'); ?></th>
                            <th><?php _e('Reason', 'cftp_admin'); ?></th>
                            <th><?php _e('Actions', 'cftp_admin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $dbh->prepare("SELECT a.*, u.name as requester_name FROM " . TABLE_APPROVALS . " a LEFT JOIN " . TABLE_USERS . " u ON a.requester_id = u.id WHERE a.status = 'Pending' ORDER BY a.created_at DESC");
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . html_output($row['requester_name']) . '</td>';
                            echo '<td>' . html_output($row['file_id']) . '</td>';
                            echo '<td>' . html_output($row['reason']) . '</td>';
                            echo '<td>
                                    <form action="approvals.php" method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" id="csrf_token" value="' . getCsrfToken() . '" />
                                        <input type="hidden" name="action" value="process_approval">
                                        <input type="hidden" name="approval_id" value="' . $row['id'] . '">
                                        <button type="submit" name="status" value="Approved" class="btn btn-sm btn-success"><i class="fa fa-check"></i></button>
                                        <button type="submit" name="status" value="Rejected" class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                    </form>
                                  </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- My Requests (For standard Employees) -->
    <div class="col-12">
        <div class="ps-card">
            <div class="ps-card-header">
                <h2 class="ps-card-title"><?php _e('My Share Requests', 'cftp_admin'); ?></h2>
                <div class="ps-card-actions">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="fa fa-paper-plane"></i> <?php _e('New Request', 'cftp_admin'); ?>
                    </button>
                </div>
            </div>
            <div class="ps-card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php _e('File ID', 'cftp_admin'); ?></th>
                            <th><?php _e('Target Dept', 'cftp_admin'); ?></th>
                            <th><?php _e('Status', 'cftp_admin'); ?></th>
                            <th><?php _e('Comments', 'cftp_admin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $dbh->prepare("SELECT a.*, d.name as dept_name FROM " . TABLE_APPROVALS . " a LEFT JOIN " . TABLE_DEPARTMENTS . " d ON a.target_department_id = d.id WHERE a.requester_id = :uid ORDER BY a.created_at DESC");
                        $stmt->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . html_output($row['file_id']) . '</td>';
                            echo '<td>' . html_output($row['dept_name']) . '</td>';
                            echo '<td><span class="badge bg-secondary">' . html_output($row['status']) . '</span></td>';
                            echo '<td>' . html_output($row['reviewer_comments']) . '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- New Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php _e('Request File Share', 'cftp_admin'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="approvals.php" method="post">
            <?php addCsrf(); ?>
            <input type="hidden" name="action" value="request_approval">
            <div class="mb-3">
                <label for="file_id" class="form-label"><?php _e('Select File ID', 'cftp_admin'); ?></label>
                <input type="number" class="form-control" id="file_id" name="file_id" required>
            </div>
            <div class="mb-3">
                <label for="department_id" class="form-label"><?php _e('Target Department', 'cftp_admin'); ?></label>
                <select class="form-select" id="department_id" name="department_id" required>
                    <option value=""><?php _e('Select...', 'cftp_admin'); ?></option>
                    <?php
                    $d_stmt = $dbh->prepare("SELECT id, name FROM " . TABLE_DEPARTMENTS . " ORDER BY name ASC");
                    $d_stmt->execute();
                    while ($d = $d_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $d['id'] . '">' . html_output($d['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="reason" class="form-label"><?php _e('Reason', 'cftp_admin'); ?></label>
                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
            </div>
            <div class="modal-footer px-0 pb-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'cftp_admin'); ?></button>
                <button type="submit" class="btn btn-primary"><?php _e('Send Request', 'cftp_admin'); ?></button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
