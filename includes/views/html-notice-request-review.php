<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-ebiz-notice wc-ebiz-request-review">
	<p>⭐️ <?php printf( __( 'Szia! Tetszik a %sWooCommerce OTP eBiz%s bővítmény? Kérlek, értékeld a WordPress.org-on. Csak egy perc az egész. Köszönöm!', 'wc-ebiz' ), '<strong>', '</strong>' ); ?></p>
	<p>
		<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/integration-for-ebiz-woocommerce/reviews/?filter=5#new-post"><?php esc_html_e( 'Igen, értékelem!', 'wc-ebiz' ); ?></a>
		<a class="button-secondary wc-ebiz-hide-notice remind-later" data-nonce="<?php echo wp_create_nonce( 'wc-ebiz-hide-notice' )?>" data-notice="request_review" href="#"><?php esc_html_e( 'Emlékeztess később', 'wc-ebiz' ); ?></a>
		<a class="button-secondary wc-ebiz-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-ebiz-hide-notice' )?>" data-notice="request_review" href="#"><?php esc_html_e( 'Nem, köszi', 'wc-ebiz' ); ?></a>
	</p>
</div>
