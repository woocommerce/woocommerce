<?php
/**
 * ShippingUtil class file.
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Utilities;

/**
 * The ShippingUtil class provides utilities for working with shipping and shipping packages.
 */
class ShippingUtil {

	/**
	 * The package keys of the cart from the last build, with the cart state they belong to.
	 *
	 * @var array{state: string, keys: array<int|string, true>|null}|null
	 */
	private static $cart_package_keys = null;

	/**
	 * Get the selected shipping rates from the packages.
	 *
	 * @param array $packages The packages to get the selected shipping rates from.
	 * @return \WC_Shipping_Rate[] The selected shipping rates.
	 */
	public static function get_selected_shipping_rates_from_packages( $packages ) {
		return array_filter(
			array_map(
				function ( $package_id, $package ) {
					$selected_rate_id = wc_get_chosen_shipping_method_for_package( $package_id, $package );
					$selected_rate    = false !== $selected_rate_id && isset( $package['rates'][ $selected_rate_id ] ) ? $package['rates'][ $selected_rate_id ] : null;

					return $selected_rate instanceof \WC_Shipping_Rate ? $selected_rate : null;
				},
				array_keys( $packages ),
				array_values( $packages )
			)
		);
	}

	/**
	 * Check that a rate was listed for a package key the last time shipping was calculated.
	 *
	 * A rate id in the `chosen_shipping_methods` session value is used only when the `previous_shipping_methods`
	 * session value lists it for the same key.
	 *
	 * @since 11.2.2
	 *
	 * @param int|string $package_key Package key, as used in `chosen_shipping_methods`.
	 * @param mixed      $rate_id     Rate id to check. Only strings can match.
	 * @return bool
	 */
	public static function is_shipping_rate_listed( $package_key, $rate_id ): bool {
		if ( ! is_string( $rate_id ) || ( ! is_int( $package_key ) && ! is_string( $package_key ) ) || ! is_callable( array( WC()->session, 'get' ) ) ) {
			return false;
		}

		$listed_rates = WC()->session->get( 'previous_shipping_methods' );
		$rate_ids     = is_array( $listed_rates ) ? ( $listed_rates[ $package_key ] ?? null ) : null;

		// Compare as strings: array_keys() turns a numeric rate id into an int.
		return is_array( $rate_ids ) && in_array( $rate_id, array_map( 'strval', array_filter( $rate_ids, 'is_scalar' ) ), true );
	}

	/**
	 * Keep only the chosen methods whose rates were listed for the current cart packages.
	 *
	 * Each rate must be listed for its key. Key must be a shipping package of
	 * the cart at the time of request. The session can keep keys from earlier requests and keys
	 * that other code adds for carts other than this one.
	 *
	 * @since 11.2.2
	 *
	 * @param mixed $chosen_methods Chosen methods keyed by package key, usually the `chosen_shipping_methods` session value.
	 * @return array The listed entries with their keys kept. Empty when the input is not an array.
	 */
	public static function filter_chosen_shipping_methods( $chosen_methods ): array {
		if ( ! is_array( $chosen_methods ) || ! is_callable( array( WC()->session, 'get' ) ) ) {
			return array();
		}

		$listed_rates   = WC()->session->get( 'previous_shipping_methods' );
		$listed_rates   = is_array( $listed_rates ) ? $listed_rates : array();
		$chosen_methods = array_filter(
			$chosen_methods,
			function ( $rate_id, $package_key ) use ( $listed_rates ) {
				$rate_ids = $listed_rates[ $package_key ] ?? null;

				// Compare as strings: array_keys() turns a numeric rate id into an int.
				return is_string( $rate_id ) && is_array( $rate_ids ) && in_array( $rate_id, array_map( 'strval', array_filter( $rate_ids, 'is_scalar' ) ), true );
			},
			ARRAY_FILTER_USE_BOTH
		);

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in WC_Abstract_Order::get_tax_location().
		$pickup_method_ids = apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) );
		$pickup_method_ids = is_array( $pickup_method_ids ) ? $pickup_method_ids : array();
		$package_keys      = null;

		foreach ( $chosen_methods as $package_key => $rate_id ) {
			if ( ! in_array( explode( ':', $rate_id )[0], $pickup_method_ids, true ) ) {
				continue;
			}

			// This runs on every tax rate lookup, so build the keys again only when the cart state changes.
			// The totals counters change for each Subscriptions recurring cart, which uses other keys.
			if ( null === $package_keys ) {
				$cart = WC()->cart;
				if ( ! $cart instanceof \WC_Cart ) {
					$package_keys = array();
				} else {
					$customer = WC()->customer;
					$state    = md5(
						(string) wp_json_encode(
							array(
								spl_object_id( $cart ),
								wp_list_pluck( $cart->get_cart_contents(), 'quantity' ),
								$customer instanceof \WC_Customer ? array(
									$customer->get_shipping_country(),
									$customer->get_shipping_state(),
									$customer->get_shipping_postcode(),
									$customer->get_shipping_city(),
									$customer->get_shipping_address_1(),
									$customer->get_shipping_address_2(),
								) : array(),
								did_action( 'woocommerce_before_calculate_totals' ),
								did_action( 'woocommerce_after_calculate_totals' ),
							)
						)
					);

					if ( null === self::$cart_package_keys || self::$cart_package_keys['state'] !== $state ) {
						// Save the state first: a nested call from the package filter sees null keys, keeps the pickup and does not build the packages again.
						self::$cart_package_keys = array(
							'state' => $state,
							'keys'  => null,
						);
						try {
							self::$cart_package_keys['keys'] = array_fill_keys( array_keys( $cart->get_shipping_packages() ), true );
						} finally {
							// A build that did not complete leaves the keys for the current state null.
							if ( null === ( self::$cart_package_keys['keys'] ?? null ) ) {
								self::$cart_package_keys = null;
							}
						}
					}
					$package_keys = self::$cart_package_keys['keys'] ?? null;
					if ( null === $package_keys ) {
						break;
					}
				}
			}

			if ( ! isset( $package_keys[ $package_key ] ) ) {
				unset( $chosen_methods[ $package_key ] );
			}
		}

		return $chosen_methods;
	}
}
