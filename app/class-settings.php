<?php
/**
 * The plugin Settings class.
 *
 *  This file contains the definition for The plugin Settings class,
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

use WP_Query;

/**
 * Plugin Settings class
 */
class Settings {

	/**
	 * Mail Handler Class instance
	 *
	 * @var Mail_Handler $mailer
	 */
	private $mailer;

	/**
	 * Constructor method.
	 */
	public function __construct() {
		$this->mailer = new Mail_Handler();
		$this->xlta_add_hooks();
	}

	/**
	 * Add required hooks.
	 *
	 * @return void
	 */
	private function xlta_add_hooks() {
		add_action( 'admin_menu', array( $this, 'xlta_add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'xlta_enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'xlta_enqueue_front_assets' ) );
		add_action( 'init', array( $this, 'xlta_create_terms_acceptance_page' ) );
		add_action( 'wp_login', array( $this, 'xlta_user_redirect_after_login' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'xlta_restrict_access_based_on_terms' ) );
		add_action( 'template_redirect', array( $this, 'xlta_restrict_access_based_on_terms' ) );
		add_action( 'admin_init', array( $this, 'xlta_register_settings' ) );
		add_shortcode( 'xlta_terms_acceptance', array( $this, 'xlta_terms_acceptance_form_shortcode' ) );
		add_action( 'init', array( $this, 'xlta_terms_acceptance_actions' ) );
	}

