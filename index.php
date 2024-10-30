<?php
/*
Plugin Name: Integration for OTP eBiz & WooCommerce
Plugin URI: http://visztpeter.me
Description: OTP eBiz összeköttetés WooCommercehez
Author: Viszt Péter
Version: 1.1
WC requires at least: 3.0.0
WC tested up to: 3.9
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Generate stuff on plugin activation
function wc_ebiz_activate() {
	$upload_dir =  wp_upload_dir();

	$files = array(
		array(
			'base' 		=> $upload_dir['basedir'] . '/wc_ebiz',
			'file' 		=> 'index.html',
			'content' 	=> ''
		)
	);

	foreach ( $files as $file ) {
		if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
			if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
				fwrite( $file_handle, $file['content'] );
				fclose( $file_handle );
			}
		}
	}
}
register_activation_hook( __FILE__, 'wc_ebiz_activate' );

class WC_eBiz {

	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basename;
	public static $version;
	public $soap_client = null;

  //Construct
	public function __construct() {

		//Default variables
		self::$plugin_prefix = 'wc_ebiz_';
		self::$plugin_basename = plugin_basename(__FILE__);
		self::$plugin_url = plugin_dir_url(self::$plugin_basename);
		self::$plugin_path = trailingslashit(dirname(__FILE__));
		self::$version = '1.1';

		//SOAP Client helper
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-extend-simplexml.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/ebiz-api-client.php' );
		$this->soap_client = new WC_eBiz_Motor_soapv2SoapClient();

		//Plugin loaded
		add_action( 'plugins_loaded', array( $this, 'init' ) );

  }

	//Load plugin stuff
	public function init() {

		// Load includes
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-settings.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-admin-notices.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-product-options.php' );

		//Health check for WP 5.2+
		global $wp_version;
		if ( version_compare( $wp_version, '5.2-alpha', 'ge' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-health-check.php' );
		}

		//Plugin links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

		//Settings page
		add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

		//Load admin CSS & JS
		add_action( 'admin_init', array( $this, 'wc_ebiz_admin_init' ) );

		//Add metabox for order details
		add_action( 'add_meta_boxes', array( $this, 'wc_ebiz_add_metabox' ) );

		//Ajax functions in the admin to create invoices
		add_action( 'wp_ajax_wc_ebiz_generate_invoice', array( $this, 'generate_invoice_with_ajax' ) );
		add_action( 'wp_ajax_wc_ebiz_sztorno', array( $this, 'generate_invoice_sztorno_with_ajax' ) );
		add_action( 'wp_ajax_wc_ebiz_complete', array( $this, 'generate_invoice_complete_with_ajax' ) );
		add_action( 'wp_ajax_wc_ebiz_already', array( $this, 'wc_ebiz_already' ) );
		add_action( 'wp_ajax_wc_ebiz_already_back', array( $this, 'wc_ebiz_already_back' ) );

		//Create a hook based on the status setup in settings to auto-generate invoice
		if(get_option('_wc_ebiz_pro_enabled') && $this->get_option('auto') && $this->get_option('auto') == 'yes') {
			$order_auto_status = str_replace( 'wc-', '', $this->get_option('auto_status', 'completed') );
			add_action( 'woocommerce_order_status_'.$order_auto_status, array( $this, 'on_order_complete' ) );

			$order_auto_sztorno = str_replace( 'wc-', '', $this->get_option('auto_sztorno', 'no') );
			if($order_auto_sztorno != 'no') {
				add_action( 'woocommerce_order_status_'.$order_auto_sztorno, array( $this, 'on_order_deleted' ) );
			}
		}

		//Create invoice when new order is created
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_order_processing' ) );

		//Adds icon at the orders page table to download invoices
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_listing_actions' ) );

		//Add invoice download link on the my account page
		add_filter('woocommerce_my_account_my_orders_actions', array( $this, 'ebiz_download_button' ), 10,2);

		//Show VAT number field during checkout if needed
		if($this->get_option('vat_number_form') == 'yes') {
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'add_vat_number_checkout_field' ) );
			add_filter( 'woocommerce_before_checkout_form' , array( $this, 'add_vat_number_info_notice' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_vat_number' ) );
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_vat_number' ) );
		}

		//Attach invoice to emails
		if($this->get_option('attachment') == 'yes') {
			add_filter( 'woocommerce_email_attachments', array( $this, 'email_attachment'), 10, 3 );
		}

	}

	//Integration page
	public function add_integration( $integrations ) {
		$integrations[] = 'WC_eBiz_Settings';
		return $integrations;
	}

  //Add CSS & JS
	public function wc_ebiz_admin_init() {
		wp_enqueue_script( 'ebiz_js', plugins_url( '/assets/js/admin.js',__FILE__ ), array('jquery'), wc_ebiz::$version, TRUE );
		wp_enqueue_style( 'ebiz_css', plugins_url( '/assets/css/admin.css',__FILE__ ), array(), wc_ebiz::$version );

		$wc_ebiz_local = array( 'loading' => plugins_url( '/assets/images/ajax-loader.gif',__FILE__ ) );
		wp_localize_script( 'ebiz_js', 'wc_ebiz_params', $wc_ebiz_local );
  }

	//Meta box on order page
	public function wc_ebiz_add_metabox( $post_type ) {
		add_meta_box('wc_ebiz_order_option', 'OTP eBiz számla', array( $this, 'render_meta_box_content' ), 'shop_order', 'side');
	}

	//Render metabox content
	public function render_meta_box_content($post) {
		$order = wc_get_order($post->ID);
		include( dirname( __FILE__ ) . '/includes/views/html-metabox.php' );
	}

	//Generate Invoice with Ajax
	public function generate_invoice_with_ajax() {
		check_ajax_referer( 'wc_generate_invoice', 'nonce' );

		$order_id = absint($_POST['order']);
		$return_info = $this->generate_invoice($order_id);

		wp_send_json_success($return_info);
	}

	//Generate Invoice with Ajax
	public function generate_invoice_complete_with_ajax() {
		check_ajax_referer( 'wc_generate_invoice', 'nonce' );
		if( true ) {
			$orderid = intval($_POST['order']);
			$return_info = $this->generate_invoice_complete($orderid);
			wp_send_json_success($return_info);
		}
	}

	//Generate XML for Szamla Agent
	public function generate_invoice($orderId,$invoice_type = 'invoice') {
		global $wpdb, $woocommerce;
		$order = new WC_Order($orderId);
		$order_items = $order->get_items();

		//Build Xml
		$szamla = new WCeBizSimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><EbizInvoice xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></EbizInvoice>');

		//If custom details
		if(isset($_POST['note']) && isset($_POST['deadline']) && isset($_POST['completed'])) {
			$note = sanitize_text_field($_POST['note']);
			$deadline = intval($_POST['deadline']);
			$complated_date = sanitize_text_field($_POST['completed']);
		} else {
			$note = $this->get_option('note');
			$deadline = $this->get_payment_method_deadline($order->get_payment_method());
			$complated_date = date('Y-m-d');
		}

		//Create invoice name
		if($this->get_invoice_number($order) != '') {
			$szamla->addChild('ID', $this->get_invoice_number($order));
		}

		//Replace customer email in note
		$note_replacements = array('{customer_email}' => $order->get_billing_email());
		$note = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $note);
		$szamla->addChild('Note', $note);

		//Headers and footers
		$szamla->addChild('H1Note', $this->get_option('invoice_h1_note'));
		$szamla->addChild('H2Note', $this->get_option('invoice_h2_note'));
		$szamla->addChild('F1Note', $this->get_option('invoice_f1_note'));
		$szamla->addChild('F2Note', $this->get_option('invoice_f2_note'));

		//Díjbekérő
		$multiplier = 1;
		$sztorno_szamla = false;
		$payment_request = false;
		if(($invoice_type && $invoice_type == 'proform') || (isset($_POST['request']) && esc_attr($_POST['request']) == 'on')) {
			$payment_request = true;
			$szamla->addChild('InvoiceTypeCode', 'ProformaInvoice');
		} elseif ($invoice_type && $invoice_type == 'sztorno') {
			$sztorno_szamla = true;
			$multiplier = -1;
			$szamla->addChild('InvoiceTypeCode', 'StornoInvoice');
		} else {
			$szamla->addChild('InvoiceTypeCode', 'SalesInvoice');
		}

		//Invoice type
		$electronic_invoice_type = $this->get_invoice_type($order);
		$szamla->addChild('OutputType', $electronic_invoice_type);

		//Currency
		$order_currency = method_exists( $order, 'get_currency' ) ? $order->get_currency() : $order->get_order_currency();
		$szamla->addChild('DocumentCurrencyCode', $order_currency);

		//Issue, complete, due dates & times
		$szamla->addChild('IssueDate', date('Y-m-d') );
		$szamla->addChild('IssueTime', date('h:m:s') );
		$szamla->addChild('TaxPointDate', $complated_date );
		if($deadline) {
			$szamla->addChild('DueDate', date('Y-m-d', strtotime('+'.$deadline.' days')));
		} else {
			$szamla->addChild('DueDate', date('Y-m-d'));
		}

		//Order number
		$szamla->addChild('OrderReference', $order->get_order_number());

		//If its a sztorno invoice, reference original invoice
		if($sztorno_szamla) {
			$invoice_number = get_post_meta($orderId,'_wc_ebiz',true);
			$szamla->addChild('BillingReference');
			$szamla->BillingReference->addChild('InvoiceDocumentReference');
			$szamla->BillingReference->InvoiceDocumentReference->addChild('ID', '');
			$szamla->BillingReference->InvoiceDocumentReference->addChild('DocumentDescription', 'ORIGINAL');
		}

		//Payment method
		$PaymentMeans = $szamla->addChild('PaymentMeans');
		$PaymentMeans->addChild('PaymentMeansCode', $this->get_payment_means_code($order));
		$PaymentMeans->appendXML($this->get_issuer_bank_account_xml_object());

		//Seller data
		$szamla->appendXML($this->get_issuer_xml_object());

		//Customer data
		$AccountigCustomerParty = $szamla->addChild('AccountigCustomerParty');
		$AccountigCustomerPartyPartyName = $AccountigCustomerParty->addChild('PartyName');
		$AccountigCustomerPartyPartyName->addChild('Name', $this->get_customer_name($order));
		$PartyLegalEntity = $AccountigCustomerParty->addChild('PartyLegalEntity');
		$PartyLegalEntity->addChild('RegistrationName', $this->get_customer_name($order));
		$RegistrationAddress = $PartyLegalEntity->addChild('RegistrationAddress');
		$RegistrationAddress->addChild('StreetName', '');
		$RegistrationAddress->addChild('AdditionalStreetName', '');
		$RegistrationAddress->addChild('BuildingNumber', '');
		$RegistrationAddress->addChild('CityName', $order->get_billing_city());
		$RegistrationAddress->addChild('PostalZone', $order->get_billing_postcode());
		$RegistrationAddress->addChild('AddressLine');
		$RegistrationAddress->AddressLine->addChild('Line', $order->get_billing_address_1());
		$RegistrationAddress->addChild('Country');
		$RegistrationAddress->Country->addChild('IdentificationCode', $order->get_billing_country());
		$RegistrationAddress->Country->addChild('Name', WC()->countries->countries[ $order->get_billing_country() ]);
		$PartyLegalEntity->addChild('CompanyLegalForm', '');

		//Shipping address
		if($this->get_option('shipping_info') == 'yes') {
			$AccountigCustomerPartyPartyName->addChild('DeliveryAddress');
			$AccountigCustomerPartyPartyName->DeliveryAddress->addChild('CityName', $order->get_shipping_city());
			$AccountigCustomerPartyPartyName->DeliveryAddress->addChild('PostalZone', $order->get_shipping_postcode());
			$AccountigCustomerPartyPartyName->DeliveryAddress->addChild('AddressLine');
			$AccountigCustomerPartyPartyName->DeliveryAddress->AddressLine->addChild('Line', $order->get_shipping_address_1());
			$AccountigCustomerPartyPartyName->DeliveryAddress->addChild('Country');
			$AccountigCustomerPartyPartyName->DeliveryAddress->Country->addChild('IdentificationCode', $order->get_shipping_country());
			$AccountigCustomerPartyPartyName->DeliveryAddress->Country->addChild('Name', WC()->countries->countries[ $order->get_shipping_country() ]);
		}

		//Currency exchange rate
		if($order_currency != 'HUF') {
			$szamla->appendXML($this->get_exchange_rate_xml_object($order_currency));
		}

		//Calculate taxes
		$taxPercentage = round($order->get_total_tax()/($order->get_total()-$order->get_total_tax())*100, 0);
		$TaxTotal = $szamla->addChild('TaxTotal');
		$TaxTotal->addChild('TaxAmount', round($order->get_total_tax()*$multiplier,2));
		$TaxTotal->addChild('TaxSubtotal');
		$TaxTotal->TaxSubtotal->addChild('TaxableAmount', round(($order->get_total() - $order->get_total_tax())*$multiplier,2));
		$TaxTotal->TaxSubtotal->addChild('TaxAmount', round($order->get_total_tax()*$multiplier,2));
		$TaxTotal->TaxSubtotal->addChild('CurrencyID', 'HUF');
		$TaxTotal->TaxSubtotal->addChild('Percent', $taxPercentage);
		$TaxTotal->TaxSubtotal->addChild('TaxCategory');
		$TaxTotal->TaxSubtotal->TaxCategory->addChild('Name', $this->get_tax_name($taxPercentage));
		$TaxTotal->TaxSubtotal->TaxCategory->addChild('TaxScheme');
		$TaxTotal->TaxSubtotal->TaxCategory->TaxScheme->addChild('ID', $this->get_tax_name($taxPercentage));
		$TaxTotal->TaxSubtotal->TaxCategory->TaxScheme->addChild('Name', $this->get_tax_name($taxPercentage));

		//Calculate invoice total
		$LegalMonetaryTotal = $szamla->addChild('LegalMonetaryTotal');
		$LegalMonetaryTotal->addChild('LineExtensionAmount', 0);
		$LegalMonetaryTotal->addChild('TaxExclusiveAmount', round(($order->get_total() - $order->get_total_tax())*$multiplier,2));
		$LegalMonetaryTotal->addChild('TaxInclusiveAmount', round($order->get_total()*$multiplier,2));
		$LegalMonetaryTotal->addChild('PayableAmount', round($order->get_total()*$multiplier,2));

		//Invoice items

		//Products
		$orderItemId = 1;
		foreach( $order_items as $termek ) {

			$lineitem = $this->create_line_item_xml_object(array(
				'id' => $orderItemId,
				'tax' => round($termek->get_total_tax()*$multiplier,2),
				'total' => round(($termek->get_total()+$termek->get_total_tax())*$multiplier,2),
				'qty' => $termek->get_quantity()*$multiplier,
				'name' => $termek->get_name(),
				'note' => $termek->get_product()->get_meta('wc_ebiz_megjegyzes'),
				'product' => $termek,
				'taxPercentage' => $taxPercentage
			));

			$szamla->appendXML($lineitem);
			$orderItemId++;
		}

		//Shipping
		if($order->get_shipping_methods()) {
			$lineitem = $this->create_line_item_xml_object(array(
				'id' => $orderItemId,
				'tax' => round($order->get_shipping_tax()*$multiplier,2),
				'total' => round(($order->get_shipping_tax()+$order->get_shipping_total())*$multiplier,2),
				'name' => $order->get_shipping_method(),
				'taxPercentage' => $taxPercentage
			));

			$szamla->appendXML($lineitem);
			$orderItemId++;
		}

		//Extra Fees
		$fees = $order->get_fees();
		if(!empty($fees)) {
			foreach( $fees as $fee ) {
				$lineitem = $this->create_line_item_xml_object(array(
					'id' => $orderItemId,
					'tax' => round($fee->get_total_tax()*$multiplier,2),
					'total' => round(($fee->get_total()+$fee->get_total_tax())*$multiplier,2),
					'name' => $fee->get_name(),
					'taxPercentage' => $taxPercentage
				));

				$szamla->appendXML($lineitem);
				$orderItemId++;
			}
		}

		//Append parameter block id
		$xmlparameters = new SimpleXMLElement('<parameters><block_id>EBIZ_EXTERNAL_INV_NAV_BLOCK</block_id></parameters>');

		//Generate XML
		$xml_szamla = apply_filters('wc_ebiz_xml',$szamla,$order);
		$xml = $xml_szamla->asXML();

		//Setup response
		$soap_response = false;
		$response = array();
		$response['error'] = false;

		//Validate with XSD
		$doc = new DOMDocument();
		$doc->loadXML($xml);
		$is_valid_xml = $doc->schemaValidate(plugin_dir_path( __FILE__ ) . 'includes/xsd/ebiz_external_inv_nav_v1.0_schema_validator.xsd');
		if (!$is_valid_xml){
			$response['error'] = true;
			$response['messages'][] = 'Hibaüzenet: Az eBiz felé küldött XML formátuma nem megfelelő';

			//Update order notes
			$order->add_order_note( __( 'ebiz.hu számlakészítés sikertelen! Az eBiz felé küldött XML formátuma nem megfelelő.', 'wc-ebiz' ));

			//Return response
			return $response;
		}

		//E-mail notice
		$postparam3_name = '';
		$postparam3_value = '';
		if($this->get_option('auto_email') == 'yes') {
			$postparam3_name = 'deliverylist';
			$email_data = new WCeBizSimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><deliverylist></deliverylist>');
			$email_data->addChild('delivery');
			$email_data->delivery->addChild('mode', 'MAIL_PSEUDOSOAP');
			$email_data->delivery->addChild('source', 'result');
			$email_data->delivery->addChild('address', $order->get_billing_email());
			$postparam3_value = $email_data->asXML();
		}

		//Try to generate invoice
		$soap_request = $this->soap_client->DocProcessor($this->get_authentication_details(), 'ebiz_external_inv_nav', 'store_document', false, 'xmldocument', $xml, 'xmlparameters', $xmlparameters->asXML(), $postparam3_name, $postparam3_value, 'webshop', 'woocommerce');

		//Store XML for debug
		if($this->get_option('debug') == 'yes') {
			$UploadDir = wp_upload_dir();
			$UploadURL = $UploadDir['basedir'];
			$location  = realpath($UploadURL . "/wc_ebiz/");
			$xmlfile = $location.'/'.$orderId.'-'.substr(md5(rand()),5).'.xml';
			$test = file_put_contents($xmlfile, $xml);
		}

		//Convert soap response to object(its a json string)
		if($soap_request && $soap_request->result) {
			$soap_response = json_decode($soap_request->result, true);
		}

		//Check if invoice generation failed
		if(!$soap_response || !$soap_response['success']) {
			$response['error'] = true;

			//Create error message
			if(!is_null($soap_response['errorobject'][0])) {
				$errorCode = $soap_response['errorcode'].'/'.$soap_response['errorobject'][0]['code'];
				$errorMsg = $soap_response['errorobject'][0]['message'];
			} else {
				$errorCode = $soap_response['errorcode'].'/'.$soap_response['errorobject']['error'];
				$errorMsg = $soap_response['errorobject']['error_msg'];
			}

			$response['messages'][] = 'Hibakód: '.$errorCode;
			$response['messages'][] = 'Hibaüzenet: '.$errorMsg;

			//Update order notes
			$order->add_order_note( __( 'ebiz.hu számlakészítés sikertelen! Hibakód: ', 'wc-ebiz' ).$errorCode.' - '.$errorMsg );

			//Run action for third-party plugins
			do_action('wc_ebiz_after_invoice_error', $order, $response, $soap_response['errorobject']);

			//Return response
			return $response;
		}

		//Download & Store PDF - generate a random file name so it will be downloadable later only by you
		$random_file_name = substr(md5(rand()),5);
		$pdf_file_name = 'szamla_'.$random_file_name.'_'.$orderId.'.pdf';
		$pdf_file = $location.'/'.$pdf_file_name;
		file_put_contents($pdf_file, pack("H*", $soap_response['rendereddoc']));

		//Get the invoice number
		$response['invoice_name'] = $this->get_invoice_number($order);
		$response['invoice_id'] = sanitize_text_field($soap_response['docid']);

		//We sent an email?
		$auto_email_sent = $this->get_option('auto_email', 'yes');

		//Save data
		if($payment_request) {
			if($auto_email_sent == 'yes') {
				$response['messages'][] = __('Díjbekérő sikeresen létrehozva és elküldve a vásárlónak emailben.','wc-ebiz');
			} else {
				$response['messages'][] = __('Díjbekérő sikeresen létrehozva.','wc-ebiz');
			}

			//Store as a custom field
			update_post_meta( $orderId, '_wc_ebiz_dijbekero', $response['invoice_name'] );
			update_post_meta( $orderId, '_wc_ebiz_dijbekero_id', $response['invoice_id'] );

			//Update order notes
			$order->add_order_note( __( 'eBiz díjbekérő sikeresen létrehozva. A számla sorszáma: ', 'wc-ebiz' ).$response['invoice_name'] );

			//Store the filename
			update_post_meta( $orderId, '_wc_ebiz_dijbekero_pdf', $pdf_file_name );

		} elseif($sztorno_szamla) {
			$response['messages'][] = __('Sztornó számla sikeresen létrehozva.','wc-ebiz');

			//Store as a custom field
			update_post_meta( $orderId, '_wc_ebiz_sztorno', $response['invoice_name'] );
			update_post_meta( $orderId, '_wc_ebiz_sztorno_id', $response['invoice_id'] );

			//Delete other meta
			$order->delete_meta_data( '_wc_ebiz_dijbekero' );
			$order->delete_meta_data( '_wc_ebiz_dijbekero_id' );
			$order->delete_meta_data( '_wc_ebiz' );
			$order->delete_meta_data( '_wc_ebiz_id' );
			$order->save();

			//Update order notes
			$order->add_order_note( __( 'eBiz sztornó számla sikeresen létrehozva. A számla sorszáma: ', 'wc-ebiz' ).$response['invoice_name'] );

			//Store the filename
			update_post_meta( $orderId, '_wc_ebiz_sztorno_pdf', $pdf_file_name );

		} else {
			if($auto_email_sent == 'yes') {
				$response['messages'][] = __('Számla sikeresen létrehozva és elküldve a vásárlónak emailben.','wc-ebiz');
			} else {
				$response['messages'][] = __('Számla sikeresen létrehozva.','wc-ebiz');
			}

			//Store as a custom field
			update_post_meta( $orderId, '_wc_ebiz', $response['invoice_name'] );
			update_post_meta( $orderId, '_wc_ebiz_id', $response['invoice_id'] );

			//Update order notes
			$order->add_order_note( __( 'ebiz.hu számla sikeresen létrehozva. A számla sorszáma: ', 'wc-ebiz' ).$response['invoice_name'] );

			//Store the filename
			update_post_meta( $orderId, '_wc_ebiz_pdf', $pdf_file_name );
		}

		//Return the download url
		$button_label = __('Számla megtekintése','wc-ebiz');
		if($payment_request) {
			$pdf_url = $this->generate_download_link($orderId,'dijbekero');
		} elseif ($sztorno_szamla) {
			$pdf_url = $this->generate_download_link($orderId,'sztorno');
		} else {
			$pdf_url = $this->generate_download_link($orderId);
		}

		$response['link'] = '<p><a href="'.$pdf_url.'" id="wc_ebiz_download" class="button button-primary" target="_blank">'.$button_label.'</a></p>';

		do_action('wc_ebiz_after_invoice_success', $order, $response, $pdf_url);

		return $response;
	}

	//Mark invoice completed
	public function generate_invoice_complete($orderId) {
		$order = new WC_Order($orderId);
		$response = array();
		$response['error'] = false;

		$domain = 'https://app.otpebiz.hu';
		if($this->get_option('debug') == 'yes') {
			$domain = 'https://teszt.otpebiz.hu';
		}

		//Get session cookie
		$auth_response = wp_remote_post( $domain.'/signin', array(
			'method' => 'POST',
			'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
			'body' => json_encode(array( 'username' => $this->get_option('username'), 'password' => $this->get_option('password') )),
			'data_format' => 'body'
		));

		if ( is_wp_error( $auth_response ) ) {
			$response['error'] = true;
			$response['messages'][] = __( 'eBiz teljesítettnek jelölés sikertelen! Hibakód: ', 'wc-ebiz' ).$auth_response->get_error_message();
			$order->add_order_note( __( 'eBiz teljesítettnek jelölés sikertelen! Hibakód: ', 'wc-ebiz' ).$auth_response->get_error_message() );
			return $response;
		} else {

			//Get all pending invoices
			$pending_invoices = wp_remote_get( $domain.'/invoice/invoicesOut?filter%5B%5D=Status,in,Payable,Expired', array(
				'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
				'data_format' => 'body',
				'cookies' => $auth_response['cookies']
			));

			if ( is_wp_error( $pending_invoices ) ) {
				$response['error'] = true;
				$response['messages'][] = __( 'eBiz teljesítettnek jelölés sikertelen! Hibakód: ', 'wc-ebiz' ).$pending_invoices->get_error_message();
				$order->add_order_note( __( 'eBiz teljesítettnek jelölés sikertelen! Hibakód: ', 'wc-ebiz' ).$pending_invoices->get_error_message() );
				return $response;
			}

			$pending_invoices_content = wp_remote_retrieve_body( $pending_invoices );
			$pending_invoices_json = json_decode( $pending_invoices_content );
			$invoice_recordid = false;

			if(isset($pending_invoices_json->v_ebiz_invoice_out)) {
				foreach ($pending_invoices_json->v_ebiz_invoice_out as $invoice) {
					if($invoice->docid == get_post_meta($orderId,'_wc_ebiz_id',true)) {
						$invoice_recordid = $invoice->recordid;
					}
				}
			}

			if(!$invoice_recordid) {
				$response['error'] = true;
				$response['messages'][] = __( 'eBiz teljesítettnek jelölés sikertelen! Hibakód: ', 'wc-ebiz' ).$pending_invoices->get_error_message();
				$order->add_order_note( __( 'eBiz teljesítettnek jelölés sikertelen! Hibakód: ', 'wc-ebiz' ).$pending_invoices->get_error_message() );
				return $response;
			}

			$date_paid = $order->get_date_paid();
			$ConnectionDate = date('Y-m-d');
			if( ! empty( $date_paid) ){
				$ConnectionDate = $date_paid->date("Y-m-d");
			}

			 $response_reconciliation = wp_remote_post( $domain.'/invoice/reconciliation', array(
	 			'method' => 'POST',
	 			'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
	 			'body' => json_encode(array(
					'data' => array(
						'InvoiceId' => $invoice_recordid,
						"ConnectionDate" => $ConnectionDate,
						"ConnectionType" => $this->get_payment_means_code($order),
						"ConnectionAmount" => round($order->get_total(),2)
					),
					'log' => array(
						"partyName" => $this->get_customer_name($order),
						"dueDate" => date('Y-m-d'),
						"docType" => "ebiz_external_inv_nav"
					)
				)),
	 			'data_format' => 'body',
				'cookies' => $auth_response['cookies']
	 		));

			//Save data
			$response['messages'][] = __('Jóváírás sikeresen rögzítve.','wc-ebiz');

			//Store as a custom field
			update_post_meta( $orderId, '_wc_ebiz_jovairas', time() );

			//Update order notes
			$order->add_order_note( __( 'eBiz jóváírás sikeresen rögzítve', 'wc-ebiz' ) );

			//Text response
			$response['link'] = '<p>'.__('Jóváírás rögzítve','wc-ebiz').': '.date("Y-m-d").'</a></p>';

			return $response;
		}

	}

	//Autogenerate invoice
	public function on_order_complete( $order_id ) {

		//Only generate invoice, if it wasn't already generated & only if automatic invoice is enabled
		if(!$this->is_invoice_generated($order_id)) {
			$return_info = false;

			//Check if we need to generate an invoice or a receipt
			$return_info = $this->generate_invoice($order_id);

			//If credit entry enabled for payment method
			$order = new WC_Order($order_id);
			$payment_method = $order->get_payment_method();

			if($this->check_payment_method_options($payment_method, 'complete')) {
				if($this->is_invoice_generated($order_id)) {
					//$return_info = $this->generate_invoice_complete($order_id);
				}
			}

			//If there was an error while generating invoices automatically
			if($return_info && $return_info['error']) {
				$this->on_auto_invoice_error($order_id);
			}
		}

	}

	//Autogenerate invoice
	public function on_order_processing( $order_id ) {

		//Only generate invoice, if it wasn't already generated & only if automatic invoice is enabled
		$order = new WC_Order($order_id);
		$payment_method = $order->get_payment_method();

		if($this->check_payment_method_options($payment_method, 'request')) {
			if(!$this->is_invoice_generated($order_id)) {
				$return_info = $this->generate_invoice($order_id, 'proform');
			}
		}
	}

	public function on_order_deleted( $order_id ) {

		//Only generate sztornó, if regular invoice already generated & only if automatic invoice is enabled
		if($this->is_invoice_generated($order_id)) {
			$return_info = $this->generate_invoice($order_id, 'sztorno');
		}

	}

	//Send email on error
	public function on_auto_invoice_error( $order_id ) {
		update_option('_wc_ebiz_error', $order_id);

		//Check if we need to send an email todo
		if($this->get_option('error_email')) {
			$order = wc_get_order($order_id);
			$mailer = WC()->mailer();
			$content = wc_get_template_html( 'includes/emails/invoice-error.php', array(
				'order'         => $order,
				'email_heading' => __('Sikertelen számlakészítés', 'wc-ebiz'),
				'plain_text'    => false,
				'email'         => $mailer
			), '', plugin_dir_path( __FILE__ ) );
			$recipient = $this->get_option('error_email');
			$subject = __("Sikertelen számlakészítés", 'wc-ebiz');
			$headers = "Content-Type: text/html\r\n";
			$mailer->send( $recipient, $subject, $content, $headers );
		}

	}

	//Check if it was already generated or not
	public function is_invoice_generated( $order_id ) {
		$invoice_name = get_post_meta($order_id,'_wc_ebiz',true);
		$invoice_own = get_post_meta($order_id,'_wc_ebiz_own',true);
		if($invoice_name || $invoice_own) {
			return true;
		} else {
			return false;
		}
	}

	//Add icon to order list to show invoice
	public function add_listing_actions( $order ) {
		$order_id = $order->get_id();

		if($this->is_invoice_generated($order_id)):
		?>
			<a href="<?php echo $this->generate_download_link($order_id); ?>" class="button tips wc_ebiz_button" target="_blank" alt="" data-tip="<?php _e('ebiz.hu számla','wc-ebiz'); ?>">
				<img src="<?php echo wc_ebiz::$plugin_url . 'assets/images/invoice.svg'; ?>" alt="" width="12" height="16">
			</a>
		<?php
		endif;

		if(get_post_meta($order_id,'_wc_ebiz_dijbekero_pdf',true)):
		?>
			<a href="<?php echo $this->generate_download_link($order_id,'dijbekero'); ?>" class="button tips wc_ebiz_button" target="_blank" alt="" data-tip="<?php _e('ebiz.hu díjbekérő','wc-ebiz'); ?>">
				<img src="<?php echo wc_ebiz::$plugin_url . 'assets/images/payment_request.svg'; ?>" alt="" width="12" height="16">
			</a>
		<?php
		endif;
	}

	//Generate download url
	public function generate_download_link( $order_id, $type = false, $absolute = false) {
		if($order_id) {
			$pdf_name = '';
			if($type && $type == 'dijbekero') {
				$pdf_name = get_post_meta($order_id,'_wc_ebiz_dijbekero_pdf',true);
			} else if($type && $type == 'sztorno') {
				$pdf_name = get_post_meta($order_id,'_wc_ebiz_sztorno_pdf',true);
			} else {
				$pdf_name = get_post_meta($order_id,'_wc_ebiz_pdf',true);
			}

			if($pdf_name) {
				$UploadDir = wp_upload_dir();
				$UploadURL = $UploadDir['baseurl'];
				if($absolute) {
					$pdf_file_url = $UploadDir['basedir'].'/wc_ebiz/'.$pdf_name;
				} else {
					$pdf_file_url = $UploadURL.'/wc_ebiz/'.$pdf_name;
				}
				return $pdf_file_url;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	//Get available checkout methods and ayment gateways
	public function get_available_payment_gateways() {
		$available_gateways = WC()->payment_gateways->payment_gateways();
		$available = array();
		$available['none'] = __('Válassz fizetési módot','wc-ebiz');
		foreach ($available_gateways as $available_gateway) {
			$available[$available_gateway->id] = $available_gateway->title;
		}
		return $available;
	}

	//If the invoice is already generated without the plugin
	public function wc_ebiz_already() {
		check_ajax_referer( 'wc_already_invoice', 'nonce' );
		if( true ) {
			if ( !current_user_can( 'edit_shop_orders' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			$orderid = absint($_POST['order']);
			$note = sanitize_text_field($_POST['note']);
			update_post_meta( $orderid, '_wc_ebiz_own', $note );

			$response = array();
			$response['error'] = false;
			$response['messages'][] = __('Saját számla sikeresen hozzáadva.','wc-ebiz');
			$response['invoice_name'] = $note;

			wp_send_json_success($response);
		}
	}

	//If the invoice is already generated without the plugin, turn it off
	public function wc_ebiz_already_back() {
		check_ajax_referer( 'wc_already_invoice', 'nonce' );
		if( true ) {
			if ( !current_user_can( 'edit_shop_orders' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			$orderid = absint($_POST['order']);
			$note = sanitize_text_field($_POST['note']);
			update_post_meta( $orderid, '_wc_ebiz_own', '' );

			$response = array();
			$response['error'] = false;
			$response['messages'][] = __('Visszakapcsolás sikeres.','wc-ebiz');

			wp_send_json_success($response);
		}
	}

	//Add vat number field to checkout page
	public function add_vat_number_checkout_field($fields) {
		$show = true;

		if($this->get_option('vat_number_form_min') == 'yes' && WC()->cart->get_taxes_total() < 100000) {
			$show = false;
		}

		if($show) {
			$fields['billing']['adoszam'] = array(
				 'label'     => __('Adószám', 'wc-ebiz'),
				 'placeholder'   => _x('12345678-1-23', 'placeholder', 'wc-ebiz'),
				 'required'  => false,
				 'class'     => array('form-row-wide'),
				 'clear'     => true,
				 'priority'	 => 99
			);
		}

		return $fields;
	}

	public function add_vat_number_info_notice($checkout) {
		if(WC()->cart->get_taxes_total() > 100000) {
			wc_print_notice( $this->get_option('vat_number_notice'), 'notice' );
		}
	}

	public function save_vat_number( $order_id ) {
		if ( ! empty( $_POST['adoszam'] ) ) {
			update_post_meta( $order_id, 'adoszam', sanitize_text_field( $_POST['adoszam'] ) );
		}
	}

	public function display_vat_number($order){
		$order_id = $order->get_id();
		if($adoszam = get_post_meta( $order_id, 'adoszam', true )) {
			echo '<p><strong>'.__('Adószám').':</strong> ' . $adoszam . '</p>';
		}
	}

	//Generate Sztornó invoice with Ajax
	public function generate_invoice_sztorno_with_ajax() {
		check_ajax_referer( 'wc_generate_invoice', 'nonce' );
		if( true ) {
			$orderid = absint($_POST['order']);
			$return_info = $this->generate_invoice($orderid, 'sztorno');
			wp_send_json_success($return_info);
		}
	}

	//Add download icons to order details page
	public function ebiz_download_button($actions, $order) {
		$order_id = $order->get_id();
		if($this->get_option('customer_download','no') == 'yes') {

			//Add invoice link
			if(get_post_meta($order_id,'_wc_ebiz_pdf',true)) {
				$link = $this->generate_download_link($order_id);
				$actions['ebiz_pdf'] = array(
					'url'  => $link,
					'name' => __( 'Számla', 'wc_ebiz' )
				);
			}

			//Add payment request link
			if(get_post_meta($order_id,'_wc_ebiz_dijbekero_pdf',true)) {
				$link_request = $this->generate_download_link($order_id,'dijbekero');
				$actions['ebiz_pdf'] = array(
					'url'  => $link_request,
					'name' => __( 'Díjbekérő', 'wc_ebiz' )
				);
			}
		}
		return $actions;
	}

	//Get options stored
	public function get_option($key, $default = '') {
		$settings = get_option( 'woocommerce_wc_ebiz_settings', null );
		$value = $default;

		if($settings && isset($settings[$key])) {
			$value = $settings[$key];
		} else if(get_option($key)) {
			$value = get_option($key);
		}

		//Try to get password from wp-config
		if($key == 'password' && defined( 'WC_EBIZ_PASSWORD' )) {
			$value = WC_EBIZ_PASSWORD;
		}

		return $value;
	}

	//Email attachment
	public function email_attachment($attachments, $email_id, $order){
		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		if( $email_id === 'customer_completed_order' || $email_id === 'customer_invoice'){
			$pdf_url = $this->generate_download_link($order_id, false, true);
			if($pdf_url) $attachments[] = $pdf_url;
		}

		if( $email_id === 'customer_processing_order' || $email_id === 'customer_on_hold_order') {
			$pdf_url = $this->generate_download_link($order_id, 'dijbekero', true);
			if($pdf_url) $attachments[] = $pdf_url;
		}

		if( $email_id === 'customer_refunded_order' || $email_id === 'cancelled_order') {
			$pdf_url = $this->generate_download_link($order_id, 'sztorno', true);
			if($pdf_url) $attachments[] = $pdf_url;
		}
		return $attachments;
	}

	//Plugin links
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_ebiz' ) . '" aria-label="' . esc_attr__( 'OTP eBiz Beállítások', 'wc-ebiz' ) . '">' . esc_html__( 'Beállítások', 'wc-ebiz' ) . '</a>',
			'documentation' => '<a href="https://ebiz.visztpeter.me/dokumentacio/" target="_blank" aria-label="' . esc_attr__( 'OTP eBiz Dokumentáció', 'wc-ebiz' ) . '">' . esc_html__( 'Dokumentáció', 'wc-ebiz' ) . '</a>'
		);

		if (!get_option('_wc_ebiz_pro_enabled') ) {
			$action_links['get-pro'] = '<a target="_blank" rel="noopener noreferrer" style="color:#46b450;" href="https://ebiz.visztpeter.me/" aria-label="' . esc_attr__( 'OTP eBiz Pro verzió', 'wc-ebiz' ) . '">' . esc_html__( 'Pro verzió', 'wc-ebiz' ) . '</a>';
		}
		return array_merge( $action_links, $links );
	}

	public function get_invoice_type($order) {
		$electronic_invoice_type = 'P';
		if($this->get_option('invoice_type') != 'paper') {
			$electronic_invoice_type = 'E';
		}

		if($order->get_billing_company()) {
			if($this->get_option('invoice_type_company') == 'electronic') {
				$electronic_invoice_type = 'E';
			} elseif ($this->get_option('invoice_type_company') == 'paper') {
				$electronic_invoice_type = 'P';
			}
		}

		return $electronic_invoice_type;
	}

	public function check_payment_method_options($payment_method_id, $option) {
		$found = false;
		$payment_method_options = $this->get_option('wc_ebiz_payment_method_options');
		if(isset($payment_method_options[$payment_method_id]) && isset($payment_method_options[$payment_method_id][$option])) {
			$found = $payment_method_options[$payment_method_id][$option];
		}
		return $found;
	}

	public function get_payment_method_deadline($payment_method_id) {
		$deadline = $this->get_option('wc_ebiz_payment_deadline');
		$payment_method_options = $this->get_option('wc_ebiz_payment_method_options');
		if($payment_method_options && isset($payment_method_options[$payment_method_id]) && isset($payment_method_options[$payment_method_id]['deadline'])) {
			$deadline = $payment_method_options[$payment_method_id]['deadline'];
		}
		return $deadline;
	}

	public function get_issuer_xml_object() {

		//Seller data
		$AccountigSupplierParty = new WCeBizSimpleXMLElement('<AccountigSupplierParty></AccountigSupplierParty>');
		$AccountigSupplierParty->addChild('PartyName');
		$AccountigSupplierParty->PartyName->addChild('Name', $this->get_option('issuer_name'));

		//Vat number
		if($this->get_option('issuer_vat_number')) {
			$PartyTaxScheme = $AccountigSupplierParty->addChild('PartyTaxScheme');
			$PartyTaxScheme->addChild('CompanyID', $this->get_option('issuer_vat_number'));
			$PartyTaxScheme->addChild('TaxScheme');
			$PartyTaxScheme->TaxScheme->addChild('ID', 'VAT');
		}

		//EU vat number
		if($this->get_option('issuer_eu_vat_number')) {
			$PartyTaxScheme = $AccountigSupplierParty->addChild('PartyTaxScheme');
			$PartyTaxScheme->addChild('CompanyID', $this->get_option('issuer_eu_vat_number'));
			$PartyTaxScheme->addChild('TaxScheme');
			$PartyTaxScheme->TaxScheme->addChild('ID', 'EUVAT');
		}

		//Billing info
		$PartyLegalEntity = $AccountigSupplierParty->addChild('PartyLegalEntity');
		$PartyLegalEntity->addChild('RegistrationName', $this->get_option('issuer_name'));
		$RegistrationAddress = $PartyLegalEntity->addChild('RegistrationAddress');
		$RegistrationAddress->addChild('StreetName', '');
		$RegistrationAddress->addChild('AdditionalStreetName', '');
		$RegistrationAddress->addChild('BuildingNumber', '');
		$RegistrationAddress->addChild('CityName', $this->get_option('issuer_city'));
		$RegistrationAddress->addChild('PostalZone', $this->get_option('issuer_postcode'));
		$RegistrationAddress->addChild('AddressLine');
		$RegistrationAddress->AddressLine->addChild('Line', $this->get_option('issuer_address'));
		$RegistrationAddress->addChild('Country');
		$RegistrationAddress->Country->addChild('IdentificationCode', $this->get_option('issuer_country'));
		$RegistrationAddress->Country->addChild('Name', WC()->countries->countries[ $this->get_option('issuer_country') ]);
		$PartyLegalEntity->addChild('CompanyLegalForm', '');

		return $AccountigSupplierParty;
	}

	public function get_issuer_bank_account_xml_object() {
		$PayeeFinancialAccount = new WCeBizSimpleXMLElement('<PayeeFinancialAccount></PayeeFinancialAccount>');
		$PayeeFinancialAccount->addChild('ID', $this->get_option('issuer_bank_number'));
		$PayeeFinancialAccount->addChild('FinancialInstitutionBranch');
		$PayeeFinancialAccount->FinancialInstitutionBranch->addChild('ID', $this->get_option('issuer_bank_swift'));
		$PayeeFinancialAccount->FinancialInstitutionBranch->addChild('Name', $this->get_option('issuer_bank_name'));
		$PayeeFinancialAccount->addChild('PayeeFinancialAccountExtension');
		$PayeeFinancialAccount->PayeeFinancialAccountExtension->addChild('IBAN', $this->get_option('issuer_bank_iban'));
		return $PayeeFinancialAccount;
	}

	public function get_invoice_number($order) {
		$invoice_number = '';
		$placeholders = array(
			'{ev}' => $order->get_date_created()->date("Y"),
			'{honap}' => $order->get_date_created()->date("m"),
			'{nap}' => $order->get_date_created()->date("d"),
			'{rendelesszam}' => $order->get_order_number(),
		);

		if($this->get_option('invoice_number') && $this->get_option('invoice_number') != '') {
			$invoice_number = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $this->get_option('invoice_number'));
		}
		return $invoice_number;
	}

	public function get_exchange_rate_xml_object($currency) {
		$exchange_rate = get_transient( 'wc_ebiz_mnb_arfolyam_kozep_'.$currency );
		if(!$exchange_rate) {

			//Query MNB for new rates
			$client = new SoapClient("http://www.mnb.hu/arfolyamok.asmx?wsdl");
			$soap_response = $client->GetCurrentExchangeRates()->GetCurrentExchangeRatesResult;
			$xml = simplexml_load_string($soap_response);
			foreach($xml->Day->Rate as $rate) {
				$attributes = $rate->attributes();
				if((string)$attributes->curr == $currency) {
					$exchange_rate = (string) $rate;
					$exchange_rate = str_replace(',','.',$exchange_rate);
					set_transient( 'wc_ebiz_mnb_arfolyam_kozep_'.$currency, $exchange_rate, 60*60*12 );
				}
			}
		}

		//Create XML object
		$PricingExchangeRate = new WCeBizSimpleXMLElement('<PricingExchangeRate></PricingExchangeRate>');
		$PricingExchangeRate->addChild('SourceCurrencyCode', $currency);
		$PricingExchangeRate->addChild('TargetCurrencyCode', 'HUF');
		$PricingExchangeRate->addChild('CalculationRate', floatval($exchange_rate));
		$PricingExchangeRate->addChild('MathematicOperatorCode');

		return $PricingExchangeRate;
	}

	public function get_authentication_details() {
		$apikey = $this->get_option('api_key');
		$apikey = base64_decode($apikey);
		if($apikey) {
			$values = explode('|', $apikey);
			return array(
				'apikey' => $values[2],
				'identity' => $values[3],
				'username' => $values[0],
				'password' => $values[1],
				'dev_environment' => $this->get_option('debug')
			);
		} else {
			return false;
		}
	}

	public function get_tax_name($taxPercentage) {
		if($this->get_option('afakulcs')) {
			return $this->get_option('afakulcs');
		} else {
			return $taxPercentage.'%';
		}
	}

	public function create_line_item_xml_object($options) {
		$defaults  = array(
			'id' => 1,
			'tax' => 0,
			'total' => 0,
			'qty' => 1,
			'name' => '',
			'note' => false,
			'product' => false,
			'taxPercentage' => 0
		);
		$options = wp_parse_args( $options, $defaults );

		//Create line item
		$tetel = new WCeBizSimpleXMLElement('<InvoiceLine></InvoiceLine>');

		//Define line item ID(just a simple increment)
		$tetel->addChild('ID', $options['id']);

		//Check if we need to add a note
		if($options['note']) {
			$tetel->addChild('Note', $options['note']);
		}

		//Quantity
		$tetel->addChild('InvoicedQuantity', $options['qty']);

		//Quantity unit(it can be set globally and on product level too)
		if($options['product'] && $options['product']->get_product()->get_meta('wc_ebiz_mennyisegi_egyseg')) {
			$tetel->addChild('UnitCode', $termek->get_product()->get_meta('wc_ebiz_mennyisegi_egyseg'));
		} else if($this->get_option('mennyisegi_egyseg')) {
			$tetel->addChild('UnitCode', $this->get_option('mennyisegi_egyseg'));
		} else {
			$tetel->addChild('UnitCode', '');
		}

		//No idea what is this, but its a required field
		$tetel->addChild('LineExtensionAmount', 0);

		//VAT value
		$tetel->addChild('TaxAmount', $options['tax']);

		//Item data
		$tetel->addChild('Item');

		//Set name(can be change manually in product options)
		if($options['product'] && $options['product']->get_product()->get_meta('wc_ebiz_tetel_nev') && $options['product']->get_product()->get_meta('wc_ebiz_tetel_nev') != '') {
			$tetel->Item->addChild('Name', htmlspecialchars($options['product']->get_product()->get_meta('wc_ebiz_tetel_nev')));
		} else {
			$tetel->Item->addChild('Name', htmlspecialchars($options['name']));
		}

		//Set SKU
		if($options['product']) {
			$tetel->Item->addChild('SellersItemIdentification');
			$tetel->Item->SellersItemIdentification->addChild('ID', $options['product']->get_product()->get_sku());
		}

		//Set VAT type
		$tetel->Item->addChild('ClassifiedTaxCategory');
		$tetel->Item->ClassifiedTaxCategory->addChild('Percent', $options['taxPercentage']);
		$tetel->Item->ClassifiedTaxCategory->addChild('Name', $this->get_tax_name($options['taxPercentage']));

		//Set price
		$tetel->addChild('Price');
		$tetel->Price->addChild('PriceAmount', $options['total']);
		$tetel->Price->addChild('BaseQuantity', 0);

		return $tetel;
	}

	public function get_customer_name($order) {
		$name = ($order->get_billing_company() ? htmlspecialchars($order->get_billing_company(), ENT_XML1, 'UTF-8') : $order->get_billing_last_name().' '.$order->get_billing_first_name());

		//Switch name if needed
		if($this->get_option('switch_name_order') == 'yes') {
			$name = ($order->get_billing_company() ? htmlspecialchars($order->get_billing_company(), ENT_XML1, 'UTF-8') : $order->get_billing_first_name().' '.$order->get_billing_last_name());
		}

		//Add name after company name
		if($order->get_billing_company() && $this->get_option('append_company_name') == 'yes') {
			if($this->get_option('switch_name_order') == 'yes') {
				$name .= ' - '.$order->get_billing_first_name().' '.$order->get_billing_last_name();
			} else {
				$name .= ' - '.$order->get_billing_last_name().' '.$order->get_billing_first_name();
			}
		}

		return $name;
	}

	public function get_payment_means_code($order) {
		$order_payment_method = $order->get_payment_method();
		$ebiz_id = $this->check_payment_method_options($order_payment_method, 'ebiz_id');
		$reconciliation_ids = array("10" => 3, "30" => 5, "48" => 9, "zO" => 15, "zU" => 11, "zP" => 18, "zV" => 21);

		if (array_key_exists($ebiz_id, $reconciliation_ids)) {
			return $reconciliation_ids[$ebiz_id];
		} else {
			return 8;
		}
	}

}

//WC Detection
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ;
	}
}


//WooCommerce inactive notice.
function wc_ebiz_woocommerce_inactive_notice() {
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<div id="message" class="error"><p>';
		printf( __( 'A %1$sWooCommerce OTP eBiz inaktív%2$s. A %3$sWooCommerce bővítménynek %4$s aktívnak kell lennie. %5$sTelepítsd és kapcsold be WooCommerce-et &raquo;%6$s', 'wc-ebiz' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
		echo '</p></div>';
	}
}

//Initialize
if ( is_woocommerce_active() ) {
	$GLOBALS['wc_ebiz'] = new wc_eBiz();
} else {
	add_action( 'admin_notices', 'wc_ebiz_woocommerce_inactive_notice' );
}
