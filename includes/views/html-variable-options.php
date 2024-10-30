<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div>
  <?php
	woocommerce_wp_text_input( array(
		'id' => 'wc_ebiz_mennyisegi_egyseg[' . $loop . ']',
		'label' => __('Mennyiségi egység', 'wc-ebiz'),
    'placeholder' => __('db', 'wc-ebiz'),
    'desc_tip' => true,
		'value' => esc_attr(get_post_meta( $variation->ID, 'wc_ebiz_mennyisegi_egyseg', true )),
		'description' => __('Az OTP eBiz által generált számlán a tételnél feltüntetett mennyiségi egység. Az alapértelmezett értéket a bővítmény beállításokban tudod beállítani.', 'wc-ebiz')
	));

	woocommerce_wp_text_input( array(
		'id' => 'wc_ebiz_megjegyzes[' . $loop . ']',
		'label' => __('Tétel megjegyzés', 'wc-ebiz'),
    'desc_tip' => true,
		'value' => esc_attr(get_post_meta( $variation->ID, 'wc_ebiz_megjegyzes', true )),
		'description' => __('Az OTP eBiz által generált számlán a tételnél feltüntetett megjegyzés.', 'wc-ebiz')
	));

	if(get_option('_wc_ebiz_pro_enabled')) {
		woocommerce_wp_text_input( array(
			'id' => 'wc_ebiz_tetel_nev[' . $loop . ']',
			'label' => __('Tétel elnevezés', 'wc-ebiz'),
			'desc_tip' => true,
			'value' => esc_attr(get_post_meta( $variation->ID, 'wc_ebiz_tetel_nev', true )),
			'description' => __('Itt módosíthatod a tétel nevét, ha mást szeretnél a számlára írni, mint a termék neve.', 'wc-ebiz')
		));
	}
  ?>
</div>
