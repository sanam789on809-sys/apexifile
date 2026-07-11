<?php
/**
 * Show updates status messages.
 */
    if (!empty($db_upgrade)) {
        if (!empty($db_upgrade->getAppliedUpdates())) {
            $updates_made = 1;
        };
    }

    // If any update was made to the database structure, show the message
	if (get_option('show_upgrade_success_message') == 'true' && current_role_in(['System Administrator', 'Account Manager', 'Uploader'])) {
?>
        <div class="row">
            <div class="col-sm-12">
                <div id="donations_message">
                    <p id="db_upgraded"><i class="fa fa-info-circle"></i> <?php _e('The database was updated to support this version of the software.', 'cftp_admin'); ?></p>
                    <p class="changelog-trigger-wrap">
                        <a href="#" class="changelog-trigger fs-5" data-version="<?php echo html_output(CURRENT_VERSION); ?>"><?php echo sprintf(__("See what's new in %s", 'cftp_admin'), '<strong>' . html_output(CURRENT_VERSION) . '</strong>'); ?> <i class="fa fa-arrow-right"></i></a>
                    </p>

                    <!-- Changelog modal -->
                    <div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changelogModalLabel"><?php _e("What's new", 'cftp_admin'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php _e('Close', 'cftp_admin'); ?>"></button>
                                </div>
                                <div class="modal-body" id="changelogModalBody">
                                    <div class="changelog-loading text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Close', 'cftp_admin'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
<?php
    }

    // Used when a new version is found, but only show to users who can manage updates
    // Don't show on the updates page itself
    $current_page = basename($_SERVER["SCRIPT_FILENAME"], '.php');
    if ( !current_role_in(['Client']) && current_user_can('manage_updates') && $current_page !== 'updates' ) {
        if (!should_check_for_updates() && (basename($_SERVER["SCRIPT_FILENAME"], '.php') == 'dashboard')) {
            ?>
            <div class="alert alert-warning update_msg">
                <div class="row">
                    <div class="col-12">
                        <strong><?php _e('Important', 'cftp_admin'); ?></strong> <?php echo sprintf( __('Checking for updates has been disabled. Make sure to check periodically for a new release', 'cftp_admin')); ?>
                    </div>
                </div>
            </div>
<?php
        } else {
            $update_data = get_latest_version_data();
            $update_data = json_decode($update_data);
            if ($update_data->update_available == '1') {
?>
                <div class="alert alert-warning update_msg mb-5">
                    <div class="row align-items-center">
                        <div class="col-sm-8">
                            <strong><?php _e('Update available!', 'cftp_admin'); ?></strong> <?php echo sprintf( __('CGT %s has been released', 'cftp_admin'), $update_data->latest_version); ?>
                        </div>
                        <div class="col-sm-4 text-right d-flex align-items-center justify-content-end gap-2">
                            <a href="<?php echo BASE_URI; ?>updates.php" class="btn btn-primary btn-sm"><?php _e('Update Now', 'cftp_admin');?></a>
                            <a href="<?php echo $update_data->url; ?>" class="btn btn-pslight btn-sm" target="_blank"><?php _e('Download', 'cftp_admin');?></a>
                            <a href="<?php echo $update_data->chlog; ?>" target="_blank" class="btn btn-pslight btn-sm"><?php _e('Changelog', 'cftp_admin');?></a>
                        </div>
                    </div>
                </div>
<?php
    		}
        }
	}

	if ( isset( $updates_error_messages ) && !empty( $updates_error_messages ) ) {
?>
		<div class="row">
			<div class="col-sm-12">
				<?php
					foreach ( $updates_error_messages as $updates_error_msg ) {
						echo system_message( 'error', $updates_error_msg );
					}
				?>
			</div>
		</div>
<?php
	}
