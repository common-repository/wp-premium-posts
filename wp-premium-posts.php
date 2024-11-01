<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
/*
  Plugin Name: WP Premium Posts
  Plugin URI: https://mostasharoon.org
  Description: WP Premium Posts allows you to go beyond the frontend posting. You can charge for anything related to posting on your website!
  Version: 1.1.1
  Author: Mohammed Thaer
  Author URI: https://mostasharoon.org
  Text Domain: wpp
 */

define( 'WPP_VERSION', '1.1.1' );

// Check if Tajer is active. If not display warning message and don't do anything
add_action( 'plugins_loaded', 'wpp_tajer_checker' );
function wpp_tajer_checker() {
	if ( ! defined( 'TAJER_VERSION' ) ) {
		add_action( 'admin_notices', 'wpp_no_tajer_warning' );

		return false;
		//Check if Tajer is old
	} elseif ( version_compare( TAJER_VERSION, '1.0', '<' ) ) {
		add_action( 'admin_notices', 'wpp_old_tajer_warning' );

		return false;
	}

	return true;
}

// Check if FMS is active. If not display warning message and don't do anything
add_action( 'plugins_loaded', 'wpp_fms_checker' );
function wpp_fms_checker() {
	if ( ! defined( 'FMS_VERSION' ) ) {
		add_action( 'admin_notices', 'wpp_no_fms_warning' );

		return false;
		//Check if FMS is old
	} elseif ( version_compare( FMS_VERSION, '2.0.4', '<' ) ) {
		add_action( 'admin_notices', 'wpp_old_fms_warning' );

		return false;
	}

	return true;
}

function wpp_no_tajer_warning() {
	?>
	<div class="message error">
		<p><?php printf( __( 'WP Premium Posts is enabled but not effective. It requires <a href="%s">Tajer</a> in order to work.', 'wpp' ),
				'https://mostasharoon.org' ); ?></p></div>
	<?php
}

function wpp_no_fms_warning() {
	?>
	<div class="message error">
		<p><?php printf( __( 'WP Premium Posts is enabled but not effective. It requires <a href="%s">FMS</a> in order to work.', 'wpp' ),
				'https://mostasharoon.org/forms-management-system/' ); ?></p></div>
	<?php
}

function wpp_old_tajer_warning() {
	?>
	<div class="message error">
		<p><?php printf( __( 'WP Premium Posts is enabled but not effective. It is not compatible with  <a href="%s">Tajer</a> versions prior 1.0.', 'wpp' ),
				'https://mostasharoon.org' ); ?></p></div>
	<?php
}

function wpp_old_fms_warning() {
	?>
	<div class="message error">
		<p><?php printf( __( 'WP Premium Posts is enabled but not effective. It is not compatible with  <a href="%s">FMS</a> versions prior 2.0.4.', 'wpp' ),
				'https://mostasharoon.org/forms-management-system/' ); ?></p></div>
	<?php
}

// Dir to the plugin
define( 'WPP_DIR', plugin_dir_path( __FILE__ ) );
// URL to the plugin
define( 'WPP_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'wpp_load_translation' );

/**
 *Loads a translation files.
 */
function wpp_load_translation() {
	load_plugin_textdomain( 'wpp', false, 'wp-premium-posts/languages' );
}


require_once( 'classes/class-wpp-fms-part.php' );
require_once( 'classes/class-wpp-tajer-part.php' );
require_once( 'includes/wpp-functions.php' );
require_once( 'includes/wpp-cron.php' );

register_activation_hook( __FILE__, 'wpp_register_cron_jobs' );
