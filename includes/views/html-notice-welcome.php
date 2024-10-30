<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-ebiz-notice wc-ebiz-welcome">
	<div class="wc-ebiz-welcome-body">
    <button type="button" class="notice-dismiss wc-ebiz-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-ebiz-hide-notice' )?>" data-notice="welcome"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss' ); ?></span></button>
		<h2>WooCommerce + OTP eBiz PRO</h2>
		<p>Köszönöm, hogy telepítetted a bővítményt. Ha esetleg nem tudnád, van egy PRO verziója is, amivel sokkal több funkciót érhetsz el, például automata számlakészítés, díjbekérő készítés, teljesítettnek jelölés és 1 éves support. A bővítmény használatához a beállításokban add meg a számlázással kapcsolatos adataidat.</p>
		<p>
			<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://szamlazz.visztpeter.me/ebiz"><?php esc_html_e( 'PRO verzió vásárlása', 'woocommerce' ); ?></a>
			<a class="button-secondary" href="<?php echo admin_url( wp_nonce_url('admin.php?page=wc-settings&tab=integration&section=wc_ebiz&welcome=1', 'wc-ebiz-hide-notice' ) ); ?>"><?php esc_html_e( 'Beállítások', 'woocommerce' ); ?></a>
		</p>
	</div>
</div>
