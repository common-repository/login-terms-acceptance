<?php
/**
 * The plugin singleton class.
 *
 *  This file contains the definition for The plugin singleton class,
 *  which extends the WP_List_Table class to handle custom table functionalities
 *  in the WordPress admin area.
 *
 * @package Login Terms Acceptance
 * @version 1.0.0
 * @since   1.0.0
 * @author  XTND.net
 */

namespace XLTA;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * The main class
 */
class Plugin {

	/**
	 * Class instance.
	 *
	 * @access private
	 * @static
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Constructor method.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function init() {
		$this->include_plugin_classes();
		new Settings();
	}

	/**
	 * Get instance of the class.
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
	}


	/**
	 * Includes plugin classes.
	 *
	 * Includes necessary class files for the plugin.
	 *
	 * @return void
	 */
	public function include_plugin_classes() {
		$class_files = array(
			'class-settings.php',
			'class-mail-handler.php',
			'class-acceptance-state-report.php',
		);

		foreach ( $class_files as $class_file ) {
			include_once XLTA_PLUGIN_DIR . 'app/' . $class_file;
		}
	}

	/**
	 * Handles the plugin deactivation.
	 *
	 * This method is called when the plugin is deactivated.
	 * It deletes the terms acceptance page and the associated option.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$page_id = get_option( XLTA_TERMS_PAGE_ID );
		// If the page ID exists, delete the page and remove the option.
		if ( $page_id ) {
			wp_delete_post( $page_id, true ); // Delete the page permanently.
			delete_option( XLTA_TERMS_PAGE_ID ); // Remove the option.
		}
	}
}
