<?php
/**
 * WooCommerce Shipping
 *
 * Handles shipping and loads shipping methods via hooks.
 *
 * @version 2.6.0
 * @package WooCommerce\Classes\Shipping
 */

use Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipping class.
 */
class WC_Shipping {

	/**
	 * True if shipping is enabled.
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Stores methods loaded into woocommerce.
	 *
	 * @var array|null
	 */
	public $shipping_methods = null;

	/**
	 * Stores the shipping classes.
	 *
	 * @var array
	 */
	public $shipping_classes = array();

	/**
	 * Stores packages to ship and to get quotes for.
	 *
	 * @var array
	 */
	public $packages = array();

	/**
	 * The single instance of the class
	 *
	 * @var WC_Shipping
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Shipping Instance.
	 *
	 * Ensures only one instance of WC_Shipping is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @return WC_Shipping Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce' ), '2.1' );
	}

	/**
	 * Magic getter.
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	public function __get( $name ) {
		// Grab from cart for backwards compatibility with versions prior to 3.2.
		if ( 'shipping_total' === $name ) {
			return WC()->cart->get_shipping_total();
		}
		if ( 'shipping_taxes' === $name ) {
			return WC()->cart->get_shipping_taxes();
		}
	}

	/**
	 * Initialize shipping.
	 */
	public function __construct() {
		$this->enabled = wc_shipping_enabled();

		if ( $this->enabled ) {
			$this->init();
		}
	}

	/**
	 * Initialize shipping.
	 */
	public function init() {
		do_action( 'woocommerce_shipping_init' );
	}

	/**
	 * Shipping methods register themselves by returning their main class name through the woocommerce_shipping_methods filter.
	 *
	 * @return array
	 */
	public function get_shipping_method_class_names() {
		// Unique Method ID => Method Class name.
		$shipping_methods = array(
			'flat_rate'     => 'WC_Shipping_Flat_Rate',
			'free_shipping' => 'WC_Shipping_Free_Shipping',
			'local_pickup'  => 'WC_Shipping_Local_Pickup',
		);

		// For backwards compatibility with 2.5.x we load any ENABLED legacy shipping methods here.
		$maybe_load_legacy_methods = array( 'flat_rate', 'free_shipping', 'international_delivery', 'local_delivery', 'local_pickup' );

		foreach ( $maybe_load_legacy_methods as $method ) {
			$options = get_option( 'woocommerce_' . $method . '_settings' );
			if ( $options && isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
				$shipping_methods[ 'legacy_' . $method ] = 'WC_Shipping_Legacy_' . $method;
			}
		}

		return apply_filters( 'woocommerce_shipping_methods', $shipping_methods );
	}

	/**
	 * Loads all shipping methods which are hooked in.
	 * If a $package is passed, some methods may add themselves conditionally and zones will be used.
	 *
	 * @param array $package Package information.
	 * @return WC_Shipping_Method[]
	 */
	public function load_shipping_methods( $package = array() ) {
		if ( ! empty( $package ) ) {
			$debug_mode             = 'yes' === get_option( 'woocommerce_shipping_debug_mode', 'no' );
			$shipping_zone          = WC_Shipping_Zones::get_zone_matching_package( $package );
			$this->shipping_methods = $shipping_zone->get_shipping_methods( true );

			// translators: %s: shipping zone name.
			$matched_zone_notice = sprintf( __( 'Customer matched zone "%s"', 'woocommerce' ), $shipping_zone->get_zone_name() );

			// Debug output.
			if ( $debug_mode && ! Constants::is_defined( 'WOOCOMMERCE_CHECKOUT' ) && ! Constants::is_defined( 'WC_DOING_AJAX' ) && ! wc_has_notice( $matched_zone_notice ) ) {
				wc_add_notice( $matched_zone_notice );
			}
		} else {
			$this->shipping_methods = array();
		}

		// For the settings in the backend, and for non-shipping zone methods, we still need to load any registered classes here.
		foreach ( $this->get_shipping_method_class_names() as $method_id => $method_class ) {
			$this->register_shipping_method( $method_class );
		}

		// Methods can register themselves manually through this hook if necessary.
		do_action( 'woocommerce_load_shipping_methods', $package );

		// Return loaded methods.
		return $this->get_shipping_methods();
	}

