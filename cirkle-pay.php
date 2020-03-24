<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.linkedin.com/in/umernaeem217
 * @since             1.0.0
 * @package           Cirkle_Pay
 *
 * @wordpress-plugin
 * Plugin Name:       CirklePay Payment Gateway
 * Plugin URI:        https://www.cirklepay.com/
 * Description:       The easiest way to accept, process and disburse digital payments for businesses.
 * Version:           1.0.0
 * Author:            Muhammad Umer Naeem
 * Author URI:        https://www.linkedin.com/in/umernaeem217
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cirkle-pay
 * Domain Path:       /languages
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
define( 'CIRKLE_PAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cirkle-pay-activator.php
 */
function activate_cirkle_pay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cirkle-pay-activator.php';
	Cirkle_Pay_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cirkle-pay-deactivator.php
 */
function deactivate_cirkle_pay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cirkle-pay-deactivator.php';
	Cirkle_Pay_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cirkle_pay' );
register_deactivation_hook( __FILE__, 'deactivate_cirkle_pay' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cirkle-pay.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cirkle_pay() {

	$plugin = new Cirkle_Pay();
	$plugin->run();

}
run_cirkle_pay();
