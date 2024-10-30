<?php
/**
 * File Name: email-template.php
 *
 * Description: Email Template page
 *
 * @package Login Terms Acceptance
 * @author XTND.net
 * @version 1.0.0
 *
 * @var string $terms_content
 * @var string $user_signature
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Terms and Conditions Accepted', 'login-terms-acceptance' ); ?></title>
</head>
<body>
<div style="font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; width: 80%; margin: 20px auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 10px; overflow: hidden; box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1);">
	<div style="background-color: #0073aa; color: #ffffff; padding: 20px 0; text-align: center;">
		<h1 style="margin: 0;"><?php esc_html_e( 'Terms and Conditions Accepted', 'login-terms-acceptance' ); ?></h1>
	</div>
	<div style="padding: 20px;">
		<?php
		$user_name   = wp_get_current_user()->display_name;
		$mail_header = sprintf( 'Hello %s', $user_name );
		?>
		<p><?php echo esc_html( $mail_header ); ?>,</p>
		<p><?php esc_html_e( 'You have successfully agreed to our Terms and Conditions:', 'login-terms-acceptance' ); ?></p>
		<p style="font-size: 18px; color: #0073aa;"><?php echo esc_html( $terms_content ); ?></p>
	</div>
	<div style="padding: 20px; text-align: right; background-color: #ffffff; border-top: 1px solid #e5e5e5;">
		<p style="margin: 0; font-size: 14px; color: #000;">
			<?php esc_html_e( 'Your Signature,', 'login-terms-acceptance' ); ?><br><?php echo esc_html( $user_signature ); ?>
		</p>
	</div>
</div>
</body>
</html>
