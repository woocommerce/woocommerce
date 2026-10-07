<?php
/**
 * Class WC_Gateway_COD file.
 *
 * @package WooCommerce\Gateways
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Gateways\ShippingMethodRestrictionsTrait;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils as SettingsUtils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cash on Delivery Gateway.
 *
 * Provides a Cash on Delivery Payment Gateway.
 *
 * @class       WC_Gateway_COD
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce\Classes\Payment
 */
class WC_Gateway_COD extends WC_Payment_Gateway {

	use ShippingMethodRestrictionsTrait;

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'cod';

	/**
	 * Gateway instructions that will be added to the thank you page and emails.
	 *
	 * @var string
	 */
	public $instructions;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );
		$this->init_shipping_method_restrictions();

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = self::ID;
		$this->icon               = apply_filters( 'woocommerce_cod_icon', '' );
		$this->method_title       = __( 'Cash on delivery', 'woocommerce' );
		$this->method_description = __( 'Let your shoppers pay upon delivery — by cash or other methods of payment.', 'woocommerce' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array_merge(
			array(
				'enabled'      => array(
					'title'       => __( 'Enable/Disable', 'woocommerce' ),
					'label'       => __( 'Enable cash on delivery', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
				'title'        => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'safe_text',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( 'Cash on delivery', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description'  => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ),
					'default'     => __( 'Pay with cash upon delivery.', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce' ),
					'default'     => __( 'Pay with cash upon delivery.', 'woocommerce' ),
					'desc_tip'    => true,
				),
			),
			$this->get_shipping_method_restrictions_form_fields()
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			/**
			 * Filter the order status for COD orders.
			 *
			 * @since 2.6.0
			 *
			 * @param string $order_status Default status for COD orders.
			 */
			$process_payment_status = apply_filters( 'woocommerce_cod_process_payment_order_status', $order->has_downloadable_item() ? OrderStatus::ON_HOLD : OrderStatus::PROCESSING, $order );
			// Mark as processing or on-hold (payment won't be taken until delivery).
			$order->update_status( $process_payment_status, __( 'Payment to be made upon delivery.', 'woocommerce' ) );
		} else {
			$order->payment_complete();
		}

		// Remove cart if it still matches the order being processed.
		if ( $order instanceof WC_Order && WC()->cart && $order->has_cart_hash( WC()->cart->get_cart_hash() ) ) {
			WC()->cart->empty_cart();
		}

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Change payment complete order status to completed for COD orders.
	 *
	 * @since  3.1.0
	 * @param  string         $status Current order status.
	 * @param  int            $order_id Order ID.
	 * @param  WC_Order|false $order Order object.
	 * @return string
	 */
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		if ( $order && self::ID === $order->get_payment_method() ) {
			$status = OrderStatus::COMPLETED;
		}
		return $status;
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}

	/**
	 * Get the settings URL for the gateway.
	 *
	 * @return string The settings page URL for the gateway.
	 */
	public function get_settings_url() {
		// Search for a WC_Settings_Payment_Gateways instance in the settings pages.
		$payments_settings_page = null;
		foreach ( WC_Admin_Settings::get_settings_pages() as $settings_page ) {
			if ( $settings_page instanceof WC_Settings_Payment_Gateways ) {
				$payments_settings_page = $settings_page;
				break;
			}
		}
		// If no instance found, return the default settings URL (the Reactified page).
		if ( empty( $payments_settings_page ) ) {
			return SettingsUtils::wc_payments_settings_url( '/' . WC_Settings_Payment_Gateways::OFFLINE_SECTION_NAME . '/' . $this->id );
		}

		$should_use_react_settings_page = $payments_settings_page->should_render_react_section( WC_Settings_Payment_Gateways::COD_SECTION_NAME );

		// We must not include both the path and the section query parameter, as this can cause weird behavior.
		return SettingsUtils::wc_payments_settings_url(
			$should_use_react_settings_page ? '/' . WC_Settings_Payment_Gateways::OFFLINE_SECTION_NAME . '/' . $this->id : null,
			$should_use_react_settings_page ? array() : array( 'section' => $this->id )
		);
	}
}
