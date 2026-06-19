<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Interactivity;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Gates the Interactivity API takeover of the classic cart shortcode.
 *
 * When the 'experimental-iapi-cart' feature is enabled, the classic cart is
 * driven by the Interactivity API instead of the legacy jQuery layer
 * (assets/js/frontend/cart.js, enqueued as 'wc-cart'). This controller is the
 * single switch point: it removes the legacy script and will enqueue the iAPI
 * module + hydrate the shared cart store.
 *
 * The legacy implementation is left fully intact so the two can run side by
 * side; only the feature flag decides which one is active. The cart fragments
 * script ('wc-cart-fragments') is intentionally NOT dequeued, since the classic
 * Mini-Cart widget still relies on it.
 *
 * @since 11.0.0
 */
class ClassicCartInteractivity implements RegisterHooksInterface {

	/**
	 * Script handle of the legacy cart frontend (assets/js/frontend/cart.js).
	 *
	 * @var string
	 */
	private const LEGACY_CART_SCRIPT_HANDLE = 'wc-cart';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Runs after WC_Frontend_Scripts::load_scripts() (default priority) has
		// enqueued 'wc-cart', so the handle exists to be dequeued.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_take_over_cart' ), 100 );
	}

	/**
	 * Swap the legacy cart scripts for the Interactivity API implementation.
	 *
	 * @return void
	 */
	public function maybe_take_over_cart(): void {
		if ( ! $this->is_iapi_cart_active() ) {
			return;
		}

		// Remove the legacy jQuery interaction layer. Its server-side fallbacks
		// (form POST handler, wc-ajax endpoints) stay registered for no-JS and
		// back-compat.
		wp_dequeue_script( self::LEGACY_CART_SCRIPT_HANDLE );

		/*
		 * TODO (Phase 2): enqueue the 'woocommerce/cart' iAPI script module and
		 * hydrate the shared 'woocommerce' store via BlocksSharedState, then
		 * inject the iAPI directives onto the rendered cart markup. Until that
		 * lands, enabling the flag yields a non-interactive cart, which is why
		 * the flag ships off in core.json.
		 */
	}

	/**
	 * Whether the Interactivity API cart should take over on the current request.
	 *
	 * Mirrors the condition used to enqueue 'wc-cart' (the cart page), gated by
	 * the experimental feature flag.
	 *
	 * @return bool
	 */
	private function is_iapi_cart_active(): bool {
		return is_cart() && Features::is_enabled( 'experimental-iapi-cart' );
	}
}
