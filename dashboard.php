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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row mb-4">
    <div class="col-xl-8 col-lg-7">
        <div class="ps-card h-100">
            <div class="ps-card-header d-flex justify-content-between align-items-center">
                <h3 class="ps-card-title mb-0"><?php _e('Task Analytics', 'cftp_admin'); ?></h3>
            </div>
            <div class="ps-card-body">
                <?php
                // Fetch task counts
                $stmt_stats = $dbh->prepare("SELECT status, COUNT(*) as count FROM " . TABLE_TASKS . " GROUP BY status");
                $stmt_stats->execute();
                $stats = ['Pending' => 0, 'In Progress' => 0, 'Waiting Review' => 0, 'Completed' => 0];
                while ($row = $stmt_stats->fetch(PDO::FETCH_ASSOC)) {
                    $status = $row['status'] ?: 'Pending';
                    $stats[$status] += $row['count'];
                }
                ?>
                <canvas id="tasksChart" style="max-height: 350px;"></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var ctx = document.getElementById('tasksChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pending', 'In Progress', 'Waiting Review', 'Completed'],
                            datasets: [{
                                data: [
                                    <?php echo $stats['Pending']; ?>,
                                    <?php echo $stats['In Progress']; ?>,
                                    <?php echo $stats['Waiting Review']; ?>,
                                    <?php echo $stats['Completed']; ?>
                                ],
                                backgroundColor: [
                                    '#94a3b8', // Slate 400
                                    '#3b82f6', // Blue 500
                                    '#f59e0b', // Amber 500
                                    '#10b981'  // Emerald 500
                                ],
                                hoverOffset: 8,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { 
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        font: { family: "'Outfit', 'Inter', sans-serif", size: 13 }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                    padding: 12,
                                    titleFont: { family: "'Outfit', 'Inter', sans-serif", size: 14 },
                                    bodyFont: { family: "'Outfit', 'Inter', sans-serif", size: 13 },
                                    cornerRadius: 8
                                }
                            },
                            cutout: '75%'
                        }
                    });
                });
                </script>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-5">
        <div class="ps-card h-100" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(0,0,0,0.04);">
            <div class="ps-card-header" style="border-bottom: 1px solid rgba(0,0,0,0.04);">
                <h3 class="ps-card-title mb-0" style="font-weight: 700; color: #0f172a;"><i class="fa fa-bolt" style="color:#6366f1;"></i> <?php _e('Activity Feed', 'cftp_admin'); ?></h3>
            </div>
            <div class="ps-card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Fetch recent tasks
                    $stmt_feed = $dbh->prepare("SELECT title, status, updated_at FROM " . TABLE_TASKS . " ORDER BY updated_at DESC LIMIT 6");
                    $stmt_feed->execute();
                    if ($stmt_feed->rowCount() == 0) {
                        echo '<div class="p-4 text-center text-muted">No recent activity</div>';
                    }
                    while ($feed = $stmt_feed->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="list-group-item list-group-item-action border-0" style="background: transparent; border-bottom: 1px solid rgba(0,0,0,0.04) !important; padding: 1.25rem 1.5rem; transition: background 0.2s;">';
                        echo '  <div class="d-flex w-100 justify-content-between align-items-center mb-1">';
                        echo '    <h6 class="mb-0 text-truncate" style="max-width: 70%; font-weight: 600; color: #1e293b;">' . html_output($feed['title']) . '</h6>';
                        echo '    <small class="text-muted" style="font-size: 0.75rem;">' . date('M j, g:i a', strtotime($feed['updated_at'])) . '</small>';
                        echo '  </div>';
                        echo '  <p class="mb-0 small text-muted">Status changed to <strong style="color: #4f46e5;">' . html_output($feed['status']) . '</strong></p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-widgets-container" id="dashboard-widgets">
    <!-- Existing widgets kept below -->
    <?php if (current_user_can('view_statistics')) { ?>
        <div class="widget-container" data-widget="statistics">
            <?php include_once WIDGETS_FOLDER . 'statistics.php'; ?>
        </div>
    <?php } ?>
    <?php if (current_user_can('view_system_info')) { ?>
        <div class="widget-container" data-widget="system-info">
            <?php include_once WIDGETS_FOLDER . 'system-information.php'; ?>
        </div>
    <?php } ?>
</div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
