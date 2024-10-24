<?php
/**
 * Class WC_Shipping_Free_Shipping file.
 *
 * @package WooCommerce\Shipping
 */

use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free Shipping Method.
 *
 * A simple shipping method for free shipping.
 *
 * @class   WC_Shipping_Free_Shipping
 * @version 2.6.0
 * @package WooCommerce\Classes\Shipping
 */
class WC_Shipping_Free_Shipping extends WC_Shipping_Method {

	/**
	 * Min amount to be valid.
	 *
	 * @var integer
	 */
	public $min_amount = 0;

	/**
	 * Requires option.
	 *
	 * @var string
	 */
	public $requires = '';

	/**
	 * Ignore discounts.
	 *
	 * If set, free shipping would be available based on pre-discount order amount.
	 *
	 * @var string
	 */
	public $ignore_discounts;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'free_shipping';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Free shipping', 'woocommerce' );
		$this->method_description = __( 'Free shipping is a special method which can be triggered with coupons and minimum spends.', 'woocommerce' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
	}

	/**
	 * Initialize free shipping.
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title            = $this->get_option( 'title' );
		$this->min_amount       = $this->get_option( 'min_amount', 0 );
		$this->requires         = $this->get_option( 'requires' );
		$this->ignore_discounts = $this->get_option( 'ignore_discounts' );

		// Actions.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_footer', array( 'WC_Shipping_Free_Shipping', 'enqueue_admin_js' ), 10 ); // Priority needs to be higher than wc_print_js (25).
	}

	/**
	 * Sanitize the cost field.
	 *
	 * @since 8.3.0
	 * @param string $value Unsanitized value.
	 * @throws Exception Last error triggered.
	 * @return string
	 */
	public function sanitize_cost( $value ) {
		$value = is_null( $value ) ? '' : $value;
		$value = wp_kses_post( trim( wp_unslash( $value ) ) );
		$value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ) ), '', $value );

		$test_value = str_replace( wc_get_price_decimal_separator(), '.', $value );
		$test_value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ), wc_get_price_thousand_separator() ), '', $test_value );

		if ( $test_value && ! is_numeric( $test_value ) ) {
			throw new Exception( __( 'Please enter a valid number', 'woocommerce' ) );
		}

		return $value;
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'            => array(
				'title'       => __( 'Name', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your customers will see the name of this shipping method during checkout.', 'woocommerce' ),
				'default'     => $this->method_title,
				'placeholder' => __( 'e.g. Free shipping', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'requires'         => array(
				'title'   => __( 'Free shipping requires', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'options' => array(
					''           => __( 'No requirement', 'woocommerce' ),
					'coupon'     => __( 'A valid free shipping coupon', 'woocommerce' ),
					'min_amount' => __( 'A minimum order amount', 'woocommerce' ),
					'either'     => __( 'A minimum order amount OR coupon', 'woocommerce' ),
					'both'       => __( 'A minimum order amount AND coupon', 'woocommerce' ),
				),
			),
			'min_amount'       => array(
				'title'             => __( 'Minimum order amount', 'woocommerce' ),
				'type'              => 'text',
				'class'             => 'wc-shipping-modal-price',
				'placeholder'       => wc_format_localized_price( 0 ),
				'description'       => __( 'Customers will need to spend this amount to get free shipping.', 'woocommerce' ),
				'default'           => '0',
				'desc_tip'          => true,
				'sanitize_callback' => array( $this, 'sanitize_cost' ),
			),
			'ignore_discounts' => array(
				'title'       => __( 'Coupons discounts', 'woocommerce' ),
				'label'       => __( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Get setting form fields for instances of this shipping method within zones.
	 *
	 * @return array
	 */
	public function get_instance_form_fields() {
		return parent::get_instance_form_fields();
	}

	/**
	 * See if free shipping is available based on the package and cart.
	 *
	 * @param array $package Shipping package.
	 * @return bool
	 */
	public function is_available( $package ) {
		$has_coupon         = false;
		$has_met_min_amount = false;

		if ( in_array( $this->requires, array( 'coupon', 'either', 'both' ), true ) ) {
			$coupons = WC()->cart->get_coupons();

			if ( $coupons ) {
				foreach ( $coupons as $code => $coupon ) {
					if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
						$has_coupon = true;
						break;
					}
				}
			}
		}

		if ( in_array( $this->requires, array( 'min_amount', 'either', 'both' ), true ) ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( 'no' === $this->ignore_discounts ) {
				$total = $total - WC()->cart->get_discount_total();
				if ( WC()->cart->display_prices_including_tax() ) {
					$total = $total - WC()->cart->get_discount_tax();
				}
			}

			// Prepare min_amount for comparison
			$min_amount = str_replace( wc_get_price_decimal_separator(), '.', $this->min_amount );
			$min_amount = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ), wc_get_price_thousand_separator(), ), '', $min_amount );

			$min_amount = NumberUtil::round( $min_amount, wc_get_price_decimals() );
			$total = NumberUtil::round( $total, wc_get_price_decimals() );

			if ( $total >= $min_amount ) {
				$has_met_min_amount = true;
			}
		}

		switch ( $this->requires ) {
			case 'min_amount':
				$is_available = $has_met_min_amount;
				break;
			case 'coupon':
				$is_available = $has_coupon;
				break;
			case 'both':
				$is_available = $has_met_min_amount && $has_coupon;
				break;
			case 'either':
				$is_available = $has_met_min_amount || $has_coupon;
				break;
			default:
				$is_available = true;
				break;
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	/**
	 * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
	 *
	 * @uses WC_Shipping_Method::add_rate()
	 *
	 * @param array $package Shipping package.
	 */
	public function calculate_shipping( $package = array() ) {
		$this->add_rate(
			array(
				'label'   => $this->title,
				'cost'    => 0,
				'taxes'   => false,
				'package' => $package,
			)
		);
	}

	/**
	 * Enqueue JS to handle free shipping options.
	 *
	 * Static so that's enqueued only once.
	 */
	public static function enqueue_admin_js() {
		wc_enqueue_js(
			"jQuery( function( $ ) {
				function wcFreeShippingShowHideMinAmountField( el ) {
					var form = $( el ).closest( 'form' );
					var minAmountField = $( '#woocommerce_free_shipping_min_amount', form ).closest( 'tr' );
					var ignoreDiscountField = $( '#woocommerce_free_shipping_ignore_discounts', form ).closest( 'tr' );
					if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
						minAmountField.hide();
						ignoreDiscountField.hide();
					} else {
						minAmountField.show();
						ignoreDiscountField.show();
					}
				}

				$( document.body ).on( 'change', '#woocommerce_free_shipping_requires', function() {
					wcFreeShippingShowHideMinAmountField( this );
				});

				// Change while load.
				$( '#woocommerce_free_shipping_requires' ).trigger( 'change' );
				$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
					if ( 'wc-modal-shipping-method-settings' === target ) {
						wcFreeShippingShowHideMinAmountField( $( '#wc-backbone-modal-dialog #woocommerce_free_shipping_requires', evt.currentTarget ) );
					}
				} );
			});"
		);
	}
}
