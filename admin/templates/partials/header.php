<?php
/**
 * File Name: header.php
 *
 * Description: Login Terms Acceptance Settings admin pages header
 *
 * @package Login Terms Acceptance
 * @version 1.0.0
 *
 * @var string $header_text
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="xlta-header-wrap">
	<img src="<?php echo esc_url( XLTA_PLUGIN_DIR_URL . 'assets/images/xtnd-logo.svg' ); ?>" alt="Xtnd logo">
	<h1><?php echo esc_html( $header_text ); ?></h1>
</div>
