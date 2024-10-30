<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_eBiz_Health_Check', false ) ) :

	class WC_eBiz_Health_Check {

		//Init notices
		public static function init() {

      add_filter( 'debug_information', array( __CLASS__, 'debug_info' ) );
    	add_filter( 'site_status_tests', array(__CLASS__, 'status_tests') );
		}

		public static function debug_info($debug) {
      $wc_ebiz = array(
  			'wc_ebiz' => array(
  				'label'       => __( 'eBiz.hu', 'wc_ebiz' ),
  				'description' => sprintf(
  					__(
  						'Diagnosztikai információk az eBiz WooCommerce integrációhoz kapcsolódóan. Ha kérdésed van vagy valami nem működik, mellékeld ezeket az adatokat is: <a href="%1$s" target="_blank" rel="noopener noreferrer">Támogatás</a>',
  						'wc_ebiz'
  					),
  					esc_html( 'https://szamlazz.visztpeter.me/' )
  				),
  				'fields'      => self::debug_info_data(),
  			),
  		);
  		$debug = array_merge($debug, $wc_ebiz);
  		return $debug;
		}

    public static function status_tests($core_tests) {

      $core_tests['direct']['wc_ebiz_soap'] = array(
        'label' => __( 'eBiz követelmények', 'wc_ebiz' ),
        'test'  => function() {
          $settings = get_option( 'woocommerce_wc_ebiz_settings', null );

          $result = array(
      			'label'       => 'eBiz követelmények',
      			'status'      => 'good',
      			'badge'       => array(
      				'label' => __( 'eBiz' ),
      				'color' => 'blue',
      			),
      			'description' => 'A weboldal és a tárhely minden követelményenek megfelel egy sikeres számlakészítéshez az eBiz WooCommerce bővítménnyel.',
      			'test'        => 'wc_ebiz_php_version',
      		);

          //Check for cURL
					if(!extension_loaded('soap')) {
            $result['status'] = 'critical';
            $result['badge']['color'] = 'red';
            $result['description'] = __('A <strong>WooCommerce + eBiz</strong> bővítmény használatához a SOAP funkció szükséges és úgy néz ki, ezen a tárhelyen nincs bekapcsolva. Ha nem tudod mi ez, kérd meg a tárhelyszolgáltatót, hogy kapcsolják be.', 'wc_ebiz');
          }

          //Username/password
          if($settings['api_key'] || defined( 'WC_EBIZ_API_KEY' )) {
            //all good
          } else {
            $result['status'] = 'critical';
            $result['badge']['color'] = 'red';
            $result['description'] = __('A <strong>WooCommerce + eBiz</strong> bővítmény használatához add meg a bővítmény beállításaiban a WooCommerce API kulcsot. Az eBiz-től kell kérned ezeket az adatokat, ha nem rendelkezel még vele.', 'wc_ebiz');
            $result['actions'] = sprintf(
      				'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s <span aria-hidden="true" class="dashicons dashicons-admin-generic"></span></a></p>',
      				esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_ebiz' ) ),
      				__( 'Bellítások', 'wc_ebiz' )
      			);
          }

          return $result;
        }
      );

      //Debug mode is turned on
      $core_tests['direct']['wc_ebiz_debug'] = array(
        'label' => __( 'eBiz debug mód', 'wc_ebiz' ),
        'test'  => function() {
          $settings = get_option( 'woocommerce_wc_ebiz_settings', null );

          $result = array(
      			'label'       => __('eBiz fejlesztői mód ki van kapcsolva', 'wc-ebiz'),
      			'status'      => 'good',
      			'badge'       => array(
      				'label' => __( 'eBiz' ),
      				'color' => 'blue',
      			),
      			'description' => 'Az eBiz WooCommerce fejlesztői módja ki van kapcsolva.',
      			'test'        => 'wc_ebiz_check_debug_mode',
      		);

          //If debug mode is turned on
          if($settings['debug'] && $settings['debug'] == 'yes') {
            $result['label'] = __('Az eBiz fejlesztői mód be van kapcsolva', 'wc-szamlazz');
            $result['status'] = 'critical';
            $result['badge']['color'] = 'red';
            $result['description'] = __('A <strong>WooCommerce + eBiz</strong> bővítmény fejlesztői módja be van kapcsolva. Éles használat esetén ezt mindenképp kapcsold ki, amit megtehetsz a bővítmény beállításaiban.', 'wc_ebiz');
            $result['actions'] = sprintf(
      				'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s <span aria-hidden="true" class="dashicons dashicons-admin-generic"></span></a></p>',
      				esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_ebiz' ) ),
      				__( 'Bellítások', 'wc_ebiz' )
      			);
          }

          return $result;
        }
      );

      return $core_tests;
  	}

    public static function debug_info_data() {
      $debug_info = array();

			//PRO verzió
			$debug_info['wc_ebiz_pro_version'] = array(
				'label'   => __('PRO verzió', 'wc_ebiz'),
				'value'   => get_option('_wc_ebiz_pro_enabled')
			);

			//Invoice path
			$UploadDir = wp_upload_dir();
			$UploadURL = $UploadDir['basedir'];
			$location  = realpath($UploadURL . "/wc_ebiz/");
			$debug_info['wc_ebiz_path'] = array(
				'label'   => __('Útvonal', 'wc_ebiz'),
				'value'   => $location
			);

			//Payment options
			$payment_options = get_option('wc_ebiz_payment_method_options');
			$debug_info['wc_ebiz_payment_options'] = array(
				'label'   => __('Fizetési módok', 'wc_ebiz'),
				'value'   => print_r($payment_options, true)
			);

			//Display saved settings
			$settings_api = new WC_eBiz_Settings();
      $settings = get_option( 'woocommerce_wc_ebiz_settings', null );
      $options = $settings_api->form_fields;
			unset($options['api_key']);
			unset($options['password']);

      foreach ($options as $option_id => $option) {
				if(!in_array($option['type'], array('pro', 'title', 'payment_methods', 'blank'))) {
					$debug_info[$option_id] = array(
						'label'   => $option['title'],
						'value'   => $settings[$option_id]
					);
				}
      }

      return $debug_info;
    }

  }

	WC_eBiz_Health_Check::init();

endif;
