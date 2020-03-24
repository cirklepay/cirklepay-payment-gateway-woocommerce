<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.alaksns.com/
 * @since      1.0.0
 *
 * @package    Cirkle_Pay
 * @subpackage Cirkle_Pay/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Cirkle_Pay
 * @subpackage Cirkle_Pay/includes
 * @author     Muhammad Umer Naeem <umerkarzansoftb@gmail.com>
 */
class Cirkle_Pay_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'cirkle-pay',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
