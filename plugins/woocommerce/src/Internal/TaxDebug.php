<?php
/**
 * Tax debug mode functionality.
 *
 * Provides debug notices for tax calculations when debug mode is enabled.
 *
 * @package WooCommerce\Internal
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\ProductTaxStatus;
use WC_Tax;

defined( 'ABSPATH' ) || exit;

/**
 * Class to display tax debug notices on cart/checkout.
 *
 * This class uses the notice data key 'woocommerce_internal_tax_debug' to identify
 * its notices. This key is reserved for internal WooCommerce use and should not be
 * used by extensions.
 *
 * @since 10.5.0
 */
class TaxDebug {

	/**
	 * Tracks whether debug notices have been shown to avoid duplicates.
	 *
	 * @var bool
	 */
	private static bool $notices_shown = false;

	/**
	 * Data key used to identify tax debug notices.
	 *
	 * This key is reserved for internal WooCommerce use. Extensions should not
	 * use this key in notice data attributes.
	 *
	 * @var string
	 */
	private const NOTICE_DATA_KEY = 'woocommerce_internal_tax_debug';

	/**
	 * Initialize the class and set up hooks.
	 *
	 * @internal
	 */
	final public function init() {
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_show_debug_notices' ), 999 );
	}

	/**
	 * Check if tax debug mode is enabled.
	 *
	 * @return bool
	 */
	public static function is_debug_mode_enabled(): bool {
		return 'yes' === get_option( 'woocommerce_tax_debug_mode', 'no' );
	}

	/**
	 * Display debug notices if conditions are met.
	 *
	 * @param \WC_Cart $cart Cart object.
	 */
	public function maybe_show_debug_notices( $cart ): void {
		if ( ! $this->should_show_notices( $cart ) ) {
			return;
		}

		self::$notices_shown = true;

		// Clear any existing tax debug notices before adding fresh ones.
		// This prevents duplicate notices when address changes on block checkout.
		$this->clear_tax_debug_notices();

		$this->show_tax_location_notice( $cart );
		$this->show_applied_rates_notice( $cart );
		$this->show_tax_classes_notice( $cart );
	}

	/**
	 * Determine if debug notices should be shown.
	 *
	 * @param \WC_Cart|null $cart Cart object.
	 * @return bool
	 */
	private function should_show_notices( $cart ): bool {
		if ( ! self::is_debug_mode_enabled() ) {
			return false;
		}

		if ( self::$notices_shown ) {
			return false;
		}

		if ( ! wc_tax_enabled() ) {
			return false;
		}

		// This is true when actively processing an order, we don't want to add notices then.
		if ( Constants::is_defined( 'WOOCOMMERCE_CHECKOUT' ) && Constants::is_true( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return false;
		}

		if ( Constants::is_defined( 'WC_DOING_AJAX' ) && Constants::is_true( 'WC_DOING_AJAX' ) ) {
			return false;
		}

		if ( ! $cart || $cart->is_empty() ) {
			return false;
		}

		return true;
	}

	/**
	 * Show notice about which address is being used for tax calculation.
	 *
	 * @param \WC_Cart $cart Cart object.
	 */
	private function show_tax_location_notice( $cart ): void {
		$customer = $cart->get_customer();
		if ( ! $customer ) {
			return;
		}

		$location_info = $this->get_tax_location_info( $customer );
		$notice        = $this->format_tax_location_notice( $location_info );

		$this->add_tax_debug_notice( $notice );
	}

	/**
	 * Get information about the tax location being used.
	 *
	 * @param \WC_Customer $customer Customer object.
	 * @return array{source: string, country: string, state: string, postcode: string, city: string, is_local_pickup: bool}
	 */
	private function get_tax_location_info( $customer ): array {
		$tax_based_on    = get_option( 'woocommerce_tax_based_on', 'shipping' );
		$is_local_pickup = $this->is_local_pickup_selected();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in abstract-wc-order.php:1747
		$apply_base_taxes = apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true );

		if ( $is_local_pickup && $apply_base_taxes ) {
			$source = 'base_local_pickup';
		} elseif ( 'base' === $tax_based_on ) {
			$source = 'base';
		} elseif ( 'billing' === $tax_based_on ) {
			$source = 'billing';
		} else {
			$source = 'shipping';
		}

		if ( 'base' === $source || 'base_local_pickup' === $source ) {
			$country  = WC()->countries->get_base_country();
			$state    = WC()->countries->get_base_state();
			$postcode = WC()->countries->get_base_postcode();
			$city     = WC()->countries->get_base_city();
		} elseif ( 'billing' === $source ) {
			$country  = $customer->get_billing_country();
			$state    = $customer->get_billing_state();
			$postcode = $customer->get_billing_postcode();
			$city     = $customer->get_billing_city();
		} else {
			$country  = $customer->get_shipping_country();
			$state    = $customer->get_shipping_state();
			$postcode = $customer->get_shipping_postcode();
			$city     = $customer->get_shipping_city();
		}

		return array(
			'source'          => $source,
			'country'         => $country,
			'state'           => $state,
			'postcode'        => $postcode,
			'city'            => $city,
			'is_local_pickup' => $is_local_pickup,
		);
	}

