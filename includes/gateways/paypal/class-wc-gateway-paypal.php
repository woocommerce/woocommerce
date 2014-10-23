<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * PayPal Standard Payment Gateway
 *
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class 		WC_Paypal
 * @extends		WC_Gateway_Paypal
 * @version		2.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Gateway_Paypal extends WC_Payment_Gateway {

	var $notify_url;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'paypal';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Proceed to PayPal', 'woocommerce' );
		$this->liveurl            = 'https://www.paypal.com/cgi-bin/webscr';
		$this->testurl            = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		$this->method_title       = __( 'PayPal', 'woocommerce' );
		$this->method_description = __( 'PayPal standard works by sending the user to PayPal to enter their payment information.', 'woocommerce' );
		$this->notify_url         = WC()->api_request_url( 'WC_Gateway_Paypal' );
		$this->supports           = array(
			'products',
			'refunds'
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title 			= $this->get_option( 'title' );
		$this->description 		= $this->get_option( 'description' );
		$this->email 			= $this->get_option( 'email' );
		$this->receiver_email   = $this->get_option( 'receiver_email', $this->email );
		$this->testmode			= $this->get_option( 'testmode' );
		$this->send_shipping	= $this->get_option( 'send_shipping' );
		$this->address_override	= $this->get_option( 'address_override' );
		$this->debug			= $this->get_option( 'debug' );
		$this->page_style 		= $this->get_option( 'page_style' );
		$this->invoice_prefix	= $this->get_option( 'invoice_prefix', 'WC-' );
		$this->paymentaction    = $this->get_option( 'paymentaction', 'sale' );
		$this->identity_token   = $this->get_option( 'identity_token', '' );
		$this->api_username 	= $this->get_option( 'api_username' );
		$this->api_password 	= $this->get_option( 'api_password' );
		$this->api_signature 	= $this->get_option( 'api_signature' );

		// Logs
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		// Actions
		add_action( 'valid-paypal-standard-ipn-request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_paypal', array( $this, 'pdt_return_handler' ) );

		// Payment listener/API hook
		add_action( 'woocommerce_api_wc_gateway_paypal', array( $this, 'check_ipn_response' ) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		}
	}

	/**
	 * get_icon function.
	 *
	 * @return string
	 */
	public function get_icon() {
		$link = null;
		switch ( WC()->countries->get_base_country() ) {
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
				$link = 'https://www.paypal.com/mx/cgi-bin/webscr?cmd=xpt/Marketing/general/WIPaypal-outside';
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
				$link = null;
			break;
		}

		if ( is_null( $link ) ) {
			$link = 'https://www.paypal.com/' . strtolower( WC()->countries->get_base_country() ) . '/webapps/mpp/paypal-popup';
		}

		if ( is_array( $icon ) ) {
			$icon_html = '';
			foreach ( $icon as $i ) {
				$icon_html .= '<img src="' . esc_attr( $i ) . '" alt="PayPal Acceptance Mark" />';
			}
		} else {
			$icon_html = '<img src="' . esc_attr( apply_filters( 'woocommerce_paypal_icon', $icon ) ) . '" alt="PayPal Acceptance Mark" />';
		}

		if ( $link ) {
			$what_is_paypal = sprintf( '<a href="%1$s" class="about_paypal" onclick="javascript:window.open(\'%1$s\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is PayPal?', 'woocommerce' ) . '">' . esc_attr__( 'What is PayPal?', 'woocommerce' ) . '</a>', esc_url( $link ) );
		} else {
			$what_is_paypal = '';
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon_html . $what_is_paypal, $this->id );
	}

	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @return bool
	 */
	function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paypal_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) ) ) {
			return false;
		}

		return true;
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
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayPal standard', 'woocommerce' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'PayPal', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.', 'woocommerce' )
			),
			'email' => array(
				'title'       => __( 'PayPal Email', 'woocommerce' ),
				'type'        => 'email',
				'description' => __( 'Please enter your PayPal email address; this is needed in order to take payment.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => 'you@youremail.com'
			),
			'testmode' => array(
				'title'       => __( 'PayPal sandbox', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable PayPal sandbox', 'woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'woocommerce' ), 'https://developer.paypal.com/' ),
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log PayPal events, such as IPN requests, inside <code>%s</code>', 'woocommerce' ), wc_get_log_file_path( 'paypal' ) )
			),
			'shipping' => array(
				'title'       => __( 'Shipping options', 'woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'send_shipping' => array(
				'title'       => __( 'Shipping details', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Send shipping details to PayPal instead of billing.', 'woocommerce' ),
				'description' => __( 'PayPal allows us to send 1 address. If you are using PayPal for shipping labels you may prefer to send the shipping address rather than billing.', 'woocommerce' ),
				'default'     => 'no'
			),
			'address_override' => array(
				'title'       => __( 'Address override', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable "address_override" to prevent address information from being changed.', 'woocommerce' ),
				'description' => __( 'PayPal verifies addresses therefore this setting can cause errors (we recommend keeping it disabled).', 'woocommerce' ),
				'default'     => 'no'
			),
			'advanced' => array(
				'title'       => __( 'Advanced options', 'woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'receiver_email' => array(
				'title'       => __( 'Receiver Email', 'woocommerce' ),
				'type'        => 'email',
				'description' => __( 'If your main PayPal email differs from the PayPal email entered above, input your main receiver email for your PayPal account here. This is used to validate IPN requests.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => 'you@youremail.com'
			),
			'invoice_prefix' => array(
				'title'       => __( 'Invoice Prefix', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'woocommerce' ),
				'default'     => 'WC-',
				'desc_tip'    => true,
			),
			'paymentaction' => array(
				'title'       => __( 'Payment Action', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce' ),
				'default'     => 'sale',
				'desc_tip'    => true,
				'options'     => array(
					'sale'          => __( 'Capture', 'woocommerce' ),
					'authorization' => __( 'Authorize', 'woocommerce' )
				)
			),
			'page_style' => array(
				'title'       => __( 'Page Style', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Optionally enter the name of the page style you wish to use. These are defined within your PayPal account.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Optional', 'woocommerce' )
			),
			'identity_token' => array(
				'title'       => __( 'PayPal Identity Token', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Optionally enable "Payment Data Transfer" (Profile > Website Payment Preferences) and then copy your identity token here. This will allow payments to be verified without the need for PayPal IPN.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Optional', 'woocommerce' )
			),
			'api_details' => array(
				'title'       => __( 'API Credentials', 'woocommerce' ),
				'type'        => 'title',
				'description' => sprintf( __( 'Enter your PayPal API credentials to process refunds via PayPal. Learn how to access your PayPal API Credentials %shere%s.', 'woocommerce' ), '<a href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/#creating-classic-api-credentials">', '</a>' ),
			),
			'api_username' => array(
				'title'       => __( 'API Username', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Optional', 'woocommerce' )
			),
			'api_password' => array(
				'title'       => __( 'API Password', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Optional', 'woocommerce' )
			),
			'api_signature' => array(
				'title'       => __( 'API Signature', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => __( 'Optional', 'woocommerce' )
			),
		);
	}

	/**
	 * Limit the length of item names
	 * @param  string $item_name
	 * @return string
	 */
	public function paypal_item_name( $item_name ) {
		if ( strlen( $item_name ) > 127 ) {
			$item_name = substr( $item_name, 0, 124 ) . '...';
		}
		return html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Get PayPal Args for passing to PP
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	function get_paypal_args( $order ) {

		$order_id = $order->id;

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'paypal', 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url );
		}

		if ( in_array( $order->billing_country, array( 'US','CA' ) ) ) {
			$order->billing_phone = str_replace( array( '(', '-', ' ', ')', '.' ), '', $order->billing_phone );
			$phone_args = array(
				'night_phone_a' => substr( $order->billing_phone, 0, 3 ),
				'night_phone_b' => substr( $order->billing_phone, 3, 3 ),
				'night_phone_c' => substr( $order->billing_phone, 6, 4 ),
				'day_phone_a' 	=> substr( $order->billing_phone, 0, 3 ),
				'day_phone_b' 	=> substr( $order->billing_phone, 3, 3 ),
				'day_phone_c' 	=> substr( $order->billing_phone, 6, 4 )
			);
		} else {
			$phone_args = array(
				'night_phone_b' => $order->billing_phone,
				'day_phone_b' 	=> $order->billing_phone
			);
		}

		// PayPal Args
		$paypal_args = array_merge(
			array(
				'cmd'           => '_cart',
				'business'      => $this->email,
				'no_note'       => 1,
				'currency_code' => get_woocommerce_currency(),
				'charset'       => 'utf-8',
				'rm'            => is_ssl() ? 2 : 1,
				'upload'        => 1,
				'return'        => esc_url( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) ),
				'cancel_return' => esc_url( $order->get_cancel_order_url() ),
				'page_style'    => $this->page_style,
				'paymentaction' => $this->paymentaction,
				'bn'            => 'WooThemes_Cart',

				// Order key + ID
				'invoice'       => $this->invoice_prefix . ltrim( $order->get_order_number(), '#' ),
				'custom'        => serialize( array( $order_id, $order->order_key ) ),

				// IPN
				'notify_url'    => $this->notify_url,

				// Billing Address info
				'first_name'    => $order->billing_first_name,
				'last_name'     => $order->billing_last_name,
				'company'       => $order->billing_company,
				'address1'      => $order->billing_address_1,
				'address2'      => $order->billing_address_2,
				'city'          => $order->billing_city,
				'state'         => $this->get_paypal_state( $order->billing_country, $order->billing_state ),
				'zip'           => $order->billing_postcode,
				'country'       => $order->billing_country,
				'email'         => $order->billing_email
			),
			$phone_args
		);

		// Shipping
		if ( 'yes' == $this->send_shipping ) {
			$paypal_args['address_override'] = ( $this->address_override == 'yes' ) ? 1 : 0;

			$paypal_args['no_shipping'] = 0;

			// If we are sending shipping, send shipping address instead of billing
			$paypal_args['first_name']		= $order->shipping_first_name;
			$paypal_args['last_name']		= $order->shipping_last_name;
			$paypal_args['company']			= $order->shipping_company;
			$paypal_args['address1']		= $order->shipping_address_1;
			$paypal_args['address2']		= $order->shipping_address_2;
			$paypal_args['city']			= $order->shipping_city;
			$paypal_args['state']			= $this->get_paypal_state( $order->shipping_country, $order->shipping_state );
			$paypal_args['country']			= $order->shipping_country;
			$paypal_args['zip']				= $order->shipping_postcode;
		} else {
			$paypal_args['no_shipping'] = 1;
		}

		// Try to send line items, or default to sending the order as a whole
		if ( $line_items = $this->get_line_items( $order ) ) {
			$paypal_args = array_merge( $paypal_args, $line_items );
		} else {
			// Don't pass items - paypal borks tax due to prices including tax. PayPal has no option for tax inclusive pricing sadly. Pass 1 item for the order items overall
			$item_names = array();

			if ( sizeof( $order->get_items() ) > 0 ) {
				foreach ( $order->get_items() as $item ) {
					if ( $item['qty'] ) {
						$item_names[] = $item['name'] . ' x ' . $item['qty'];
					}
				}
			}

			$paypal_args['item_name_1'] = $this->paypal_item_name( sprintf( __( 'Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode( ', ', $item_names ) );
			$paypal_args['quantity_1']  = '1';
			$paypal_args['amount_1']    = number_format( $order->get_total() - round( $order->get_total_shipping() + $order->get_shipping_tax(), 2 ) + $order->get_order_discount(), 2, '.', '' );

			// Shipping Cost
			// No longer using shipping_1 because
			//		a) paypal ignore it if *any* shipping rules are within paypal
			//		b) paypal ignore anything over 5 digits, so 999.99 is the max
			if ( ( $order->get_total_shipping() + $order->get_shipping_tax() ) > 0 ) {
				$paypal_args['item_name_2'] = $this->paypal_item_name( __( 'Shipping via', 'woocommerce' ) . ' ' . ucwords( $order->get_shipping_method() ) );
				$paypal_args['quantity_2'] 	= '1';
				$paypal_args['amount_2'] 	= number_format( $order->get_total_shipping() + $order->get_shipping_tax(), 2, '.', '' );
			}

			// Discount
			if ( $order->get_order_discount() ) {
				$paypal_args['discount_amount_cart'] = $order->get_order_discount();
			}
		}

		$paypal_args = apply_filters( 'woocommerce_paypal_args', $paypal_args );

		return $paypal_args;
	}

	/**
	 * Get line items to send to paypal
	 *
	 * @param  WC_Order $order
	 * @return array on success, or false when it is not possible to send line items
	 */
	private function get_line_items( $order ) {
		// Do not send lines for tax inclusive prices
		if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) && 'yes' === get_option( 'woocommerce_prices_include_tax' ) ) {
			return false;
		}

		// Do not send lines when order discount is present, or too many line items in the order.
		if ( $order->get_order_discount() > 0 || ( sizeof( $order->get_items() ) + sizeof( $order->get_fees() ) ) >= 9 ) {
			return false;
		}

		$item_loop        = 0;
		$args             = array();
		$args['tax_cart'] = $order->get_total_tax();

		// Products
		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				if ( ! $item['qty'] ) {
					continue;
				}
				$item_loop ++;
				$product   = $order->get_product_from_item( $item );
				$item_name = $item['name'];
				$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );

				if ( $meta = $item_meta->display( true, true ) ) {
					$item_name .= ' ( ' . $meta . ' )';
				}

				$args[ 'item_name_' . $item_loop ] = $this->paypal_item_name( $item_name );
				$args[ 'quantity_' . $item_loop ]  = $item['qty'];
				$args[ 'amount_' . $item_loop ]    = $order->get_item_subtotal( $item, false );

				if ( $args[ 'amount_' . $item_loop ] < 0 ) {
					return false; // Abort - negative line
				}

				if ( $product->get_sku() ) {
					$args[ 'item_number_' . $item_loop ] = $product->get_sku();
				}
			}
		}

		// Discount
		if ( $order->get_cart_discount() > 0 ) {
			$args['discount_amount_cart'] = round( $order->get_cart_discount(), 2 );
		}

		// Fees
		if ( sizeof( $order->get_fees() ) > 0 ) {
			foreach ( $order->get_fees() as $item ) {
				$item_loop ++;
				$args[ 'item_name_' . $item_loop ] = $this->paypal_item_name( $item['name'] );
				$args[ 'quantity_' . $item_loop ]  = 1;
				$args[ 'amount_' . $item_loop ]    = $item['line_total'];

				if ( $args[ 'amount_' . $item_loop ] < 0 ) {
					return false; // Abort - negative line
				}
			}
		}

		// Shipping Cost item - paypal only allows shipping per item, we want to send shipping for the order
		if ( $order->get_total_shipping() > 0 ) {
			$item_loop ++;
			$args[ 'item_name_' . $item_loop ] = $this->paypal_item_name( sprintf( __( 'Shipping via %s', 'woocommerce' ), $order->get_shipping_method() ) );
			$args[ 'quantity_' . $item_loop ]  = '1';
			$args[ 'amount_' . $item_loop ]    = number_format( $order->get_total_shipping(), 2, '.', '' );
		}

		return $args;
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order       = wc_get_order( $order_id );
		$paypal_args = $this->get_paypal_args( $order );
		$paypal_args = http_build_query( $paypal_args, '', '&' );

		if ( 'yes' == $this->testmode ) {
			$paypal_adr = $this->testurl . '?test_ipn=1&';
		} else {
			$paypal_adr = $this->liveurl . '?';
		}

		return array(
			'result' 	=> 'success',
			'redirect'	=> $paypal_adr . $paypal_args
		);
	}

	/**
	 * Process a refund if supported
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return  bool|wp_error True or false based on success, or a WP_Error object
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! $order->get_transaction_id() || ! $this->api_username || ! $this->api_password || ! $this->api_signature ) {
			return false;
		}

		$post_data = array(
			'VERSION'       => '84.0',
			'SIGNATURE'     => $this->api_signature,
			'USER'          => $this->api_username,
			'PWD'           => $this->api_password,
			'METHOD'        => 'RefundTransaction',
			'TRANSACTIONID' => $order->get_transaction_id(),
			'REFUNDTYPE'    => is_null( $amount ) ? 'Full' : 'Partial'
		);

		if ( ! is_null( $amount ) ) {
			$post_data['AMT']          = number_format( $amount, 2, '.', '' );
			$post_data['CURRENCYCODE'] = $order->get_order_currency();
		}

		if ( $reason ) {
			if ( 255 < strlen( $reason ) ) {
				$reason = substr( $reason, 0, 252 ) . '...';
			}

			$post_data['NOTE'] = html_entity_decode( $reason, ENT_NOQUOTES, 'UTF-8' );
		}

		$response = wp_remote_post( 'yes' === $this->testmode ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp', array(
			'method'      => 'POST',
			'body'        => $post_data,
			'timeout'     => 70,
			'sslverify'   => false,
			'user-agent'  => 'WooCommerce',
			'httpversion' => '1.1'
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['body'] ) ) {
			return new WP_Error( 'paypal-error', __( 'Empty Paypal response.', 'woocommerce' ) );
		}

		parse_str( $response['body'], $parsed_response );

		switch ( strtolower( $parsed_response['ACK'] ) ) {
			case 'success':
			case 'successwithwarning':
				$order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'woocommerce' ), $parsed_response['GROSSREFUNDAMT'], $parsed_response['REFUNDTRANSACTIONID'] ) );
				return true;
			break;
		}

		return false;
	}

	/**
	 * Check PayPal IPN validity
	 **/
	public function check_ipn_request_is_valid( $ipn_response ) {
		// Get url
		if ( 'yes' == $this->testmode ) {
			$paypal_adr = $this->testurl;
		} else {
			$paypal_adr = $this->liveurl;
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'paypal', 'Checking IPN response is valid via ' . $paypal_adr . '...' );
		}

		// Get received values from post data
		$validate_ipn = array( 'cmd' => '_notify-validate' );
		$validate_ipn += stripslashes_deep( $ipn_response );

		// Send back post vars to paypal
		$params = array(
			'body' 			=> $validate_ipn,
			'sslverify' 	=> false,
			'timeout' 		=> 60,
			'httpversion'   => '1.1',
			'compress'      => false,
			'decompress'    => false,
			'user-agent'	=> 'WooCommerce/' . WC()->version
		);

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'paypal', 'IPN Request: ' . print_r( $params, true ) );
		}

		// Post back to get a response
		$response = wp_remote_post( $paypal_adr, $params );

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'paypal', 'IPN Response: ' . print_r( $response, true ) );
		}

		// check to see if the request was valid
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED' ) ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'paypal', 'Received valid response from PayPal' );
			}

			return true;
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'paypal', 'Received invalid response from PayPal' );
			if ( is_wp_error( $response ) ) {
				$this->log->add( 'paypal', 'Error response: ' . $response->get_error_message() );
			}
		}

		return false;
	}

	/**
	 * Check for PayPal IPN Response
	 */
	public function check_ipn_response() {

		@ob_clean();

		$ipn_response = ! empty( $_POST ) ? $_POST : false;

		if ( $ipn_response && $this->check_ipn_request_is_valid( $ipn_response ) ) {

			header( 'HTTP/1.1 200 OK' );

			do_action( "valid-paypal-standard-ipn-request", $ipn_response );

		} else {

			wp_die( "PayPal IPN Request Failure", "PayPal IPN", array( 'response' => 200 ) );

		}

	}

	/**
	 * Successful Payment!
	 *
	 * @param array $posted
	 */
	public function successful_request( $posted ) {

		$posted = stripslashes_deep( $posted );

		// Custom holds post ID
		if ( ! empty( $posted['invoice'] ) && ! empty( $posted['custom'] ) ) {

			$order = $this->get_paypal_order( $posted['custom'], $posted['invoice'] );

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'paypal', 'Found order #' . $order->id );
			}

			// Lowercase returned variables
			$posted['payment_status'] 	= strtolower( $posted['payment_status'] );
			$posted['txn_type'] 		= strtolower( $posted['txn_type'] );

			// Sandbox fix
			if ( 1 == $posted['test_ipn'] && 'pending' == $posted['payment_status'] ) {
				$posted['payment_status'] = 'completed';
			}

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'paypal', 'Payment status: ' . $posted['payment_status'] );
			}

			// We are here so lets check status and do actions
			switch ( $posted['payment_status'] ) {
				case 'completed' :
				case 'pending' :

					// Check order not already completed
					if ( $order->has_status( 'completed' ) ) {
						if ( 'yes' == $this->debug ) {
							$this->log->add( 'paypal', 'Aborting, Order #' . $order->id . ' is already complete.' );
						}
						exit;
					}

					// Check valid txn_type
					$accepted_types = array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' );

					if ( ! in_array( $posted['txn_type'], $accepted_types ) ) {
						if ( 'yes' == $this->debug ) {
							$this->log->add( 'paypal', 'Aborting, Invalid type:' . $posted['txn_type'] );
						}
						exit;
					}

					// Validate currency
					if ( $order->get_order_currency() != $posted['mc_currency'] ) {
						if ( 'yes' == $this->debug ) {
							$this->log->add( 'paypal', 'Payment error: Currencies do not match (sent "' . $order->get_order_currency() . '" | returned "' . $posted['mc_currency'] . '")' );
						}

						// Put this order on-hold for manual checking
						$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'woocommerce' ), $posted['mc_currency'] ) );
						exit;
					}

					// Validate amount
					if ( $order->get_total() != $posted['mc_gross'] ) {
						if ( 'yes' == $this->debug ) {
							$this->log->add( 'paypal', 'Payment error: Amounts do not match (gross ' . $posted['mc_gross'] . ')' );
						}

						// Put this order on-hold for manual checking
						$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'woocommerce' ), $posted['mc_gross'] ) );
						exit;
					}

					// Validate Email Address
					if ( strcasecmp( trim( $posted['receiver_email'] ), trim( $this->receiver_email ) ) != 0 ) {
						if ( 'yes' == $this->debug ) {
							$this->log->add( 'paypal', "IPN Response is for another one: {$posted['receiver_email']} our email is {$this->receiver_email}" );
						}

						// Put this order on-hold for manual checking
						$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal IPN response from a different email address (%s).', 'woocommerce' ), $posted['receiver_email'] ) );

						exit;
					}

					 // Store PP Details
					if ( ! empty( $posted['payer_email'] ) ) {
						update_post_meta( $order->id, 'Payer PayPal address', wc_clean( $posted['payer_email'] ) );
					}
					if ( ! empty( $posted['first_name'] ) ) {
						update_post_meta( $order->id, 'Payer first name', wc_clean( $posted['first_name'] ) );
					}
					if ( ! empty( $posted['last_name'] ) ) {
						update_post_meta( $order->id, 'Payer last name', wc_clean( $posted['last_name'] ) );
					}
					if ( ! empty( $posted['payment_type'] ) ) {
						update_post_meta( $order->id, 'Payment type', wc_clean( $posted['payment_type'] ) );
					}

					if ( $posted['payment_status'] == 'completed' ) {
						$order->add_order_note( __( 'IPN payment completed', 'woocommerce' ) );
						$txn_id = ( ! empty( $posted['txn_id'] ) ) ? wc_clean( $posted['txn_id'] ) : '';
						$order->payment_complete( $txn_id );
					} else {
						$order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce' ), $posted['pending_reason'] ) );
					}

					if ( 'yes' == $this->debug ) {
						$this->log->add( 'paypal', 'Payment complete.' );
					}

				break;
				case 'denied' :
				case 'expired' :
				case 'failed' :
				case 'voided' :
					// Order failed
					$order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );
				break;
				case 'refunded' :

					// Only handle full refunds, not partial
					if ( $order->get_total() == ( $posted['mc_gross'] * -1 ) ) {

						// Mark order as refunded
						$order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );

						$this->send_ipn_email_notification(
							sprintf( __( 'Payment for order %s refunded/reversed', 'woocommerce' ), $order->get_order_number() ),
							sprintf( __( 'Order %s has been marked as refunded - PayPal reason code: %s', 'woocommerce' ), $order->get_order_number(), $posted['reason_code'] )
						);
					}

				break;
				case 'reversed' :

					// Mark order as refunded
					$order->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );

					$this->send_ipn_email_notification(
						sprintf( __( 'Payment for order %s reversed', 'woocommerce' ), $order->get_order_number() ),
						sprintf(__( 'Order %s has been marked on-hold due to a reversal - PayPal reason code: %s', 'woocommerce' ), $order->get_order_number(), $posted['reason_code'] )
					);

				break;
				case 'canceled_reversal' :
					$this->send_ipn_email_notification(
						sprintf( __( 'Reversal cancelled for order %s', 'woocommerce' ), $order->get_order_number() ),
						sprintf( __( 'Order %s has had a reversal cancelled. Please check the status of payment and update the order status accordingly.', 'woocommerce' ), $order->get_order_number() )
					);
				break;
				default :
					// No action
				break;
			}

			exit;
		}
	}

	/**
	 * Send a notification to the user handling orders.
	 * @param  string $subject
	 * @param  string $message
	 */
	public function send_ipn_email_notification( $subject, $message ) {
		$new_order_settings = get_option( 'woocommerce_new_order_settings', array() );
		$mailer             = WC()->mailer();
		$message            = $mailer->wrap_message( $subject, $message );

		$mailer->send( ! empty( $new_order_settings['recipient'] ) ? $new_order_settings['recipient'] : get_option( 'admin_email' ), $subject, $message );
	}

	/**
	 * Return handler
	 *
	 * Alternative to IPN
	 */
	public function pdt_return_handler() {
		$posted = stripslashes_deep( $_REQUEST );

		if ( ! empty( $this->identity_token ) && ! empty( $posted['cm'] ) ) {

			$order = $this->get_paypal_order( $posted['cm'] );

			if ( ! $order->has_status( 'pending' ) ) {
				return false;
			}

			$posted['st'] = strtolower( $posted['st'] );

			switch ( $posted['st'] ) {
				case 'completed' :

					// Validate transaction
					if ( 'yes' == $this->testmode ) {
						$paypal_adr = $this->testurl;
					} else {
						$paypal_adr = $this->liveurl;
					}

					$pdt = array(
						'body' 			=> array(
							'cmd' => '_notify-synch',
							'tx'  => $posted['tx'],
							'at'  => $this->identity_token
						),
						'sslverify' 	=> false,
						'timeout' 		=> 60,
						'httpversion'   => '1.1',
						'user-agent'	=> 'WooCommerce/' . WC_VERSION
					);

					// Post back to get a response
					$response = wp_remote_post( $paypal_adr, $pdt );

					if ( is_wp_error( $response ) ) {
						return false;
					}

					if ( ! strpos( $response['body'], "SUCCESS" ) === 0 ) {
						return false;
					}

					// Validate Amount
					if ( $order->get_total() != $posted['amt'] ) {

						if ( 'yes' == $this->debug ) {
							$this->log->add( 'paypal', 'Payment error: Amounts do not match (amt ' . $posted['amt'] . ')' );
						}

						// Put this order on-hold for manual checking
						$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (amt %s).', 'woocommerce' ), $posted['amt'] ) );
						return true;

					} else {

						// Store PP Details
						$order->add_order_note( __( 'PDT payment completed', 'woocommerce' ) );
						$txn_id = ( ! empty( $posted['tx'] ) ) ? wc_clean( $posted['tx'] ) : '';
						$order->payment_complete( $txn_id );
						return true;
					}

				break;
			}
		}

		return false;
	}

	/**
	 * get_paypal_order function.
	 *
	 * @param  string $custom
	 * @param  string $invoice
	 * @return WC_Order object
	 */
	private function get_paypal_order( $custom, $invoice = '' ) {
		$custom = maybe_unserialize( $custom );

		// Backwards comp for IPN requests
		if ( is_numeric( $custom ) ) {
			$order_id  = (int) $custom;
			$order_key = $invoice;
		} elseif( is_string( $custom ) ) {
			$order_id  = (int) str_replace( $this->invoice_prefix, '', $custom );
			$order_key = $custom;
		} else {
			list( $order_id, $order_key ) = $custom;
		}

		$order = wc_get_order( $order_id );

		if ( ! isset( $order->id ) ) {
			// We have an invalid $order_id, probably because invoice_prefix has changed
			$order_id 	= wc_get_order_id_by_order_key( $order_key );
			$order 		= wc_get_order( $order_id );
		}

		// Validate key
		if ( $order->order_key !== $order_key ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'paypal', 'Error: Order Key does not match invoice.' );
			}
			exit;
		}

		return $order;
	}

	/**
	 * Get the state to send to paypal
	 * @param  string $cc
	 * @param  string $state
	 * @return string
	 */
	public function get_paypal_state( $cc, $state ) {
		if ( 'US' === $cc ) {
			return $state;
		}

		$states = WC()->countries->get_states( $cc );

		if ( isset( $states[ $state ] ) ) {
			return $states[ $state ];
		}

		return $state;
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order
	 *
	 * @return string
	 */
	public function get_transaction_url( $order ) {

		if ( 'yes' == $this->testmode ) {
			$this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		} else {
			$this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		}

		return parent::get_transaction_url( $order );
	}
}
