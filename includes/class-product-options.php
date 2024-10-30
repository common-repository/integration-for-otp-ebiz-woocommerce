<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_eBiz_Product_Options', false ) ) :

	class WC_eBiz_Product_Options {

		//Init notices
		public static function init() {
			add_action('woocommerce_product_options_advanced', array( __CLASS__, 'product_options_fields'));
			add_action('woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_options_fields'), 10, 2);

			add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'variable_options_fields'), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variable_options_fields'), 10, 2 );
		}

		public static function variable_options_fields($loop, $variation_data, $variation) {
			include( dirname( __FILE__ ) . '/views/html-variable-options.php' );
		}

    public static function product_options_fields() {
  		global $post;
      include( dirname( __FILE__ ) . '/views/html-product-options.php' );
  	}

  	public static function save_product_options_fields($product) {
  		$menny_egyseg = ! empty( $_REQUEST['wc_ebiz_mennyisegi_egyseg'] )
  			? esc_attr( $_REQUEST['wc_ebiz_mennyisegi_egyseg'] )
  			: '';
  		$product->update_meta_data( 'wc_ebiz_mennyisegi_egyseg', $menny_egyseg );
  		$megjegyz = ! empty( $_REQUEST['wc_ebiz_megjegyzes'] )
  			? esc_attr( $_REQUEST['wc_ebiz_megjegyzes'] )
  			: '';
  		$product->update_meta_data( 'wc_ebiz_megjegyzes', $megjegyz );
			$tetel_nev = ! empty( $_REQUEST['wc_ebiz_tetel_nev'] )
  			? esc_attr( $_REQUEST['wc_ebiz_tetel_nev'] )
  			: '';
  		$product->update_meta_data( 'wc_ebiz_tetel_nev', $tetel_nev );
  		$product->save_meta_data();
  	}

		public static function save_variable_options_fields($variation_id, $i) {
			$custom_field = sanitize_text_field($_POST['wc_ebiz_mennyisegi_egyseg'][$i]);
	    if ( ! empty( $custom_field ) ) {
	        update_post_meta( $variation_id, 'wc_ebiz_mennyisegi_egyseg', esc_attr( $custom_field ) );
	    } else delete_post_meta( $variation_id, 'wc_ebiz_mennyisegi_egyseg' );

			$custom_field = sanitize_text_field($_POST['wc_ebiz_megjegyzes'][$i]);
	    if ( ! empty( $custom_field ) ) {
	        update_post_meta( $variation_id, 'wc_ebiz_megjegyzes', esc_attr( $custom_field ) );
	    } else delete_post_meta( $variation_id, 'wc_ebiz_megjegyzes' );

			$custom_field = sanitize_text_field($_POST['wc_ebiz_tetel_nev'][$i]);
	    if ( ! empty( $custom_field ) ) {
	        update_post_meta( $variation_id, 'wc_ebiz_tetel_nev', esc_attr( $custom_field ) );
	    } else delete_post_meta( $variation_id, 'wc_ebiz_tetel_nev' );
		}
  }

	WC_eBiz_Product_Options::init();

endif;
