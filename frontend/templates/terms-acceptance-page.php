<?php
/**
 * File Name: terms-acceptance-page.php
 *
 * Description: Login Terms Acceptance Page With Form To Accept Terms
 *
 * @package Login Terms Acceptance
 * @author XTND.net
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="xlta-terms-conditions-wrapper">
	<div class="xlta-wrap">
		<p class="xlta-header-warning">
			<?php
			$header = __( 'You <span class="xlta-must-word">MUST</span> accept terms to be able to continue to dashboard or the site', 'login-terms-acceptance' );
			echo wp_kses_post( $header );
			?>
		</p>
		<hr>
		<div class="xlta-terms-and-conditions">
			<?php
			$terms_content = get_option( 'xlta_terms' );

			if ( ! empty( $terms_content ) ) {
				echo wp_kses_post( $terms_content );
			} else {
				esc_html_e( 'No terms and conditions found.', 'login-terms-acceptance' );
			}
			?>
		</div>

		<!-- Form for accepting terms -->
		<form class="xlta-accept-terms-form" method="post" action="">
			<input type="hidden" name="xlta_terms_action">
			<input type="hidden" name="accept_terms_nonce" value="<?php echo esc_attr( wp_create_nonce( 'accept_terms_action' ) ); ?>">
			<label for="user_name"><?php esc_html_e( 'Your signature pre-populated', 'login-terms-acceptance' ); ?></label>
			<input type="text" id="user_name" name="user_name"
					value="<?php echo esc_html( wp_get_current_user()->display_name ); ?>" required>
			<div class="xlta-submit-button-container">
				<input type="submit" name="xlta_stay_logged_out" class="xlta-reject-terms-button button button-secondary"
						value="Stay Logged Out">
				<input type="submit" name="xlta_accept_terms" class="xlta-accept-terms-button button button-primary"
						value="Accept Terms">
			</div>
		</form>
	</div>
</div>

