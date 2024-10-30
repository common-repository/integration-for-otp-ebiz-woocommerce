<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-error wc-ebiz-notice wc-ebiz-welcome">
	<div class="wc-ebiz-welcome-body">
    <button type="button" class="notice-dismiss wc-ebiz-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-ebiz-hide-notice' )?>" data-notice="error"><span class="screen-reader-text"><?php _e( 'Dismiss' ); ?></span></button>
		<h2>Sikertelen számlakészítés</h2>
		<p><?php printf( esc_html__( 'A #%s sorszámú rendeléshez tartozó számlát nem sikerült valamiért létrehozni automatikusan. A rendelés jegyzetekben látod a pontos hibát.', 'wc-ebiz' ), $order_number ); ?></p>
		<p>
			<a class="button-secondary" href="<?php echo esc_attr($order_link); ?>"><?php esc_html_e( 'Rendelés adatai', 'wc-ebiz' ); ?></a>
		</p>
	</div>
</div>