	/**
	 * Register a shipping method.
	 *
	 * @param object|string $method Either the name of the method's class, or an instance of the method's class.
	 *
	 * @return bool|void
	 */
	public function register_shipping_method( $method ) {
		if ( ! is_object( $method ) ) {
			if ( ! class_exists( $method ) ) {
				return false;
			}
			$method = new $method();
		}
		if ( is_null( $this->shipping_methods ) ) {
			$this->shipping_methods = array();
		}
		$this->shipping_methods[ $method->id ] = $method;
	}

	/**
	 * Unregister shipping methods.
	 */
	public function unregister_shipping_methods() {
		$this->shipping_methods = null;
	}

	/**
	 * Returns all registered shipping methods for usage.
	 *
	 * @return WC_Shipping_Method[]
	 */
	public function get_shipping_methods() {
		if ( is_null( $this->shipping_methods ) ) {
			$this->load_shipping_methods();
		}
		return $this->shipping_methods;
	}

	/**
	 * Get an array of shipping classes.
	 *
	 * @return array
	 */
	public function get_shipping_classes() {
		if ( empty( $this->shipping_classes ) ) {
			$classes                = get_terms(
				'product_shipping_class',
				array(
					'hide_empty' => '0',
					'orderby'    => 'name',
				)
			);
			$this->shipping_classes = ! is_wp_error( $classes ) ? $classes : array();
		}
		return apply_filters( 'woocommerce_get_shipping_classes', $this->shipping_classes );
	}

	/**
	 * Calculate shipping for (multiple) packages of cart items.
	 *
	 * @param array $packages multi-dimensional array of cart items to calc shipping for.
	 * @return array Array of calculated packages.
	 */
	public function calculate_shipping( $packages = array() ) {
		$this->packages = array();

		if ( ! $this->enabled || empty( $packages ) ) {
			return array();
		}

		// Calculate costs for passed packages.
		foreach ( $packages as $package_key => $package ) {
			$this->packages[ $package_key ] = $this->calculate_shipping_for_package( $package, $package_key );

			/**
			 * Allow merchants to disable package rate validation.
			 *
			 * @since 7.8.0
			 *
			 * @param array      $package      The array of the current package before shipping cost calculation.
			 * @param string|int $package_key  The offset of the $packages array referring to the current package. Can be used to access current package in the 3rd parameter of this filter.
			 * @param array      $packages     The array of packages after shipping costs are calculated.
			 */
			if ( apply_filters( 'woocommerce_skip_shipping_rates_validation', false, $package, $package_key, $this->packages ) ) {
				continue;
			}

			$this->validate_shipping_rates( $package, $package_key );
		}

		/**
		 * Allow packages to be reorganized after calculating the shipping.
		 *
		 * This filter can be used to apply some extra manipulation after the shipping costs are calculated for the packages
		 * but before WooCommerce does anything with them. A good example of usage is to merge the shipping methods for multiple
		 * packages for marketplaces.
		 *
		 * @since 2.6.0
		 *
		 * @param array $packages The array of packages after shipping costs are calculated.
		 */
		$this->packages = array_filter( (array) apply_filters( 'woocommerce_shipping_packages', $this->packages ) );

		return $this->packages;
	}

