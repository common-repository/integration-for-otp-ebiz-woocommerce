<?php
/**
 * Admin View: Notice - Welcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-error wc-ebiz-notice">
	<button type="button" class="notice-dismiss wc-ebiz-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-ebiz-hide-notice' )?>" data-notice="debug"><span class="screen-reader-text"><?php _e( 'Dismiss' ); ?></span></button>
	<p>A <strong>WooCommerce + OTP eBiz</strong> bővítmény jelenleg teszt üzemmódban van, tehát éles használatra még nem használható. Hamarosan elérhető lesz a teljes verzió.</p>
</div>
