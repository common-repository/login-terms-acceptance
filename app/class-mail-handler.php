<?php
/**
 * Handles the email functions for the XLTA plugin.
 *
 * This class is responsible for managing the retrieval of user email,
 * constructing email messages, and sending emails within the XLTA context.
 *
 * @package XLTA
 */

namespace XLTA;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Exception;

/**
 * Class Mail_Handler
 *
 * Handles email-related functionalities such as retrieving the current user's
 * email, constructing the email message, and sending the email.
 *
 * @package XLTA
 */
class Mail_Handler {

	/**
	 * Get the content of the email message.
	 *
	 * This method retrieves the terms content and user signature, and includes
	 * the email template.
	 *
	 * @return false|string The content of the email message, or false on failure.
	 */
	private function get_mail_message() {
		$terms_content  = get_option( 'xlta_terms' );
		$user_signature = $this->get_user_signature();

		ob_start();
			include XLTA_PLUGIN_DIR . 'admin/templates/email-template.php';
		return ob_get_clean();
	}

	/**
	 * Get the user's signature from user meta data.
	 *
	 * @return mixed|string The user's signature, or an empty string if not found.
	 */
	private function get_user_signature() {
		$user_meta = json_decode( get_user_meta( get_current_user_id(), XLTA_ACCEPTANCE_META_KEY, true ), true );
		return $user_meta['user_signature'] ?? '';
	}

	/**
	 * Send an email to the current user.
	 *
	 * This method constructs the email headers, subject, recipient, and message,
	 * and sends the email using WordPress's wp_mail function. It also handles
	 * error logging if the email fails to send or if an exception occurs.
	 *
	 * @return void
	 */
	public function send_mail() {
		try {
			$headers  = array(
				'Content-Type: text/html; charset=UTF-8',
			);
			$subject  = 'Terms And Conditions Acceptance';
			$to_email = wp_get_current_user()->user_email;
			$message  = $this->get_mail_message();

			wp_mail( $to_email, $subject, $message, $headers );

		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'Error sending email: ' . $e->getMessage() );// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
}
