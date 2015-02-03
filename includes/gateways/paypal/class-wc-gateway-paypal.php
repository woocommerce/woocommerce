<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayPal Standard Payment Gateway
 *
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class 		WC_Paypal
 * @extends		WC_Gateway_Paypal
 * @version		2.3.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Gateway_Paypal extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'paypal';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Proceed to PayPal', 'woocommerce' );
		$this->method_title       = __( 'PayPal', 'woocommerce' );
		$this->method_description = __( 'PayPal standard works by sending customers to PayPal where they can enter their payment information.', 'woocommerce' );
		$this->supports           = array(
			'products',
			'refunds'
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title                        = $this->get_option( 'title' );
		$this->description                  = $this->get_option( 'description' );
		$this->testmode                     = 'yes' === $this->get_option( 'testmode', 'no' );
		$this->email                        = $this->get_option( 'email' );
		$this->receiver_email               = $this->get_option( 'receiver_email', $this->email );
		$this->identity_token               = $this->get_option( 'identity_token' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		} else {
			include_once( 'includes/class-wc-gateway-paypal-ipn-handler.php' );
			new WC_Gateway_Paypal_IPN_Handler( $this->testmode, $this->receiver_email );

			if ( $this->identity_token ) {
				include_once( 'includes/class-wc-gateway-paypal-pdt-handler.php' );
				new WC_Gateway_Paypal_PDT_Handler( $this->testmode, $this->identity_token );
			}
		}
	}

	/**
	 * Logging method
	 * @param  string $message
	 */
	public function log( $message ) {
		if ( $this->testmode ) {
			if ( empty( $this->log ) ) {
				$this->log = new WC_Logger();
			}
			$this->log->add( 'paypal', $message );
		}
	}

	/**
	 * get_icon function.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon_html = '';
		$icon      = (array) $this->get_icon_image( WC()->countries->get_base_country() );

		foreach ( $icon as $i ) {
			$icon_html .= '<img src="' . esc_attr( $i ) . '" alt="' . __( 'PayPal Acceptance Mark', 'woocommerce' ) . '" />';
		}

		$icon_html .= sprintf( '<a href="%1$s" class="about_paypal" onclick="javascript:window.open(\'%1$s\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is PayPal?', 'woocommerce' ) . '">' . esc_attr__( 'What is PayPal?', 'woocommerce' ) . '</a>', esc_url( $this->get_icon_url( WC()->countries->get_base_country() ) ) );

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

	/**
	 * Get the link for an icon based on country
	 * @param  string $country
	 * @return string
	 */
	private function get_icon_url( $country ) {
		switch ( $country ) {
			case 'MX' :
				$link = 'https://www.paypal.com/mx/cgi-bin/webscr?cmd=xpt/Marketing/general/WIPaypal-outside';
			break;
			default :
				$link = 'https://www.paypal.com/' . strtolower( $country ) . '/webapps/mpp/paypal-popup';
			break;
		}
		return $link;
	}

	/**
	 * Get PayPal images for a country
	 * @param  string $country
	 * @return array of image URLs
	 */
	private function get_icon_image( $country ) {
		switch ( $country ) {
			case 'US' :
			case 'NZ' :
			case 'CZ' :
			case 'HU' :
			case 'MY' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
			break;
			case 'TR' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_odeme_secenekleri.jpg';
			break;
			case 'GB' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/Logo/AM_mc_vs_ms_ae_UK.png';
			break;
			case 'MX' :
				$icon = array(
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_visa_mastercard_amex.png',
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_debit_card_275x60.gif'
				);
			break;
			case 'FR' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_moyens_paiement_fr.jpg';
			break;
			case 'AU' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_AU/mktg/logo/Solutions-graphics-1-184x80.jpg';
			break;
			case 'DK' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_PayPal_betalingsmuligheder_dk.jpg';
			break;
			case 'RU' :
				$icon = 'https://www.paypalobjects.com/webstatic/ru_RU/mktg/business/pages/logo-center/AM_mc_vs_dc_ae.jpg';
			break;
			case 'NO' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/banner_pl_just_pp_319x110.jpg';
			break;
			case 'CA' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_CA/mktg/logo-image/AM_mc_vs_dc_ae.jpg';
			break;
			case 'HK' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_HK/mktg/logo/AM_mc_vs_dc_ae.jpg';
			break;
			case 'SG' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_SG/mktg/Logos/AM_mc_vs_dc_ae.jpg';
			break;
			case 'TW' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_TW/mktg/logos/AM_mc_vs_dc_ae.jpg';
			break;
			case 'TH' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_TH/mktg/Logos/AM_mc_vs_dc_ae.jpg';
			break;
			default :
				$icon = WC_HTTPS::force_https_url( WC()->plugin_url() . '/includes/gateways/paypal/assets/images/paypal.png' );
			break;
		}
		return apply_filters( 'woocommerce_paypal_icon', $icon );
	}

	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paypal_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) );
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'PayPal does not support your store currency.', 'woocommerce' ); ?></p></div>
			<?php
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = include( 'includes/settings-paypal.php' );
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order
	 *
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		if ( $this->testmode ) {
			$this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		} else {
			$this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		}
		return parent::get_transaction_url( $order );
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		include_once( 'includes/class-wc-gateway-paypal-request.php' );

		$order          = wc_get_order( $order_id );
		$paypal_request = new WC_Gateway_Paypal_Request( $this );

		return array(
			'result'   => 'success',
			'redirect' => $paypal_request->get_request_url( $order, $this->testmode )
		);
	}

	/**
	 * Can the order be refunded via paypal?
	 * @param  WC_Order $order
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order && $order->get_transaction_id();
	}

	/**
	 * Process a refund if supported
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return  boolean True or false based on success, or a WP_Error object
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			$this->log( 'Refund Failed: No transaction ID' );
			return false;
		}

		include_once( 'includes/class-wc-gateway-paypal-refund.php' );

		WC_Gateway_Paypal_Refund::$api_username  = $this->get_option( 'api_username' );
		WC_Gateway_Paypal_Refund::$api_password  = $this->get_option( 'api_password' );
		WC_Gateway_Paypal_Refund::$api_signature = $this->get_option( 'api_signature' );

		$result = WC_Gateway_Paypal_Refund::refund_order( $order, $amount, $reason, $this->testmode );

		if ( is_wp_error( $result ) ) {
			$this->log( 'Refund Failed: ' . $result->get_error_message() );
			return false;
		}

		$this->log( 'Refund Result: ' . print_r( $result, true ) );

		switch ( strtolower( $result['ACK'] ) ) {
			case 'success':
			case 'successwithwarning':
				$order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'woocommerce' ), $result['GROSSREFUNDAMT'], $result['REFUNDTRANSACTIONID'] ) );
				return true;
			break;
		}

		return false;
	}
}
