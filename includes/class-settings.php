<?php

if ( ! class_exists( 'WC_eBiz_Settings' ) ) :

class WC_eBiz_Settings extends WC_Integration {
	public static $activation_url;

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                 = 'wc_ebiz';
		$this->method_title       = __( 'OTP eBiz', 'wc-ebiz' );
		$this->method_description = __( 'Nézd át az alábbi beállításokat ahhoz, hogy számlákat tudj generálni.', 'wc-ebiz' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Action to save the fields
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'save_payment_options' ) );

		//Check and save PRO version
		add_action( 'wp_ajax_wc_ebiz_pro_check', array( $this, 'pro_check' ) );
		add_action( 'wp_ajax_wc_ebiz_pro_deactivate', array( $this, 'pro_deactivate' ) );

		//Define activation url
		self::$activation_url = 'https://szamlazz.visztpeter.me/';

	}

	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$disabled = false;
		$pro_icon = '';
		if(!get_option('_wc_ebiz_pro_enabled')) {
			$disabled = true;
			$pro_icon = '<i class="wc_ebiz_pro_label">PRO</i>';
		}

		$this->form_fields = array(
			'pro_key' => array(
        'title'    => __( 'PRO verzió', 'wc-ebiz' ),
  			'type'     => 'pro'
			),
			'section_auth_settings' => array(
				'title' => __( 'Authentikáció', 'wc-ebiz' ),
				'type'  => 'title',
				'description'  => __( 'Add meg az eBIZ-től kapott adatokat API kulcsot. Ezeket az adatok az OTP eBiz support küldi meg. E-Mail cím: devsupport@otpebiz.hu', 'wc-ebiz' ),
			),
			'api_key' => array(
        'title'    => __( 'API Kulcs', 'wc-ebiz' ),
  			'type'     => 'text',
				'class'		 => 'teszt'
			),
			'section_issuer_settings' => array(
				'title' => __( 'Számlakibocsátó adatai', 'wc-ebiz' ),
				'type'  => 'title',
				'description'  => __( 'A számla kibocsátójának adatai(megjelenik a számlán eladóként).', 'wc-ebiz' ),
			),
			'issuer_name' => array(
        'title'    => __( 'Név', 'wc-ebiz' ),
  			'type'     => 'text',
			),
			'issuer_city' => array(
        'title'    => __( 'Város', 'wc-ebiz' ),
  			'type'     => 'text',
				'default'	 => WC()->countries->get_base_city()
			),
			'issuer_address' => array(
        'title'    => __( 'Cím', 'wc-ebiz' ),
  			'type'     => 'text',
				'default'	 => WC()->countries->get_base_address()
			),
			'issuer_postcode' => array(
        'title'    => __( 'Irányítószám', 'wc-ebiz' ),
  			'type'     => 'text',
				'default'	 => WC()->countries->get_base_postcode()
			),
			'issuer_country' => array(
        'title'    => __( 'Ország', 'wc-ebiz' ),
  			'type'     => 'select',
				'class'    => 'chosen_select',
				'options'	 => WC()->countries->get_countries(),
				'default'	 => WC()->countries->get_base_country()
			),
			'issuer_vat' => array(
				'title'    => __( 'Adószám', 'wc-ebiz' ),
				'type'     => 'row_of_inputs',
				'class'    => 'row_of_inputs',
				'options'	 => array(
					'issuer_vat_number' => 'Adószám',
					'issuer_eu_vat_number' => 'Közösségi adószám',
				)
			),
			'issuer_bank' => array(
				'title'    => __( 'Bankszámlaszám(opcionális)', 'wc-ebiz' ),
				'type'     => 'row_of_inputs',
				'class'    => 'row_of_inputs',
				'options'	 => array(
					'issuer_bank_number' => 'Számlaszám',
					'issuer_bank_name' => 'Bank neve',
					'issuer_bank_iban' => 'IBAN',
					'issuer_bank_swift' => 'Swift'
				)
			),
			'section_invoice_settings' => array(
				'title' => __( 'Számla beállítások', 'wc-ebiz' ),
				'type'  => 'title',
				'description'  => __( 'A számlával kapcsolatos alapértelmezett beállítások.', 'wc-ebiz' ),
			),
			'invoice_number' => array(
        'title'    => __( 'Számla sorszáma', 'wc-ebiz' ),
  			'type'     => 'text',
				'default'	 => '',
				'desc_tip' => __( 'Az alábbi helyettesítő kódokat tudod használni: {rendelesszam}, {ev}, {honap}, {nap}. A dátumoknál számokkal írja ki. Pl.: WEBSHOP/{ev}/{honap}/{nap}/{rendelesszam}'),
				'description' => __( 'Ha üresen hagyod, akkor az eBiz generál egy sorszámot')
			),
			'invoice_type' => array(
        'title'    => __( 'Számla típusa', 'wc-ebiz' ),
  			'class'    => 'chosen_select',
  			'css'      => 'min-width:300px;',
  			'type'     => 'select',
  			'options'     => array(
  				'electronic'  => __( 'Elektronikus', 'wc-ebiz' ),
  				'paper' => __( 'Papír', 'wc-ebiz' )
  			)
			),
			'invoice_type_company' => array(
        'title'    => __( 'Számla típusa céges rendelésnél', 'wc-ebiz' ),
  			'class'    => 'chosen_select',
  			'css'      => 'min-width:300px;',
  			'type'     => 'select',
  			'options'     => array(
					'' => __( 'Alapértelmezett', 'wc-ebiz' ),
  				'electronic'  => __( 'Elektronikus', 'wc-ebiz' ),
  				'paper' => __( 'Papír', 'wc-ebiz' )
  			),
				'desc_tip' => __( 'Céges rendelés esetén a számla típusa. A rendelés akkor céges, ha volt megadva cégnév.')
			),
			'payment_deadline' => array(
        'title'    => __( 'Fizetési határidő(nap)', 'wc-ebiz' ),
  			'type'     => 'number'
			),
			'note' => array(
        'title'    => __( 'Megjegyzés', 'wc-ebiz' ),
  			'type'     => 'text',
				'desc_tip' => __( 'A {customer_email} kóddal megjelenítheted a vásárló email címét a megjegyzésben')
			),
			'afakulcs' => array(
				'type'		 => 'select',
				'title'    => __( 'Áfakulcs', 'wc-ebiz' ),
				'options'  => array(
					'' => __( 'Alapértelmezett', 'wc-ebiz' ),
					'TAM' => __( 'TAM', 'wc-ebiz' ),
					'AAM' => __( 'AAM', 'wc-ebiz' ),
					'EU' => __( 'EU', 'wc-ebiz' ),
					'EUK' => __( 'EUK', 'wc-ebiz' ),
					'MAA' => __( 'MAA', 'wc-ebiz' ),
					'F.AFA' => __( 'F.AFA', 'wc-ebiz' ),
					'ÁKK' => __( 'ÁKK', 'wc-ebiz' ),
				),
				'desc_tip'     => __( 'Az áfakulcs, ami a számlán szerepelni fog. Alapértelmezetten százalékos értéket fog mutatni.', 'wc-ebiz' ),
				'default'  => ''
			),
			'nyelv' => array(
				'type'		 => 'select',
				'title'    => __( 'Számla nyelve', 'wc-ebiz' ),
				'options'  => array(
					'hu' => __( 'Magyar', 'wc-ebiz' ),
					'de' => __( 'Német', 'wc-ebiz' ),
					'en' => __( 'Angol', 'wc-ebiz' ),
				),
				'default'  => 'hu'
			),
			'nyelv_wpml' => array(
				'title'    => __( 'WPML és Polylang kompatibilitás', 'wc-ebiz' ),
				'type'     => 'checkbox',
				'desc_tip' => __('Ha be van kapcsolva, akkor a WPML vagy a Polylang által a rendelés adataiban tárolt nyelvkódot fogja használja a számla létrehozásához', 'wc-ebiz')
			),
			'mennyisegi_egyseg' => array(
				'title'    => __( 'Mennyiségi egység', 'wc-ebiz' ),
				'type'     => 'text',
				'desc_tip' => __('Ez lesz az alapértelmezett mennyiségi egység a számlán a tételeknél, például "db". A termék szerkesztésekor a Haladó fülön megadható egyedi mennyiségi egység külön-külön minden termékhez.', 'wc-ebiz')
			),
			'append_company_name' => array(
				'title'    => __( 'Cégnév + név', 'wc-ebiz' ),
				'type'     => 'checkbox',
				'desc_tip' => __('Ha be van kapcsolva és vásárláskor cégnév van megadva, a vásárló nevét is feltünteti egy kötőjellel a cégnév után a számlán.', 'wc-ebiz')
			),
			'shipping_info' => array(
				'title'    => __( 'Szállítási adatok', 'wc-ebiz' ),
				'type'     => 'checkbox',
				'desc_tip' => __('Ha be van kapcsolva, akkor feltünteti a vásárló szállítási címét a számlán, nem csak a számlázásit.', 'wc-ebiz')
			),
			'invoice_headers' => array(
				'title'    => __( 'Számla fejléc és lábléc', 'wc-ebiz' ),
				'type'     => 'row_of_inputs',
				'class'    => 'row_of_inputs',
				'options'	 => array(
					'invoice_h1_note' => 'Fejléc 1. sor',
					'invoice_h2_note' => 'Fejléc 2. sor',
					'invoice_f1_note' => 'Lábléc 1. sor',
					'invoice_f2_note' => 'Lábléc 2. sor'
				)
			),
			'section_automatic' => array(
				'title' => __( 'Automatizálás', 'wc-ebiz' ),
				'type'  => 'title',
				'description'  => __( 'Számlakészítés automatizálásával kapcsolatos beállítások. Ha az adott fizetési módnál a teljesítettnek jelölés be van pipálva, akkor a rendelés teljesítettnek jelölésekor automatikusan kifizetettnek jelöli meg a számlát. Ha a díjbekérő be van pipálva, akkor a rendelés létrehozásakor díjbekérőt is készít. A határidő minden fizetési módhoz egyedileg beállítható(az alapértelmezett érték a fenti beállításokban van).', 'wc-ebiz' ),
			),
			'auto' => array(
				'disabled' => $disabled,
        'title'    => __( 'Automata számlakészítés', 'wc-ebiz' ).$pro_icon,
  			'type'     => 'checkbox',
  			'desc_tip'     => __( 'Ha be van kapcsolva, akkor a rendelés lezárásakor automatán kiállításra kerül a számla és az eBiz elküldi a vásárló email címére.', 'wc-ebiz' ),
			),
			'auto_status' => array(
				'type' => 'select',
				'disabled' => $disabled,
				'title' => __( 'Számlakészítés státusznál', 'wc-ebiz' ).$pro_icon,
				'class' => 'wc-enhanced-select',
				'default' => 'wc-completed',
				'options'  => wc_get_order_statuses(),
				'desc_tip' => __( 'Ha a rendelés ebbe a státuszba kerül, akkor fogja automatán megcsináni a számlát(csak ha az automata számlakészítés be van kapcsolva). ', 'wc-ebiz' ),
			),
			'auto_sztorno' => array(
				'disabled' => $disabled,
				'type' => 'select',
				'title' => __( 'Automata sztornózás', 'wc-abiz' ).$pro_icon,
				'class' => 'wc-enhanced-select',
				'default' => 'no',
				'options'  => $this->get_order_statuses_for_sztorno(),
				'desc_tip' => __( 'Ha a rendelés ebbe a státuszba kerül, akkor automatán létrehozza a sztornó számlát(természetesen akkor, ha már van a rendeléshez számla csinálva és ha az automata számlakészítés be van kapcsolva). ', 'wc-ebiz' ),
			),
			'auto_email' => array(
				'title'    => __( 'Számlaértesítő', 'wc-ebiz' ),
				'type'     => 'checkbox',
				'desc_tip'     => __( 'Ha be van kapcsolva, akkor a vásárlónak az eBiz kiküldi a számlaértesítőt automatán, emailben', 'wc-ebiz' ),
				'default'  => 'yes'
			),
			'payment_methods' => array(
				'title'    => __( 'Fizetési módok', 'wc-ebiz' ),
				'type'     => 'payment_methods',
				'disabled' => $disabled
			),
			'section_vat_number' => array(
				'title' => __( 'Adószám', 'wc-ebiz' ),
				'type'  => 'title',
				'description'  => __( '100.000 Ft áfatartalomnál magasabb rendeléseknél kötelező adószámot bekérni, ha van a vevőnek. Az alábbi opciókkal egy adószám mezőt lehet hozzáadni a pénztár űrlaphoz.', 'wc-ebiz' ),
			),
			'vat_number_form' => array(
        'title'    => __( 'Adószám mező vásárláskor', 'wc-ebiz' ),
  			'type'     => 'checkbox',
  			'desc_tip'     => __( 'A számlázási adatok alján egy új mezőben lesz bekérve. Eltárolja a rendelés adataiban, illetve számlára is ráírja. Ha kézzel kell megadni utólag a rendeléskezelőben, akkor az egyedi mezőknél az "adoszam" mezőt kell kitölteni.', 'wc-ebiz' ),
			),
			'vat_number_form_min' => array(
        'title'    => __( 'Csak 100.000 Ft áfa felett', 'wc-ebiz' ),
  			'type'     => 'checkbox',
  			'desc_tip'     => __( 'Az adószám mező csak akkor látszik, ha 100.000 Ft felett van az áfatartalom.', 'wc-ebiz' ),
			),
			'vat_number_notice' => array(
				'title'    => __( 'Adószám figyelmeztetés', 'wc-ebiz' ),
				'type'     => 'textarea',
				'default'	 => __( 'A vásárlás áfatartalma több, mint 100.000 Ft, ezért amennyiben rendelkezik adószámmal, azt kötelező megadni a számlázási adatoknál.', 'wc-ebiz'),
				'desc_tip'     => __( 'Ez az üzenet jelenik meg, ha az adószám mező be van pipálva felül a fizetés oldalon és 100.000 Ft felett van az áfatartalom.', 'wc-ebiz' ),
			),
			'section_other' => array(
				'title' => __( 'Egyéb beállítások', 'wc-ebiz' ),
				'type'  => 'title'
			),
			'customer_download' => array(
				'title'    => __( 'Számlák a profilban', 'wc-ebiz' ),
				'type'     => 'checkbox',
				'desc_tip'     => __( 'Ha be van kapcsolva, akkor a díjbekérőt és a számlát is le tudja tölteni a felhasználó belépés után, a Rendeléseim menüben.', 'wc-ebiz' ),
				'default'  => 'no'
			),
			'attachment' => array(
				'title'    => __( 'Számla csatolása rendelés emailekhez', 'wc-ebiz' ),
				'type'     => 'checkbox',
				'desc_tip'     => __( 'A számlák a WooCommerce által kiküldött emailekhez is csatolva lesznek. Érdemes ilyenkor a számlaértesítőt kikapcsolni. A Rendelés feldolgozása/Megrendelés fizetésre vár emailhez csatolja a díjbekérőt(ha van), a Teljesített rendelés/Vásárlói számla/Rendelési adatok emailhez az elkészült számlát, a Visszamondott/Visszatérített emailekhez pedig a sztornó számlát.', 'wc-ebiz' ),
			),
			'switch_name_order' => array(
				'title'    => __( 'Vezetéknév / keresztnév csere', 'wc-ebiz' ),
				'type'     => 'checkbox',
				'desc_tip'     => __( 'Ha rosszul jelenik meg a számlán a vevő neve, ezzel az opcióval meg lehet cserélni a vezetéknevet és a keresztnevet.', 'wc-ebiz' ),
			),
			'debug' => array(
				'default' => 'yes',
        'title'    => __( 'Fejlesztői mód', 'wc-ebiz' ),
  			'type'     => 'checkbox',
				'description' => __('Fejlesztői módban a teszt OTP eBiz szervert használja, tehát csak teszt authentikációs adatokkal fog működni.', 'wc-ebiz'),
  			'desc_tip' => __( 'Ha be van kapcsolva, akkor a generált XML fájl nem lesz letörölve, teszteléshez használatos opció. Az XML fájlok a wp-content/uploads/wc_ebiz/ mappában vannak, fájlnév a rendelés száma.', 'wc-ebiz' ),
			),
			'error_email' => array(
				'title'    => __( 'Email cím hibajelzéshez', 'wc-ebiz' ),
				'type'     => 'text',
				'desc_tip'     => __( 'Ha megadsz egy email címet, akkor ide küld a rendszer egy levelet, ha volt a webáruházban egy sikertelen automata számlakészítés. Hagyd üresen, ha nem szeretnél emailt kapni.', 'wc-ebiz' ),
			),

			//Just so the built in save functions works for the custom "row of inputs" field type
			'issuer_vat_number' => array('type' => 'blank'),
			'issuer_eu_vat_number' => array('type' => 'blank'),
			'issuer_bank_number' => array('type' => 'blank'),
			'issuer_bank_name' => array('type' => 'blank'),
			'issuer_bank_iban' => array('type' => 'blank'),
			'issuer_bank_swift' => array('type' => 'blank'),
			'invoice_h1_note' => array('type' => 'blank'),
			'invoice_h2_note' => array('type' => 'blank'),
			'invoice_f1_note' => array('type' => 'blank'),
			'invoice_f2_note' => array('type' => 'blank'),
		);
	}

	//Order statuses
	public function get_order_statuses_for_sztorno() {
		$built_in_statuses = array("no"=>__("Kikapcsolva")) + wc_get_order_statuses();
		return $built_in_statuses;
	}

  //Get payment methods
  public function get_payment_methods() {
    $available_gateways = WC()->payment_gateways->payment_gateways();
		$payment_methods = array();
		foreach ($available_gateways as $available_gateway) {
			if($available_gateway->enabled == 'yes') {
				$payment_methods[$available_gateway->id] = $available_gateway->title;
			}
		}
    return $payment_methods;
  }

	public function generate_pro_html( $key, $data ) {
		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'class'             => 'button-secondary',
			'css'               => '',
			'custom_attributes' => array(),
			'desc_tip'          => false,
			'description'       => '',
			'title'             => '',
		);
		$data = wp_parse_args( $data, $defaults );
		ob_start();
		?>
		<tr valign="top" class="wc-ebiz-section-pro <?php if(get_option('_wc_ebiz_pro_enabled')): ?>wc-ebiz-section-pro-active<?php endif; ?>">
			<td>
				<div class="notice notice-error inline" style="display:none;"><p></p></div>
				<div class="wc-ebiz-section-pro-flex">
					<?php if(get_option('_wc_ebiz_pro_enabled')): ?>
						<div class="wc-ebiz-section-pro-active">
							<strong>Pro verzió aktiválva</strong>
							<small><?php echo esc_html(get_option('_wc_ebiz_pro_key')); ?> / <?php echo esc_html(get_option('_wc_ebiz_pro_email')); ?></small>
						</div>
						<a href="https://szamlazz.visztpeter.me" target="_blank" class="button-primary"><?php _e('Support', 'wc-ebiz'); ?></a>
						<button class="button-secondary" type="button" name="<?php echo esc_attr( $field ); ?>-deactivate" id="<?php echo esc_attr( $field ); ?>-deactivate"><?php _e('Deaktiválás', 'wc-ebiz'); ?></button>
					<?php else: ?>
						<div class="wc-ebiz-section-pro-form">
							<h3>WooCommerce + eBiz PRO verzió</h3>
							<p>Ha már megvásároltad, add meg a termékkulcsot és a vásárláshoz használt e-mail címet.</p>
							<?php echo $this->get_tooltip_html( $data ); ?>
							<fieldset>
								<input class="input-text regular-input" type="text" name="woocommerce_wc_ebiz_pro_key" id="woocommerce_wc_ebiz_pro_key" value="" placeholder="Termékkulcs"><br>
								<input class="input-text regular-input" type="text" name="woocommerce_wc_ebiz_pro_email" id="woocommerce_wc_ebiz_pro_email" value="" placeholder="Vásárláshoz használt email cím">
								<p><button class="button-primary" type="button" name="<?php echo esc_attr( $field ); ?>_submit" id="<?php echo esc_attr( $field ); ?>_submit"><?php _e('Aktiválás', 'wc-ebiz'); ?></button></p>
							</fieldset>
						</div>
						<div class="wc-ebiz-section-pro-cta">
							<h4>Miért érdemes megvennem?</h4>
							<ul>
								<li>Automata számlakészítés</li>
								<li>Automata díjbekérő létrehozása és sztornózás</li>
								<li>Automata teljesítettnek jelölés</li>
								<li>Prémium support</li>
							</ul>
							<div class="wc-ebiz-section-pro-cta-button">
								<a href="https://szamlazz.visztpeter.me/ebiz">PRO verzió vásárlása</a>
								<span>
									<small>nettó</small>
									<strong>10.000 Ft</strong>
								</span>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<table class="form-table">
	<tbody>
		<?php
		return ob_get_clean();
	}

	public function pro_check() {

		if ( !current_user_can( 'edit_shop_orders' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$pro_key = sanitize_text_field($_POST['key']);
		$pro_email = sanitize_email($_POST['email']);

		$args = array(
				'wc-api' => 'software-api',
				'request' => 'activation',
				'email' => $pro_email,
				'licence_key' => $pro_key,
				'product_id' => 'WC_EBIZ'
		);

		//Execute request (function below)
		$base_url = add_query_arg('wc-api', 'software-api', WC_eBiz_Settings::$activation_url);
		$target_url = $base_url . '&' . http_build_query( $args );
		$data = wp_remote_get( $target_url );
		$result = json_decode($data['body']);

		if(isset($result->activated) && $result->activated) {

			//Store the key and email
			update_option('_wc_ebiz_pro_key', $pro_key);
			update_option('_wc_ebiz_pro_email', $pro_email);
			update_option('_wc_ebiz_pro_enabled', true);

			wp_send_json_success();

		} else {

			wp_send_json_error(array(
				'message' => __('Nem sikerült az aktiválás, kérlek ellenőrizd az adatok helyességét.', 'wc-ebiz')
			));

		}

	}

	public function pro_deactivate() {
		if ( !current_user_can( 'edit_shop_orders' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$pro_key = get_option('_wc_ebiz_pro_key');
		$pro_email = get_option('_wc_ebiz_pro_email');

		$args = array(
				'wc-api' => 'software-api',
				'request' => 'activation_reset',
				'email' => $pro_email,
				'licence_key' => $pro_key,
				'product_id' => 'WC_EBIZ'
		);

		//Execute request (function below)
		$base_url = add_query_arg('wc-api', 'software-api', WC_eBiz_Settings::$activation_url);
		$target_url = $base_url . '&' . http_build_query( $args );
		$data = wp_remote_get( $target_url );
		$result = json_decode($data['body']);

		if(isset($result->reset) && $result->reset) {

			//Store the key and email
			delete_option('_wc_ebiz_pro_key');
			delete_option('_wc_ebiz_pro_email');
			delete_option('_wc_ebiz_pro_enabled');

			wp_send_json_success();

		} else {

			wp_send_json_error(array(
				'message' => __('Nem sikerült a deaktiválás, kérlek ellenőrizd az adatok helyességét.', 'wc-ebiz')
			));

		}

	}

	public function generate_payment_methods_html($key, $data) {
		ob_start();

		$payment_methods = $this->get_payment_methods();
		$saved_values = get_option('wc_ebiz_payment_method_options');

		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Fizetési módok:', 'woocommerce' ); ?></th>
			<td class="forminp">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table" cellspacing="0">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Fizetési mód', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'eBiz azonosító', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Fizetési határidő(nap)', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Díjbekérő létrehozása', 'woocommerce' ); ?> <?php if($data['disabled']): ?><i class="wc_ebiz_pro_label">PRO</i><?php endif; ?></th>
								<th><?php //esc_html_e( 'Teljesítettnek jelölés', 'woocommerce' ); ?> <?php if($data['disabled']): ?><i class="wc_ebiz_pro_label">PRO</i><?php endif; ?></th>
							</tr>
						</thead>
						<tbody class="wc_ebiz_settings_payment_methods">
							<?php foreach ( $payment_methods as $payment_method_id => $payment_method ): ?>
								<?php
								if($saved_values && isset($saved_values[esc_attr( $payment_method_id )])) {
									$value_ebiz_id = esc_attr( $saved_values[esc_attr( $payment_method_id )]['ebiz_id']);
									$value_deadline = esc_attr( $saved_values[esc_attr( $payment_method_id )]['deadline']);
									$value_request = $saved_values[esc_attr( $payment_method_id )]['request'];
									$value_complete = $saved_values[esc_attr( $payment_method_id )]['complete'];
								} else {
									$value_ebiz_id = '';
									$value_deadline = '';
									$value_request = false;
									$value_complete = false;
								}
								?>
								<tr>
									<td><strong><?php echo $payment_method; ?></strong></td>
									<td>
										<select name="wc_ebiz_payment_options[<?php echo esc_attr( $payment_method_id ); ?>][ebiz_id]">
											<option value="10" <?php selected( $value_ebiz_id, '10' ); ?>>Készpénz</option>
											<option value="30" <?php selected( $value_ebiz_id, '30' ); ?>>Átutalás</option>
											<option value="48" <?php selected( $value_ebiz_id, '48' ); ?>>Bankkártya</option>
											<option value="zO" <?php selected( $value_ebiz_id, 'zO' ); ?>>OTP Simple</option>
											<option value="zU" <?php selected( $value_ebiz_id, 'zU' ); ?>>Utánvét</option>
											<option value="zP" <?php selected( $value_ebiz_id, 'zP' ); ?>>PayPal</option>
											<option value="zV" <?php selected( $value_ebiz_id, 'zV' ); ?>>Utalvány</option>
										</select>
									</td>
									<td><input type="number" name="wc_ebiz_payment_options[<?php echo esc_attr( $payment_method_id ); ?>][deadline]" value="<?php echo $value_deadline; ?>" /></td>
									<td><input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="wc_ebiz_payment_options[<?php echo esc_attr( $payment_method_id ); ?>][request]" value="1" <?php checked( $value_request ); ?> /></td>
									<td><input <?php disabled( $data['disabled'] ); ?> style="display:none;" type="checkbox" name="wc_ebiz_payment_options[<?php echo esc_attr( $payment_method_id ); ?>][complete]" value="1" <?php checked( $value_complete ); ?> /></td>
								</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}

	public function save_payment_options() {
		$accounts = array();

		if ( isset( $_POST['wc_ebiz_payment_options'] ) ) {

			foreach ($_POST['wc_ebiz_payment_options'] as $payment_method_id => $payment_method) {
				$ebiz_id = wc_clean($payment_method['ebiz_id']);
				$deadline = wc_clean($payment_method['deadline']);
				$request = isset($payment_method['request']) ? true : false;
				$complete = isset($payment_method['complete']) ? true : false;

				$accounts[$payment_method_id] = array(
					'ebiz_id'  => $ebiz_id,
					'deadline' => $deadline,
					'request'  => $request,
					'complete' => $complete
				);
			}

		}

		update_option( 'wc_ebiz_payment_method_options', $accounts );
	}

	public function generate_blank_html($key, $data) {
		ob_start();
		?>

		<?php
		return ob_get_clean();
	}

	public function generate_row_of_inputs_html($key, $data) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);
		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<div class="wc-ebiz-row-inputs">
					<?php foreach ($data['options'] as $field_id => $label): ?>
						<div>
							<label for="<?php echo esc_attr( $this->get_field_key($field_id) ); ?>"><?php echo esc_attr( $label ); ?></label>
							<input class="input-text regular-input" type="text" name="<?php echo esc_attr( $this->get_field_key($field_id ) ); ?>" id="<?php echo esc_attr( $this->get_field_key($field_id) ); ?>" value="<?php echo esc_attr( $this->get_option( $field_id ) ); ?>" />
						</div>
					<?php endforeach; ?>
					<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
				</div>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}


}

endif;
