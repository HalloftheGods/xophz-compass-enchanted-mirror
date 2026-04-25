<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.mycompassconsulting.com/getcompass/
 * @since             1.0.0
 * @package           Xophz_Compass_
 *
 * @wordpress-plugin
 * Category:          True North
 * Plugin Name:       Xophz Enchanted Mirror 
 * Plugin URI:        https://github.com/HalloftheGods/xophz-compass-enchanted-mirror
 * Description:       A site audience comparison tool that helps peer into how your site's audience is faring against other sites side-by-side. 
 * Version:           26.4.25.841
 * Author:            Hall of the Gods, Inc.
 * Author URI:        http://www.hallofthegods.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       xophz-compass-enchanted-mirror
 * Domain Path:       /languages
 * Update URI:        https://github.com/HalloftheGods/xophz-compass-enchanted-mirror
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'XOPHZ_COMPASS_ENCHANTED_MIRROR_VERSION', '26.4.25.841' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-xophz-compass-enchanted-mirror-activator.php
 */
function activate_xophz_compass_enchanted_mirror() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-xophz-compass-enchanted-mirror-activator.php';
	Xophz_Compass_Enchanted_Mirror_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-xophz-compass-enchanted-mirror-deactivator.php
 */
function deactivate_xophz_compass_enchanted_mirror() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-xophz-compass-enchanted-mirror-deactivator.php';
	Xophz_Compass_Enchanted_Mirror_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_xophz_compass_enchanted_mirror' );
register_deactivation_hook( __FILE__, 'deactivate_xophz_compass_enchanted_mirror' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-xophz-compass-enchanted-mirror.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_xophz_compass_enchanted_mirror() {
  if ( ! class_exists( 'Xophz_Compass' ) ) {
    add_action( 'admin_init', 'shutoff_xophz_compass_enchanted_mirror' );
    add_action( 'admin_notices', 'admin_notice_xophz_compass_enchanted_mirror' );

    function shutoff_xophz_compass_enchanted_mirror() {
      if ( ! function_exists( 'deactivate_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      deactivate_plugins( plugin_basename( __FILE__ ) );
    }

    function admin_notice_xophz_compass_enchanted_mirror() {
      echo '<div class="error"><h2><strong>Xophz_Compass_Enchanted_Mirror</strong> requires Compass to run. It has self <strong>deactivated</strong>.</h2></div>';
      if ( isset( $_GET['activate'] ) )
        unset( $_GET['activate'] );
    }
  } else {
    $plugin = new Xophz_Compass_Enchanted_Mirror();
    $plugin->run();
  }
  
}
add_action( 'plugins_loaded', 'run_xophz_compass_enchanted_mirror' );