	/**
	 * Validates the calculated shipping rates for each of the individual products.
	 *
	 * Solves https://github.com/woocommerce/woocommerce/issues/27030.
	 *
	 * Cart would pick any rate of a shipping class of a product in cart. Even though if by using that class one of the others
	 * products could not be shipped using it.
	 *
	 * Here we validate that each one of the shipping rates the Frontend is provided with, could be used to ship each one of the
	 * products that require shipping individually.
	 *
	 * @param array $package     Package of cart items.
	 * @param int   $package_key Index of the package being calculated. Used to cache multiple package rates.
	 * @return void
	 */
	protected function validate_shipping_rates( $package, $package_key ) {
		// If package has no contents or has only one product as its content, above calculations are always correct.
		if ( empty( $this->packages[ $package_key ]['contents'] ) || count( $this->packages[ $package_key ]['contents'] ) < 2 ) {
			return;
		}

		// If no rates already, no further action is needed.
		if ( empty( $this->packages[ $package_key ]['rates'] ) ) {
			return;
		}

		$products_in_need_of_shipping = 0;

		// Find out how many products in the cart require shipping.
		foreach ( $this->packages[ $package_key ]['contents'] as $cart_content ) {
			$cart_product = $cart_content['data'];
			if ( ! $cart_product->needs_shipping() ) {
				continue;
			}

			$products_in_need_of_shipping++;
		}

		// Only one or no products require shipping. Calculations done already are correct.
		if ( 2 > $products_in_need_of_shipping ) {
			return;
		}

		// For each of the shipping rates, recalculate the packages if the contents was only one shippable package.
		// If the shipping rate looping through is not in the shipping rate when the calculation is done for the individual product,
		// then that rate is not suitable for shipping one of those products.
		foreach ( $this->packages[ $package_key ]['rates'] as $shipping_rate_key => $shipping_rate ) {
			$temp_package = $package;

			foreach ( $package['contents'] as $cart_content_key => $cart_content ) {
				// Remove all the other contents of the package in each iteration.
				unset( $temp_package['contents'] );

				// Skip products that don't require shipping. It would produce unreliable results.
				if ( ! $cart_content['data']->needs_shipping() ) {
					continue;
				}

				$temp_package['contents'][ $cart_content_key ] = $cart_content;

				// We don't provide package key, but thats fine. Cache will be overwritten suitably since we change the contents offset.
				// So we are going to take cache data when we have calculated the same before and fresh when its actually a new calculation.
				$temp_package = $this->calculate_shipping_for_package( $temp_package );

				if ( empty( $temp_package['rates'] ) ) {
					/**
					 * Allow merchants to perform extra actions when a shipping rate is found invalid.
					 *
					 * Ideal for displaying notices with explanation as to why the package cannot be shipped as is.
					 * For example using the $cart_content['data'] which is the (or one of the) product not suitable for being shipped under the current circumstances,
					 * a notice can be displayed alerting the customer that removing this product from his cart will allow him to complete his checkout.
					 *
					 * @since 7.8.0
					 *
					 * @param WC_Shipping_Rate $shipping_rate The shipping rate about to be removed.
					 * @param WC_Product       $product       The product, making the shipping rate not suitable to be used.
					 * @param array            $package       The array of the current package before shipping cost calculation.
					 * @param array            $packages      The array of packages after shipping costs are calculated.
					 */
					do_action( 'woocommerce_shipping_rate_invalid', $shipping_rate, $cart_content['data'], $package, $this->packages[ $package_key ] );
					unset( $this->packages[ $package_key ]['rates'][ $shipping_rate_key ] );
					continue;
				}

				$shipping_rate_id = $shipping_rate->get_id();

				$rate_found_individually = false;

				foreach ( $temp_package['rates'] as $rate ) {
					if ( $rate->get_id() !== $shipping_rate_id ) {
						continue;
					}

					$rate_found_individually = true;
					break;
				}

				if ( $rate_found_individually ) {
					continue;
				}

				/**
				 * Documented just a few lines above.
				 *
				 * @inheritdoc
				 */
				do_action( 'woocommerce_shipping_rate_invalid', $shipping_rate, $cart_content['data'], $package, $this->packages[ $package_key ] ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
				unset( $this->packages[ $package_key ]['rates'][ $shipping_rate_key ] );
			}
		}
	}

	/**
	 * See if package is shippable.
	 *
	 * Packages are shippable until proven otherwise e.g. after getting a shipping country.
	 *
	 * @param  array $package Package of cart items.
	 * @return bool
	 */
	public function is_package_shippable( $package ) {
		// Packages are shippable until proven otherwise.
		if ( empty( $package['destination']['country'] ) ) {
			return true;
		}

		$allowed = array_keys( WC()->countries->get_shipping_countries() );
		return in_array( $package['destination']['country'], $allowed, true );
	}

	/**
	 * Calculate shipping rates for a package,
	 *
	 * Calculates each shipping methods cost. Rates are stored in the session based on the package hash to avoid re-calculation every page load.
	 *
	 * @param array $package Package of cart items.
	 * @param int   $package_key Index of the package being calculated. Used to cache multiple package rates.
	 *
	 * @return array|bool
	 */
	public function calculate_shipping_for_package( $package = array(), $package_key = 0 ) {
		// If shipping is disabled or the package is invalid, return false.
		if ( ! $this->enabled || empty( $package ) ) {
			return false;
		}

		$package['rates'] = array();

		// If the package is not shippable, e.g. trying to ship to an invalid country, do not calculate rates.
		if ( ! $this->is_package_shippable( $package ) ) {
			return $package;
		}

		// Check if we need to recalculate shipping for this package.
		$package_to_hash = $package;

		// Remove data objects so hashes are consistent.
		foreach ( $package_to_hash['contents'] as $item_id => $item ) {
			unset( $package_to_hash['contents'][ $item_id ]['data'] );
		}

		// Get rates stored in the WC session data for this package.
		$wc_session_key = 'shipping_for_package_' . $package_key;
		$stored_rates   = WC()->session->get( $wc_session_key );

		// Calculate the hash for this package so we can tell if it's changed since last calculation.
		$package_hash = 'wc_ship_' . md5( wp_json_encode( $package_to_hash ) . WC_Cache_Helper::get_transient_version( 'shipping' ) );

		if ( ! is_array( $stored_rates ) || $package_hash !== $stored_rates['package_hash'] || 'yes' === get_option( 'woocommerce_shipping_debug_mode', 'no' ) ) {
			foreach ( $this->load_shipping_methods( $package ) as $shipping_method ) {
				if ( ! $shipping_method->supports( 'shipping-zones' ) || $shipping_method->get_instance_id() ) {
					/**
					 * Fires before getting shipping rates for a package.
					 *
					 * @since 4.3.0
					 * @param array $package Package of cart items.
					 * @param WC_Shipping_Method $shipping_method Shipping method instance.
					 */
					do_action( 'woocommerce_before_get_rates_for_package', $package, $shipping_method );

					// Use + instead of array_merge to maintain numeric keys.
					$package['rates'] = $package['rates'] + $shipping_method->get_rates_for_package( $package );

					/**
					 * Fires after getting shipping rates for a package.
					 *
					 * @since 4.3.0
					 * @param array $package Package of cart items.
					 * @param WC_Shipping_Method $shipping_method Shipping method instance.
					 */
					do_action( 'woocommerce_after_get_rates_for_package', $package, $shipping_method );
				}
			}

			/**
			 * Filter the calculated shipping rates.
			 *
			 * @see https://gist.github.com/woogists/271654709e1d27648546e83253c1a813 for cache invalidation methods.
			 * @param array $package['rates'] Package rates.
			 * @param array $package Package of cart items.
			 */
			$package['rates'] = apply_filters( 'woocommerce_package_rates', $package['rates'], $package );

			// Store in session to avoid recalculation.
			WC()->session->set(
				$wc_session_key,
				array(
					'package_hash' => $package_hash,
					'rates'        => $package['rates'],
				)
			);
		} else {
			$package['rates'] = $stored_rates['rates'];
		}

		return $package;
	}

	/**
	 * Get packages.
	 *
	 * @return array
	 */
	public function get_packages() {
		return $this->packages;
	}

	/**
	 * Reset shipping.
	 *
	 * Reset the totals for shipping as a whole.
	 */
	public function reset_shipping() {
		unset( WC()->session->chosen_shipping_methods );
		$this->packages = array();
	}

	/**
	 * Deprecated
	 *
	 * @deprecated 2.6.0 Was previously used to determine sort order of methods, but this is now controlled by zones and thus unused.
	 */
	public function sort_shipping_methods() {
		wc_deprecated_function( 'sort_shipping_methods', '2.6' );
		return $this->shipping_methods;
	}
}
