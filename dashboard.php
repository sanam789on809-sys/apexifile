<?php
/**
 * Home page for logged in system users.
 */
require_once 'bootstrap.php';
// Dashboard is accessible to all logged-in non-client users
redirect_if_not_logged_in();

// Clients used to be redirected, but now they use the main dashboard.
// if (current_role_in(['Client'])) {
//     ps_redirect(BASE_URI . 'my_files/');
// }

$page_title = __('Dashboard', 'cftp_admin');

$active_nav = 'dashboard';

$body_class = array('dashboard', 'home', 'hide_title');
$page_id = 'dashboard';

include_once ADMIN_VIEWS_DIR . DS . 'header.php';

define('CAN_INCLUDE_FILES', true);

if (current_user_can('view_dashboard_counters')) {
    include_once WIDGETS_FOLDER . 'counters.php';
}
?>
<?php
// Check user's department role
$dept_id = null;
$is_head = false;
try {
    $stmt = $dbh->prepare("SELECT department_id, is_head FROM " . TABLE_DEPARTMENT_MEMBERS . " WHERE user_id = :uid");
    $stmt->bindValue(':uid', CURRENT_USER_ID, PDO::PARAM_INT);
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dept_id = $row['department_id'];
        $is_head = ($row['is_head'] == 1);
    }
} catch (PDOException $e) {
    // Ignore, table will be created by DatabaseUpgrade shortly
}
?>
<div class="dashboard-widgets-container" id="dashboard-widgets">

    <?php if ($dept_id): ?>
        <?php if ($is_head): ?>
            <!-- Department Head Widgets -->
            <div class="widget-container w-100">
                <div class="ps-card">
                    <div class="ps-card-header"><h3 class="ps-card-title"><?php _e('Department Head Actions', 'cftp_admin'); ?></h3></div>
                    <div class="ps-card-body">
                        <div class="row text-center">
                            <div class="col-sm-4">
                                <h5><?php _e('Pending Approvals', 'cftp_admin'); ?></h5>
                                <a href="approvals.php" class="btn btn-warning"><?php _e('View Approvals', 'cftp_admin'); ?></a>
                            </div>
                            <div class="col-sm-4">
                                <h5><?php _e('Team Tasks', 'cftp_admin'); ?></h5>
                                <a href="tasks.php" class="btn btn-info"><?php _e('Manage Tasks', 'cftp_admin'); ?></a>
                            </div>
                            <div class="col-sm-4">
                                <h5><?php _e('Department Stats', 'cftp_admin'); ?></h5>
                                <p class="text-muted"><?php _e('coming soon', 'cftp_admin'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Standard Employee Widgets -->
            <div class="widget-container w-100">
                <div class="ps-card">
                    <div class="ps-card-header"><h3 class="ps-card-title"><?php _e('Employee Dashboard', 'cftp_admin'); ?></h3></div>
                    <div class="ps-card-body">
                        <div class="row text-center">
                            <div class="col-sm-4">
                                <h5><?php _e('Assigned Tasks', 'cftp_admin'); ?></h5>
                                <a href="tasks.php" class="btn btn-info"><?php _e('View My Tasks', 'cftp_admin'); ?></a>
                            </div>
                            <div class="col-sm-4">
                                <h5><?php _e('Recent Documents', 'cftp_admin'); ?></h5>
                                <a href="manage-files.php" class="btn btn-primary"><?php _e('View Files', 'cftp_admin'); ?></a>
                            </div>
                            <div class="col-sm-4">
                                <h5><?php _e('Shared Files', 'cftp_admin'); ?></h5>
                                <a href="approvals.php" class="btn btn-secondary"><?php _e('View Requests', 'cftp_admin'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif (current_role_in(['Client'])): ?>
        <!-- Client Widgets -->
        <div class="widget-container w-100">
            <div class="ps-card">
                <div class="ps-card-header"><h3 class="ps-card-title"><?php _e('Client Dashboard', 'cftp_admin'); ?></h3></div>
                <div class="ps-card-body">
                    <div class="row text-center">
                        <div class="col-sm-3">
                            <h5><?php _e('Uploaded Files', 'cftp_admin'); ?></h5>
                            <a href="<?php echo BASE_URI; ?>manage-files.php" class="btn btn-primary"><?php _e('View My Files', 'cftp_admin'); ?></a>
                        </div>
                        <div class="col-sm-3">
                            <h5><?php _e('Shared Documents', 'cftp_admin'); ?></h5>
                            <a href="<?php echo BASE_URI; ?>manage-files.php" class="btn btn-info"><?php _e('View Shared', 'cftp_admin'); ?></a>
                        </div>
                        <div class="col-sm-3">
                            <h5><?php _e('Support Tickets', 'cftp_admin'); ?></h5>
                            <a href="<?php echo BASE_URI; ?>support.php" class="btn btn-warning"><?php _e('Get Support', 'cftp_admin'); ?></a>
                        </div>
                        <div class="col-sm-3">
                            <h5><?php _e('Notifications', 'cftp_admin'); ?></h5>
                            <a href="<?php echo BASE_URI; ?>notifications.php" class="btn btn-secondary"><?php _e('View Alerts', 'cftp_admin'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>    <?php if (current_user_can('view_statistics')) { ?>
        <div class="widget-container" data-widget="statistics">
            <?php include_once WIDGETS_FOLDER . 'statistics.php'; ?>
        </div>
    <?php } ?>



    <?php if (current_user_can('view_system_info')) { ?>
        <div class="widget-container" data-widget="system-info">
            <?php include_once WIDGETS_FOLDER . 'system-information.php'; ?>
        </div>
    <?php } ?>

    <?php if (current_user_can('view_actions_log')) { ?>
        <div class="widget-container" data-widget="actions-log">
            <?php include_once WIDGETS_FOLDER . 'actions-log.php'; ?>
        </div>
    <?php } ?>


    <?php if (current_user_can('view_storage_analytics')) { ?>
        <div class="widget-container" data-widget="storage-analytics">
            <?php include_once WIDGETS_FOLDER . 'storage-analytics.php'; ?>
        </div>
    <?php } ?>


    <?php if (current_user_can('view_download_analytics')) { ?>
        <div class="widget-container" data-widget="download-analytics">
            <?php include_once WIDGETS_FOLDER . 'download-analytics.php'; ?>
        </div>
    <?php } ?>



</div>
<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