	/**
	 * Check if local pickup shipping method is selected.
	 *
	 * @return bool
	 */
	private function is_local_pickup_selected(): bool {
		$chosen_methods = wc_get_chosen_shipping_method_ids();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in abstract-wc-order.php:1761
		$local_pickup_methods = apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) );
		$matching_methods     = array_intersect( $chosen_methods, $local_pickup_methods );

		return count( $matching_methods ) > 0;
	}

	/**
	 * Format the tax location debug notice.
	 *
	 * @param array $location_info Location information.
	 * @return string
	 */
	private function format_tax_location_notice( array $location_info ): string {
		$address_parts = array_filter(
			array(
				$location_info['country'],
				$location_info['state'],
				$location_info['postcode'],
				$location_info['city'],
			)
		);
		$address_str   = implode( ', ', $address_parts );

		switch ( $location_info['source'] ) {
			case 'base_local_pickup':
				return sprintf(
					/* translators: %s: address (country, state, postcode, city) */
					__( 'Tax calculated based on store base address (local pickup selected) (%s)', 'woocommerce' ),
					$address_str
				);

			case 'base':
				return sprintf(
					/* translators: %s: address (country, state, postcode, city) */
					__( 'Tax calculated based on store base address (%s)', 'woocommerce' ),
					$address_str
				);

			case 'billing':
				return sprintf(
					/* translators: %s: address (country, state, postcode, city) */
					__( 'Tax calculated based on billing address (%s)', 'woocommerce' ),
					$address_str
				);

			case 'shipping':
			default:
				return sprintf(
					/* translators: %s: address (country, state, postcode, city) */
					__( 'Tax calculated based on shipping address (%s)', 'woocommerce' ),
					$address_str
				);
		}
	}

	/**
	 * Show notice about applied tax rates.
	 *
	 * @param \WC_Cart $cart Cart object.
	 */
	private function show_applied_rates_notice( $cart ): void {
		$taxes = $cart->get_cart_contents_taxes();

		if ( empty( $taxes ) ) {
			$this->show_no_rates_notice( $cart );
			return;
		}

		$rate_details = $this->get_rate_details( array_keys( $taxes ) );

		if ( empty( $rate_details ) ) {
			return;
		}

		$has_compound = $this->has_compound_rates( $rate_details );
		$notice       = $this->format_rates_notice( $rate_details, $has_compound );

		$this->add_tax_debug_notice( $notice );
	}

	/**
	 * Get details for tax rate IDs.
	 *
	 * @param array $rate_ids Array of rate IDs.
	 * @return array Array of rate details.
	 */
	private function get_rate_details( array $rate_ids ): array {
		$details = array();

		foreach ( $rate_ids as $rate_id ) {
			$rate = WC_Tax::_get_tax_rate( (int) $rate_id );
			if ( $rate ) {
				$details[] = array(
					'id'       => $rate_id,
					'code'     => WC_Tax::get_rate_code( $rate_id ),
					'percent'  => WC_Tax::get_rate_percent( $rate_id ),
					'compound' => WC_Tax::is_compound( $rate_id ),
				);
			}
		}

		return $details;
	}

	/**
	 * Check if any rates are compound.
	 *
	 * @param array $rate_details Rate details.
	 * @return bool
	 */
	private function has_compound_rates( array $rate_details ): bool {
		foreach ( $rate_details as $rate ) {
			if ( $rate['compound'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Format the applied rates notice.
	 *
	 * @param array $rate_details Rate details.
	 * @param bool  $has_compound Whether any rates are compound.
	 * @return string
	 */
	private function format_rates_notice( array $rate_details, bool $has_compound ): string {
		if ( 1 === count( $rate_details ) ) {
			$rate = $rate_details[0];
			return sprintf(
				/* translators: 1: tax rate code, 2: tax rate percentage, 3: rate ID */
				__( 'Tax rate applied: %1$s - %2$s (Rate ID: %3$s)', 'woocommerce' ),
				$rate['code'],
				$rate['percent'],
				$rate['id']
			);
		}

		$rate_strings = array();
		foreach ( $rate_details as $rate ) {
			$rate_strings[] = sprintf( '%s - %s', $rate['code'], $rate['percent'] );
		}

		$compound_suffix = $has_compound ? ' ' . __( '(compound)', 'woocommerce' ) : '';

		return sprintf(
			/* translators: %s: comma-separated list of tax rates */
			__( 'Tax rates applied: %s', 'woocommerce' ),
			implode( ', ', $rate_strings )
		) . $compound_suffix;
	}

	/**
	 * Show notice when no tax rates are found.
	 *
	 * @param \WC_Cart $cart Cart object.
	 */
	private function show_no_rates_notice( $cart ): void {
		$customer = $cart->get_customer();
		if ( ! $customer ) {
			return;
		}

		$location  = $customer->get_taxable_address();
		$tax_class = $this->get_primary_tax_class_from_cart( $cart );

		if ( count( $location ) < 4 ) {
			return;
		}

		list( $country, $state, $postcode, $city ) = $location;

		$address_parts = array_filter( array( $country, $state, $postcode ) );

		$notice = sprintf(
			/* translators: 1: address (country, state, postcode), 2: tax class name */
			__( 'No tax rates found for: %1$s (tax class: %2$s)', 'woocommerce' ),
			implode( ', ', $address_parts ),
			$tax_class ? $tax_class : __( 'Standard', 'woocommerce' )
		);

		$this->add_tax_debug_notice( $notice );
	}

	/**
	 * Show notice about tax classes in cart.
	 *
	 * @param \WC_Cart $cart Cart object.
	 */
	private function show_tax_classes_notice( $cart ): void {
		$tax_classes = $this->get_cart_tax_classes( $cart );

		// Only show if there are non-standard tax classes.
		$has_non_standard = false;
		foreach ( $tax_classes as $class ) {
			if ( '' !== $class ) {
				$has_non_standard = true;
				break;
			}
		}

		if ( ! $has_non_standard ) {
			return;
		}

		$class_names = array();
		foreach ( $tax_classes as $class ) {
			$class_names[] = '' === $class ? __( 'Standard', 'woocommerce' ) : ucfirst( str_replace( '-', ' ', $class ) );
		}

		$notice = sprintf(
			/* translators: %s: comma-separated list of tax classes */
			__( 'Product tax classes in cart: %s', 'woocommerce' ),
			implode( ', ', array_unique( $class_names ) )
		);

		$this->add_tax_debug_notice( $notice );
	}

	/**
	 * Get all tax classes from cart items.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return array Array of tax class slugs.
	 */
	private function get_cart_tax_classes( $cart ): array {
		$tax_classes = array();

		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'] ?? null;
			if ( $product && ProductTaxStatus::TAXABLE === $product->get_tax_status() ) {
				$tax_classes[] = $product->get_tax_class();
			}
		}

		return array_unique( $tax_classes );
	}

	/**
	 * Get the primary tax class from cart items.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return string Tax class slug.
	 */
	private function get_primary_tax_class_from_cart( $cart ): string {
		$tax_classes = $this->get_cart_tax_classes( $cart );

		if ( empty( $tax_classes ) ) {
			return '';
		}

		// Return standard if present, otherwise first class.
		if ( in_array( '', $tax_classes, true ) ) {
			return '';
		}

		return reset( $tax_classes );
	}

	/**
	 * Add a tax debug notice with identifying data attribute.
	 *
	 * @param string $message Notice message.
	 */
	private function add_tax_debug_notice( string $message ): void {
		wc_add_notice( $message, 'notice', array( self::NOTICE_DATA_KEY => true ) );
	}

	/**
	 * Clear all existing tax debug notices.
	 */
	private function clear_tax_debug_notices(): void {
		$all_notices = wc_get_notices();

		if ( empty( $all_notices['notice'] ) ) {
			return;
		}

		// Filter out tax debug notices.
		$all_notices['notice'] = array_filter(
			$all_notices['notice'],
			function ( $notice ) {
				return empty( $notice['data'][ self::NOTICE_DATA_KEY ] );
			}
		);

		// Re-index array to avoid gaps.
		$all_notices['notice'] = array_values( $all_notices['notice'] );

		wc_set_notices( $all_notices );
	}

	/**
	 * Reset the notices shown flag (useful for testing).
	 *
	 * @internal
	 */
	public static function reset_notices_flag(): void {
		self::$notices_shown = false;
	}
}
