<?php
/**
 * Plugin Name: Login Terms Acceptance
 * Description: Restrict access for selected user roles unless they accept the Terms and Conditions. This plugin ensures users acknowledge and accept your terms before they can fully access the dashboard or the site.
 * Plugin URI:
 * Author: XTND
 * Author URI: https://xtnd.net
 * Version: 1.0.0
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package Login Terms Acceptance
 */

namespace XLTA;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'XLTA_PLUGIN_FILE', __FILE__ );
define( 'XLTA_PLUGIN_DIR', plugin_dir_path( XLTA_PLUGIN_FILE ) );
define( 'XLTA_BASENAME', plugin_basename( XLTA_PLUGIN_FILE ) );
define( 'XLTA_PLUGIN_DIR_URL', plugin_dir_url( XLTA_PLUGIN_FILE ) );
define( 'XLTA_HANDLE', 'xlta-login-terms-acceptance' );
define( 'XLTA_TERMS_PAGE_TITLE', 'Login Terms Acceptance' );
define( 'XLTA_TERMS_PAGE_ID', 'xlta_terms_acceptance_page_id' );
define( 'XLTA_ACCEPTANCE_META_KEY', XLTA_HANDLE );

require XLTA_PLUGIN_DIR . 'app/class-plugin.php';

add_action( 'plugins_loaded', array( Plugin::class, 'get_instance' ), -10 );

register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );
