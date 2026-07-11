<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * This file generates the header for the back-end and also for the default
 * template.
 *
 * Other checks for user level are performed later to generate the different
 * menu items, and the content of the page that called this file.
 */
if (!defined('VIEW_TYPE')) define('VIEW_TYPE', 'private');

global $flash;

/** If no page title is defined, revert to a default one */
if (!isset($page_title)) { $page_title = __('System Administration','cftp_admin'); }

if (!isset($body_class)) { $body_class = []; }

if ( !empty( $_COOKIE['menu_contracted'] ) && $_COOKIE['menu_contracted'] == 'true' ) {
    $body_class[] = 'menu_contracted';
}

$body_class[] = 'menu_hidden';

/**
 * Silent updates that are needed even if no user is logged in.
 */
require_once INCLUDES_DIR . DS .'core.update.silent.php';

// Run required database upgrades
$db_upgrade = new \ProjectSend\Classes\DatabaseUpgrade;
$db_upgrade->upgradeDatabase(false);

/**
 * Call the database update file to see if any change is needed,
 * but only if logged in as a system user.
 */
$core_update_allowed = ['System Administrator', 'Account Manager', 'Uploader'];
if (current_role_in($core_update_allowed)) {
    require_once INCLUDES_DIR . DS . 'core.update.php';
}

// Redirect if password needs to be changed
password_change_required();

// Redirect if TOTP setup is required
totp_setup_required();
?>
<!doctype html>
<html lang="<?php echo SITE_LANG; ?>">
<head>
    <meta charset="<?php echo(CHARSET); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php meta_noindex(); ?>

    <title><?php echo html_output( $page_title . ' &raquo; ' . htmlspecialchars(get_option('this_install_title'), ENT_QUOTES, CHARSET) ); ?></title>
    <?php meta_favicon(); ?>

    <?php
        render_assets('js', 'head');
        render_assets('css', 'head');
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URI; ?>css/materialpro-theme.css">
    <link rel="stylesheet" href="<?php echo BASE_URI; ?>css/modern-revamp.css?v=<?php echo time(); ?>">
    <style id="sidebar-cache-buster-override">
    /* Force overriding the sidebar styling directly in HTML to bypass aggressive browser caching */
    .main_side_menu {
        background: #ffffff !important;
        border-right: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.02) !important;
    }
    .main_menu li.separator {
        border-top: 1px solid #f1f5f9 !important;
        margin: 20px 12px 20px 12px !important;
    }
    .main_menu li a {
        font-family: 'Poppins', sans-serif !important;
        font-weight: 800 !important; /* Extremely bold */
        font-size: 1.25rem !important; /* Huge font */
        color: #334155 !important; /* Dark Slate Blue */
        border-radius: 12px !important;
        margin: 6px 0 !important;
        padding: 16px 20px !important; /* Massive padding */
        letter-spacing: 0.02em !important;
    }
    .main_menu li a:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
    }
    .main_menu li.current_nav > a,
    .main_menu li.current_page > a {
        background: #eff6ff !important;
        color: #4F46E5 !important;
        font-weight: 800 !important;
    }
    .main_menu li.current_nav > a::before,
    .main_menu li.current_page > a::before {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 55%;
        background: #4F46E5 !important;
        border-radius: 8px 0 0 8px;
    }
    .main_menu li a i {
        font-size: 1.6rem !important; /* Massive icons */
        margin-right: 24px !important;
        color: #64748b !important;
    }
    .main_menu li.current_nav > a i,
    .main_menu li.current_page > a i {
        color: #4F46E5 !important;
    }
    .main_menu li ul li a {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #475569 !important;
        padding: 12px 16px 12px 28px !important;
    }
    </style>
    <?php
        render_custom_assets('head');
    ?>
</head>

<body <?php echo add_body_class( $body_class ); ?> <?php if (!empty($page_id)) { echo add_page_id($page_id); } ?>>
    <div class="mesh-background"></div>
    <?php include_once LAYOUT_DIR . DS . 'header-top.php'; ?>
    <?php include_once LAYOUT_DIR . DS . 'main-menu.php'; ?>

    <main>
        <div class="container-fluid">
            <div class="main_content">
                <?php
                    render_custom_assets('body_top');

                    // Gets the mark up and values for the System Updated and errors messages.
                    include_once INCLUDES_DIR . DS . 'updates.messages.php';

                    include_once INCLUDES_DIR . DS . 'header-messages.php';
                ?>

                <div class="row">
                    <div class="col-6">
                        <div id="section_title">
                            <h2><?php echo $page_title; ?></h2>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <?php
                            if (!empty($header_action_buttons)) {
                                foreach ($header_action_buttons as $header_button) {
                                    $icon = (!empty($header_button['icon'])) ? $header_button['icon'] : 'fa fa-plus';
                                    $header_button_type = (isset($header_button['type'])) ? $header_button['type'] : 'primary';
                        ?>
                                    <a href="<?php echo $header_button['url']; ?>" class="btn btn-sm btn-<?php echo $header_button_type; ?>" <?php if (!empty($header_button['id'])) { echo 'id="'.$header_button['id'].'"'; } ?>
                                    <?php if (!empty($header_button['data-attributes'])) { foreach($header_button['data-attributes'] as $data_key => $data_value) { ?>
                                        data-<?php echo $data_key; ?>="<?php echo $data_value; ?>" 
                                    <?php } } ?>
                                    >
                                        <i class="<?php echo $icon; ?> fa-small"></i> <?php echo $header_button['label']; ?>
                                    </a>
                        <?php
                                }
                            }
                        ?>
                    </div>
                </div>

                <?php
                    // Flash messages
                    if ($flash->hasMessages()) {
                        echo $flash;
                    }
