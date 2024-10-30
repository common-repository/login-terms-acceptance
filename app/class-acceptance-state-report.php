<?php
/**
 * Class Acceptance_State_Report
 *
 * This file contains the definition for the Acceptance_State_Report class,
 * which extends the WP_List_Table class to handle custom table functionalities
 * in the WordPress admin area.
 *
 * @package Login Terms Acceptance
 * @version 1.0.0
 * @since   1.0.0
 * @author  XTND.net
 */

namespace XLTA;

use WP_List_Table;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// WP_List_Table is not loaded automatically so we need to load it in our application.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Acceptance_State_Report
 *
 * A custom table for displaying acceptance state reports in WordPress admin.
 */
class Acceptance_State_Report extends WP_List_Table {

	/**
	 * Constructor method.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'user',
				'plural'   => 'users',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->process_bulk_action();

		$filters             = $this->get_filter_params();
		$terms_status_filter = $filters['terms_status_filter'];
		$role_filter         = $filters['role_filter'];
		$search              = $filters['search'];
		$order_by            = $filters['order_by'];
		$order               = $filters['order'];

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_users_count( $search, $terms_status_filter, $role_filter );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->get_users( $order_by, $order, $per_page, $current_page, $search, $terms_status_filter, $role_filter );
	}

	/**
	 * Outputs extra navigation elements (filters) above or below the table.
	 *
	 * This function adds custom dropdown filters for Acceptance Status and User Role to the table navigation.
	 * The filters allow the user to filter the table content based on whether the terms were accepted and by user role.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$filters             = $this->get_filter_params();
			$terms_status_filter = $filters['terms_status_filter'];
			$role_filter         = $filters['role_filter'];

			wp_nonce_field( 'filter_terms_nonce_action', 'filter_terms_nonce', false, true );
			?>
			<div class="alignleft actions">
					<input type="hidden" name="page" value="xlta-terms-acceptance-report" />
					<select name="terms_status" id="filter-by-terms-status">
						<option value=""><?php esc_html_e( 'Acceptance Status', 'login-terms-acceptance' ); ?></option>
						<option value="accepted" <?php selected( $terms_status_filter, 'accepted' ); ?>><?php esc_html_e( 'Accepted', 'login-terms-acceptance' ); ?></option>
						<option value="not_accepted" <?php selected( $terms_status_filter, 'not_accepted' ); ?>><?php esc_html_e( 'Not Accepted', 'login-terms-acceptance' ); ?></option>
					</select>
					<select name="user_role" id="user-role">
						<option value=""><?php esc_html_e( 'Select Role', 'login-terms-acceptance' ); ?></option>
						<?php
						$roles = get_editable_roles();
						foreach ( $roles as $role_slug => $role_data ) {
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( $role_slug ),
								selected( $role_filter, $role_slug, false ),
								esc_html( $role_data['name'] )
							);
						}
						?>
					</select>
					<?php
					submit_button( __( 'Filter', 'login-terms-acceptance' ), '', 'terms_filter_action', false );
					?>
			</div>
			<?php
		}
	}

	/**
	 * Retrieves the filter parameters for terms and roles from the GET request.
	 *
	 * This function checks if the filtering action is set in the GET request,
	 * verifies the nonce for security, and sanitizes the input values for
	 * terms status and user role. If the nonce is not valid, it returns
	 * empty values for the filters.
	 *
	 * @return array An associative array containing:
	 *               - 'terms_status_filter' (string): The sanitized terms status filter.
	 *               - 'role_filter' (string): The sanitized user role filter.
	 */
	private function get_filter_params() {
		$terms_status_filter = '';
		$role_filter         = '';
		$search              = '';
		$order_by            = '';
		$order               = '';

		if ( isset( $_GET['filter_terms_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['filter_terms_nonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'filter_terms_nonce_action' ) ) {
				return array(
					'terms_status_filter' => $terms_status_filter,
					'role_filter'         => $role_filter,
					'search'              => $search,
					'orderby'             => $order_by,
					'order'               => $order,
				);
			}

			$terms_status_filter = isset( $_GET['terms_status'] ) ? sanitize_text_field( wp_unslash( $_GET['terms_status'] ) ) : '';
			$role_filter         = isset( $_GET['user_role'] ) ? sanitize_text_field( wp_unslash( $_GET['user_role'] ) ) : '';
			$search              = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$order_by            = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
			$order               = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';
		}

		if ( ! empty( $_REQUEST['_wp_http_referer'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {

			// Remove '_wp_http_referer' from the URL to keep the query string shorter.
			$cleaned_url = remove_query_arg( '_wp_http_referer', esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

			// Redirect to the cleaned URL.
			wp_safe_redirect( $cleaned_url );
			exit;
		}

		return array(
			'terms_status_filter' => $terms_status_filter,
			'role_filter'         => $role_filter,
			'search'              => $search,
			'order_by'            => $order_by,
			'order'               => $order,
		);
	}



	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'                => '<input type="checkbox" />',
			'username'          => 'Username',
			'name'              => 'Name',
			'email'             => 'Email',
			'role'              => 'Role',
			'acceptance_status' => 'Acceptance Status',
		);
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {
		return array(
			'username'          => array( 'user_login', false ),
			'name'              => array( 'display_name', false ),
			'email'             => array( 'user_email', false ),
			'role'              => array( 'roles', false ),
			'acceptance_status' => array( 'acceptance_status', false ),
		);
	}

	/**
	 * Get the table data
	 *
	 * Retrieves the data for the table based on pagination parameters.
	 *
	 * @param string $order_by Order by parameter.
	 * @param string $order Order type ASC or DESC.
	 * @param int    $per_page The number of items to display per page.
	 * @param int    $page_number The current page number.
	 * @param string $search The current search string.
	 * @param string $terms_status_filter The current terms status filter.
	 * @param string $role_filter The current role filter.
	 * @return array The data for the table.
	 */
	private function get_users( $order_by = 'user_login', $order = 'asc', $per_page = 10, $page_number = 1, $search = '', $terms_status_filter = '', $role_filter = '' ) {

		$args = array(
			'number'  => $per_page,
			'offset'  => ( $page_number - 1 ) * $per_page,
			'orderby' => $order_by,
			'order'   => $order,
		);

		$args = $this->prepare_filtering_args( $args, $search, $terms_status_filter, $role_filter );

		$users = get_users( $args );

		$data = array();

		foreach ( $users as $user ) {
			$acceptance_data = $this->get_acceptance_data( $user );
			$profile_url     = get_edit_user_link( $user->ID );

			$data[] = array(
				'ID'                => $user->ID,
				'user_login'        => '<a href="' . esc_url( $profile_url ) . '">' . esc_html( $user->user_login ) . '</a>',
				'display_name'      => '<a href="' . esc_url( $profile_url ) . '">' . esc_html( $user->display_name ) . '</a>',
				'user_email'        => '<a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a>',
				'roles'             => implode( ', ', $user->roles ),
				'acceptance_status' => $acceptance_data,
			);
		}
		return $data;
	}


	/**
	 * Retrieves acceptance data for a given user.
	 *
	 * This method fetches the acceptance data from user meta and formats it.
	 * If no data is found or the data is not in the expected format,
	 * it returns a 'Not Accepted Yet' message.
	 *
	 * @param object $user User Object.
	 * @return array|string Formatted acceptance data or a message indicating acceptance status.
	 */
	private function get_acceptance_data( $user ) {
		$acceptance_data = get_user_meta( $user->ID, XLTA_ACCEPTANCE_META_KEY, true );
		$acceptance_data = json_decode( $acceptance_data, true );

		if ( empty( $acceptance_data ) || ! is_array( $acceptance_data ) ) {
			return 'Not Accepted Yet';
		}

		$formatted_data = array(
			'Status'          => isset( $acceptance_data['acceptance_state'] ) ? 'true' : 'Not Specified',
			'Signature'       => $acceptance_data['user_signature'] ?? 'Not Provided',
			'Acceptance Date' => $acceptance_data['acceptance_date'] ?? 'Not Provided',
		);

		return implode(
			'<br>',
			array_map(
				function ( $key, $value ) {
					return "$key: $value";
				},
				array_keys( $formatted_data ),
				$formatted_data
			)
		);
	}




	/**
	 * Get total users count
	 *
	 * @param string $search The current search string.
	 * @param string $terms_status_filter The current acceptance status filter.
	 * @param string $role_filter The current role filter.
	 * @return int
	 */
	private function get_users_count( $search = '', $terms_status_filter = '', $role_filter = '' ) {
		$args = array(
			'count_total' => true,
		);

		$args = $this->prepare_filtering_args( $args, $search, $terms_status_filter, $role_filter );

		$users_query = new WP_User_Query( $args );
		return $users_query->get_total();
	}

	/**
	 * Prepare the arguments for retrieving users based on search and filter criteria.
	 *
	 * This method builds the arguments for querying users based on the provided
	 * search term, terms acceptance status, and user role filter. It modifies
	 * the passed $args array to include search and meta query parameters as needed.
	 *
	 * @param array  $args               The arguments array to be modified for the user query.
	 * @param string $search             The search term to filter users by (user_login, user_email, display_name).
	 * @param string $terms_status_filter The filter for terms acceptance status ('accepted' or 'not_accepted').
	 * @param string $role_filter         The user role to filter users by.
	 *
	 * @return array                      This method returns an array.
	 */
	private function prepare_filtering_args( $args, $search, $terms_status_filter, $role_filter ) {
		if ( ! empty( $search ) ) {
			$args['search']         = '*' . esc_attr( $search ) . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		if ( 'accepted' === $terms_status_filter ) {

            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query'] = array(
				array(
					'key'     => XLTA_ACCEPTANCE_META_KEY,
					'value'   => '',
					'compare' => '!=',
				),
			);
		} elseif ( 'not_accepted' === $terms_status_filter ) {

            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query'] = array(
				array(
					'key'     => XLTA_ACCEPTANCE_META_KEY,
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( ! empty( $role_filter ) ) {
			$args['role'] = $role_filter;
		}
		return $args;
	}


	/**
	 * Define what data to show on each column of the table
	 *
	 * @param array  $item Data.
	 * @param String $column_name - Current column name.
	 *
	 * @return Mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'username':
				return $item['user_login'];
			case 'name':
				return $item['display_name'];
			case 'email':
				return $item['user_email'];
			case 'role':
				return $item['roles'];
			case 'acceptance_status':
				return $item['acceptance_status'];
			default:
				return __( 'N/A', 'login-terms-acceptance' );
		}
	}

	/**
	 * Adds a checkbox for bulk actions
	 *
	 * @param Object $item Data.
	 * @return String
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="users[]" value="%s" />', $item['ID'] );
	}

	/**
	 * Define the bulk actions
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'drop_consent' => 'Drop Consent',
		);
	}

	/**
	 * Process bulk actions on users.
	 *
	 * This function handles the bulk actions submitted through the form, performing security checks,
	 * validating user input, and executing the appropriate action for each selected user.
	 */
	public function process_bulk_action() {
		// Security check: Verify the nonce to ensure the request is valid.
		if ( ! empty( $_POST['_wpnonce'] ) ) {
			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			// If the nonce is invalid, terminate the process.
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				return;
			}
		}

		// Retrieve and sanitize user IDs from the GET request, or return if none are provided.
		$user_ids = isset( $_GET['users'] ) && is_array( $_GET['users'] ) ? array_map( 'absint', $_GET['users'] ) : array();

		if ( empty( $user_ids ) ) {
			return;
		}

		// Loop over the user IDs and perform the appropriate bulk action.
		foreach ( $user_ids as $user_id ) {
			// If the action is 'drop_consent', delete the consent meta data for the user.
			if ( 'drop_consent' === $this->current_action() ) {
				delete_user_meta( $user_id, XLTA_ACCEPTANCE_META_KEY );
			}
		}
	}
}
