<?php
/**
 * Cache-Optimized Cart Session Handler
 * 
 * This class extends the default cart session handler to optimize caching
 * by conditionally setting cookies only when necessary for functionality.
 * 
 * @package WooCommerce/Includes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cache_Optimized_Cart_Session class.
 */
class WC_Cache_Optimized_Cart_Session extends WC_Cart_Session {

	/**
	 * Pages where cart cookies should always be set (non-cacheable pages).
	 *
	 * @var array
	 */
	private $non_cacheable_pages = array(
		'cart',
		'checkout',
		'my-account',
		'order-received',
		'order-pay',
		'add-payment-method',
		'lost-password',
		'resetpass',
		'customer-logout',
		'edit-account',
		'edit-address',
		'orders',
		'downloads',
		'payment-methods',
		'view-order',
	);

	/**
	 * AJAX endpoints that require cart cookies.
	 *
	 * @var array
	 */
	private $ajax_endpoints = array(
		'wc_add_to_cart',
		'wc_remove_from_cart',
		'wc_update_cart',
		'wc_get_cart_fragments',
		'wc_apply_coupon',
		'wc_remove_coupon',
		'wc_update_shipping_method',
		'wc_checkout',
	);

	/**
	 * Whether we're currently in a context that requires cookies.
	 *
	 * @var bool
	 */
	private $requires_cookies = false;

	/**
	 * Constructor.
	 *
	 * @param WC_Cart $cart Cart instance.
	 */
	public function __construct( $cart ) {
		parent::__construct( $cart );
		$this->init_cache_optimization();
	}

	/**
	 * Initialize cache optimization hooks.
	 */
	private function init_cache_optimization() {
		// Determine if we need cookies based on current context
		add_action( 'wp', array( $this, 'determine_cookie_requirement' ), 5 );
		add_action( 'wp_ajax_nopriv_wc_add_to_cart', array( $this, 'set_ajax_cookie_requirement' ), 5 );
		add_action( 'wp_ajax_wc_add_to_cart', array( $this, 'set_ajax_cookie_requirement' ), 5 );
		
		// Override cookie setting behavior
		add_filter( 'woocommerce_set_cookie_enabled', array( $this, 'maybe_disable_cookies' ), 10, 5 );
		add_filter( 'woocommerce_set_cart_cookies', array( $this, 'maybe_skip_cart_cookies' ), 10, 1 );
	}

	/**
	 * Determine if cookies are required for the current request.
	 */
	public function determine_cookie_requirement() {
		// Always require cookies for non-cacheable pages
		if ( $this->is_non_cacheable_page() ) {
			$this->requires_cookies = true;
			return;
		}

		// Always require cookies for AJAX requests
		if ( wp_doing_ajax() ) {
			$this->requires_cookies = true;
			return;
		}

		// Require cookies if user is logged in (they might have saved cart)
		if ( is_user_logged_in() ) {
			$this->requires_cookies = true;
			return;
		}

		// Require cookies if there are existing cart cookies (user has items in cart)
		if ( isset( $_COOKIE['woocommerce_items_in_cart'] ) || isset( $_COOKIE['woocommerce_cart_hash'] ) ) {
			$this->requires_cookies = true;
			return;
		}

		// Check if this is a product page with add-to-cart functionality
		if ( is_product() && $this->has_add_to_cart_form() ) {
			$this->requires_cookies = true;
			return;
		}

		// Default to not requiring cookies for better caching
		$this->requires_cookies = false;
	}

	/**
	 * Set cookie requirement for AJAX requests.
	 */
	public function set_ajax_cookie_requirement() {
		$this->requires_cookies = true;
	}

	/**
	 * Check if current page is non-cacheable.
	 *
	 * @return bool
	 */
	private function is_non_cacheable_page() {
		global $wp;

		// Check if we're on a WooCommerce page
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
			return false;
		}

		// Check specific page endpoints
		foreach ( $this->non_cacheable_pages as $page ) {
			if ( is_wc_endpoint_url( $page ) ) {
				return true;
			}
		}

		// Check if we're on cart, checkout, or account pages
		return is_cart() || is_checkout() || is_account_page();
	}

	/**
	 * Check if current product page has add-to-cart form.
	 *
	 * @return bool
	 */
	private function has_add_to_cart_form() {
		global $product;
		
		if ( ! $product ) {
			return false;
		}

		// Check if product is purchasable
		if ( ! $product->is_purchasable() ) {
			return false;
		}

		// Check if product is in stock
		if ( ! $product->is_in_stock() ) {
			return false;
		}

		return true;
	}

	/**
	 * Conditionally disable cookie setting based on context.
	 *
	 * @param bool   $enabled Whether cookie should be set.
	 * @param string $name    Cookie name.
	 * @param string $value   Cookie value.
	 * @param int    $expire  Cookie expiration.
	 * @param bool   $secure  Whether cookie is secure.
	 * @return bool
	 */
	public function maybe_disable_cookies( $enabled, $name, $value, $expire, $secure ) {
		// Always allow session cookies (they're essential for functionality)
		if ( strpos( $name, 'wp_woocommerce_session_' ) === 0 ) {
			return $enabled;
		}

		// For cart cookies, check if we're in a context that requires them
		if ( in_array( $name, array( 'woocommerce_items_in_cart', 'woocommerce_cart_hash' ), true ) ) {
			return $this->requires_cookies;
		}

		// Allow other cookies by default
		return $enabled;
	}

	/**
	 * Skip cart cookie setting if not required.
	 *
	 * @param bool $set Whether to set cart cookies.
	 * @return bool
	 */
	public function maybe_skip_cart_cookies( $set ) {
		// If we don't require cookies and we're trying to set them, skip
		if ( $set && ! $this->requires_cookies ) {
			return false;
		}

		return $set;
	}

	/**
	 * Override the parent method to add cache optimization.
	 */
	public function maybe_set_cart_cookies() {
		// If we don't require cookies, skip entirely
		if ( ! $this->requires_cookies ) {
			return;
		}

		// Call parent method
		parent::maybe_set_cart_cookies();
	}

	/**
	 * Get cache optimization status for debugging.
	 *
	 * @return array
	 */
	public function get_cache_optimization_status() {
		return array(
			'requires_cookies' => $this->requires_cookies,
			'is_non_cacheable_page' => $this->is_non_cacheable_page(),
			'is_ajax' => wp_doing_ajax(),
			'is_logged_in' => is_user_logged_in(),
			'has_cart_cookies' => isset( $_COOKIE['woocommerce_items_in_cart'] ) || isset( $_COOKIE['woocommerce_cart_hash'] ),
			'is_product_with_cart' => is_product() && $this->has_add_to_cart_form(),
		);
	}
}