	/**
	 * Add menu elements for plugin
	 *
	 * @return void
	 */
	public function xlta_add_menu() {
		// Main menu page.
		add_menu_page(
			esc_html__( 'Login Terms Acceptance', 'login-terms-acceptance' ),
			esc_html__( 'Login Terms Acceptance', 'login-terms-acceptance' ),
			'manage_options',
			XLTA_HANDLE,
			array( $this, 'xlta_render_settings_page' ),
			'dashicons-format-aside',
			20
		);

		add_submenu_page(
			XLTA_HANDLE,
			esc_html__( 'User\'s Terms Acceptance Report', 'login-terms-acceptance' ),
			esc_html__( 'User\'s Terms Acceptance Report', 'login-terms-acceptance' ),
			'manage_options',
			'xlta-terms-acceptance-report',
			array( $this, 'xlta_render_users_report_table' )
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function xlta_render_settings_page() {
		require XLTA_PLUGIN_DIR . 'admin/templates/settings.php';
	}

	/**
	 * Enqueue plugin assets on the WP Admin Dashboard.
	 *
	 * @return void
	 */
	public function xlta_enqueue_admin_assets() {
		wp_enqueue_style(
			XLTA_HANDLE,
			XLTA_PLUGIN_DIR_URL . 'assets/css/admin.css',
			array(),
			filemtime( XLTA_PLUGIN_DIR . 'assets/css/admin.css' )
		);
	}

	/**
	 * Enqueue plugin assets on the WP front.
	 *
	 * @return void
	 */
	public function xlta_enqueue_front_assets() {
		$terms_acceptance_page_id = get_option( XLTA_TERMS_PAGE_ID );
		$is_terms_page            = is_page( $terms_acceptance_page_id );
		if ( ! $is_terms_page ) {
			return;
		}
		wp_enqueue_style(
			XLTA_HANDLE,
			XLTA_PLUGIN_DIR_URL . 'assets/css/xlta-front.css',
			array(),
			filemtime( XLTA_PLUGIN_DIR . 'assets/css/xlta-front.css' )
		);
	}

	/**
	 * Add Listing Report Table
	 *
	 * @return void
	 */
	public function xlta_render_users_report_table() {
		$acceptance_report = new Acceptance_State_Report();
		$acceptance_report->prepare_items();
		?>
		<div class="xlta-settings-wrapper">
			<div class="xlta-wrap">
				<?php
					$header_text = esc_html__( 'User\'s Terms Acceptance Report', 'login-terms-acceptance' );
					require XLTA_PLUGIN_DIR . 'admin/templates/partials/header.php';
				?>
				<div class="xlta-inner-wrap">
					<form method="GET">
						<?php $acceptance_report->search_box( 'Search users', 'user-search' ); ?>
						<?php $acceptance_report->display(); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register Settings Options
	 *
	 * @return void
	 */
	public function xlta_register_settings() {
		register_setting( 'xlta_settings_group', 'xlta_terms' );

		$roles = get_editable_roles();
		foreach ( $roles as $role_name => $role_info ) {
			$role_id = esc_attr( $role_name );
			register_setting( 'xlta_settings_group', 'xlta_assigned_terms_' . $role_id );
		}
	}

	/**
	 * Used to redirect users After Login if it's rule is restricted and terms not accepted
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return void
	 */
	public function xlta_user_redirect_after_login( $user_login, $user ) {

		if ( $this->xlta_is_role_restricted( $user ) && ! $this->xlta_is_terms_accepted( $user ) ) {

			wp_safe_redirect( $this->xlta_get_terms_page_redirect_url() );
			exit;
		}

		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Restrict User Access Based on Terms Acceptance
	 *
	 * This function checks if a logged-in user has accepted the terms and conditions. If the user has not accepted the terms,
	 * they will be redirected to the terms page. If they have accepted the terms and try to access the terms page again,
	 * they will be redirected to the admin dashboard. Additionally, non-restricted users and visitors trying to access the
	 * terms page will be redirected accordingly.
	 *
	 * @return void
	 */
	public function xlta_restrict_access_based_on_terms() {
		$terms_acceptance_page_id = get_option( XLTA_TERMS_PAGE_ID );
		$is_terms_page            = is_page( $terms_acceptance_page_id );
		if ( is_user_logged_in() ) {
			if ( $this->xlta_is_role_restricted() ) {
				if ( ! $this->xlta_is_terms_accepted() && ! $is_terms_page ) {
					wp_safe_redirect( $this->xlta_get_terms_page_redirect_url() );
					exit;
				} elseif ( $this->xlta_is_terms_accepted() && $is_terms_page ) {
					wp_safe_redirect( admin_url() );
					exit;
				}
			} elseif ( $is_terms_page ) {
				wp_safe_redirect( admin_url() );
				exit;
			}
		} elseif ( $is_terms_page ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}


	/**
	 * Check if user role is restricted
	 *
	 * This function checks if the specified user's role is restricted based on an option value.
	 * If no user is specified, it checks the current logged-in user.
	 *
	 * @param WP_User|null $user Optional. The user object. Defaults to the current logged-in user.
	 * @return bool True if the user's role is restricted, false otherwise.
	 */
	private function xlta_is_role_restricted( $user = null ) {
		if ( ! $user ) {
			$user = wp_get_current_user();
		}
		$user_role = $user->roles[0];

		$xlta_assigned_terms = (bool) get_option( 'xlta_assigned_terms_' . $user_role );
		if ( true === $xlta_assigned_terms ) {
			return true;
		}

		return false;
	}


	/**
	 * Check if user has accepted terms
	 *
	 * This function checks if the specified user has accepted the terms and conditions.
	 * If no user ID is specified, it checks the current logged-in user.
	 *
	 * @param int|null $user_id Optional. The user ID. Defaults to the current logged-in user ID.
	 * @return mixed The acceptance state if it exists, false otherwise.
	 */
	private function xlta_is_terms_accepted( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$acceptance_state = json_decode( get_user_meta( $user_id, XLTA_ACCEPTANCE_META_KEY, true ), true );

		if ( null !== $acceptance_state ) {
			return $acceptance_state['acceptance_state'];
		}

		return false;
	}


	/**
	 * Create Terms Acceptance Page
	 *
	 * This function creates a new page for terms acceptance if it does not already exist.
	 * The page will use a shortcode `[terms_acceptance]` to display the terms acceptance form.
	 * The page ID is then stored in the WordPress options table for future reference.
	 *
	 * @return void
	 */
	public function xlta_create_terms_acceptance_page() {
		$page_title   = XLTA_TERMS_PAGE_TITLE;
		$page_content = '[xlta_terms_acceptance]';

		$args = array(
			'post_type'      => 'page',
			'title'          => $page_title,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);

		$page_check = new WP_Query( $args );

		if ( ! $page_check->have_posts() ) {
			// Create post object.
			$new_page = array(
				'post_title'   => $page_title,
				'post_content' => $page_content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			);

			// Insert the post into the database.
			$page_id = wp_insert_post( $new_page );

			// Store the page ID in the database.
			update_option( XLTA_TERMS_PAGE_ID, $page_id );
		}
	}


	/**
	 * Terms Acceptance Form Shortcode
	 *
	 * Generates the HTML output for the terms acceptance form using a shortcode.
	 * The form is included from a template file located in the plugin's directory.
	 *
	 * @return string The HTML output of the terms acceptance form.
	 */
	public function xlta_terms_acceptance_form_shortcode() {
		ob_start();
		?>
		<?php
		require XLTA_PLUGIN_DIR . 'frontend/templates/terms-acceptance-page.php';
		?>
		<?php

		return ob_get_clean();
	}


	/**
	 * Handle Terms Acceptance Actions
	 *
	 * Processes the form submission for accepting terms and conditions.
	 * If the terms are accepted, it updates the user's metadata, sends a confirmation email,
	 * and then redirects the user to the admin dashboard. If the user chooses to stay logged out,
	 * they are logged out and redirected to the homepage.
	 *
	 * @return void
	 */
	public function xlta_terms_acceptance_actions() {
		if ( isset( $_POST['xlta_terms_action'] ) &&
			isset( $_POST['accept_terms_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['accept_terms_nonce'] ) ), 'accept_terms_action' )
		) {
			if ( isset( $_POST['xlta_accept_terms'] ) ) {
				$data = array(
					'user_signature'   => sanitize_text_field( wp_unslash( $_POST['user_name'] ?? '' ) ),
					'acceptance_state' => true,
					'acceptance_date'  => gmdate( get_option( 'date_format' ) ),
				);

				update_user_meta( get_current_user_id(), XLTA_ACCEPTANCE_META_KEY, wp_json_encode( $data ) );

				$this->mailer->send_mail();

				wp_safe_redirect( admin_url() );
				exit;

			} elseif ( isset( $_POST['xlta_stay_logged_out'] ) ) {
				wp_logout();

				wp_safe_redirect( home_url() );
				exit;
			}
		}
	}

	/**
	 * Retrieves the URL of the terms acceptance page.
	 *
	 * This method checks if the XLTA_TERMS_PAGE_ID option is set,
	 * and returns the permalink for that page. If the option is not set,
	 * it defaults to the home URL.
	 *
	 * @return string The URL of the terms acceptance page or the home URL if not set.
	 */
	private function xlta_get_terms_page_redirect_url() {
		$page_id = get_option( XLTA_TERMS_PAGE_ID );

		if ( $page_id ) {
			return get_permalink( $page_id );
		}

		return home_url();
	}
}