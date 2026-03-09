<?php
/**
 * Plugin Name: Simple Mailer
 * Plugin URI:  https://wumetax.com
 * Description: 輕量 WordPress 郵件外掛，支援 Resend API、Brevo API、SMTP，含郵件紀錄
 * Version:     1.0.0
 * Author:      wumetax
 * License:     GPL-2.0+
 * Text Domain: simple-mailer
 */

defined( 'ABSPATH' ) || exit;

define( 'SIMPLE_MAILER_VERSION', '1.0.0' );
define( 'SIMPLE_MAILER_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_MAILER_URL', plugin_dir_url( __FILE__ ) );

require_once SIMPLE_MAILER_PATH . 'includes/class-logger.php';
require_once SIMPLE_MAILER_PATH . 'includes/mailer/class-resend.php';
require_once SIMPLE_MAILER_PATH . 'includes/mailer/class-brevo.php';
require_once SIMPLE_MAILER_PATH . 'includes/mailer/class-smtp.php';
require_once SIMPLE_MAILER_PATH . 'includes/class-mailer.php';
require_once SIMPLE_MAILER_PATH . 'includes/class-settings.php';

register_activation_hook( __FILE__, array( 'Simple_Mailer_Logger', 'create_table' ) );

add_action( 'plugins_loaded', function () {
    new Simple_Mailer_Settings();
    new Simple_Mailer_Core();
} );
