<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="options_group hide_if_variable hide_if_grouped">
	<h4 style="padding-left:12px;">OTP eBiz számla beállítások</h4>
  <?php
  woocommerce_wp_text_input(array(
    'id' => 'wc_ebiz_mennyisegi_egyseg',
    'label' => __('Mennyiségi egység', 'wc-ebiz'),
    'placeholder' => __('db', 'wc-ebiz'),
    'desc_tip' => true,
    'value' => esc_attr( $post->wc_ebiz_mennyisegi_egyseg ),
    'description' => __('Az eBiz által generált számlán a tételnél feltüntetett mennyiségi egység. Az alapértelmezett értéket a bővítmény beállításokban tudod beállítani.', 'wc-ebiz')
  ));
  ?>
  <?php
  woocommerce_wp_text_input(array(
    'id' => 'wc_ebiz_megjegyzes',
    'label' => __('Tétel megjegyzés', 'wc-ebiz'),
    'desc_tip' => true,
    'value' => esc_attr( $post->wc_ebiz_megjegyzes ),
    'description' => __('Az eBiz által generált számlán a tételnél feltüntetett megjegyzés.', 'wc-ebiz')
  ));
  ?>
	<?php
	if(get_option('_wc_ebiz_pro_enabled')) {
		woocommerce_wp_text_input(array(
			'id' => 'wc_ebiz_tetel_nev',
			'label' => __('Tétel elnevezés', 'wc-ebiz'),
			'desc_tip' => true,
			'value' => esc_attr( $post->wc_ebiz_tetel_nev ),
			'description' => __('Itt módosíthatod a tétel nevét, ha mást szeretnél a számlára írni, mint a termék neve.', 'wc-ebiz')
		));
	}
	?>
</div>
