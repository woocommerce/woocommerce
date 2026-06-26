<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Interactivity;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * Gates the Interactivity API takeover of the classic cart shortcode.
 *
 * When the 'experimental-iapi-cart' feature is enabled, the classic cart is
 * driven by the Interactivity API instead of the legacy jQuery layer
 * (assets/js/frontend/cart.js, enqueued as 'wc-cart'). This controller is the
 * single switch point. It:
 *
 *   - removes the legacy 'wc-cart' script;
 *   - hydrates the shared 'woocommerce' cart store server-side;
 *   - injects Interactivity directives onto the server-rendered cart markup
 *     (without editing the templates) so the existing PHP hooks/filters keep
 *     producing the HTML;
 *   - enqueues the 'woocommerce/classic-cart' script module (registered through
 *     the script-module build / AssetsController).
 *
 * The legacy implementation is left fully intact so the two run side by side;
 * only the feature flag decides which is active. 'wc-cart-fragments' is NOT
 * dequeued, since the classic Mini-Cart widget still relies on it.
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
	 * Interactivity store namespace for the classic cart.
	 *
	 * @var string
	 */
	private const STORE_NAMESPACE = 'woocommerce/classic-cart';

	/**
	 * Style handle for the inline busy-state CSS.
	 *
	 * @var string
	 */
	private const STYLE_HANDLE = 'wc-classic-cart-iapi';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Runs after WC_Frontend_Scripts::load_scripts() has enqueued 'wc-cart'.
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_legacy_cart' ), 100 );

		// Inject directives into the rendered shortcode + per-row elements.
		add_filter( 'do_shortcode_tag', array( $this, 'inject_wrapper_directives' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'inject_quantity_directives' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'inject_remove_directives' ), 10, 2 );
	}

	/**
	 * Remove the legacy jQuery cart script when the iAPI cart is active.
	 *
	 * The server-side fallbacks (form POST handler, wc-ajax endpoints) stay
	 * registered for no-JS and back-compat.
	 *
	 * @return void
	 */
	public function dequeue_legacy_cart(): void {
		if ( ! $this->is_iapi_cart_active() ) {
			return;
		}
		// Removing 'wc-cart' also removes its localized `wc_cart_params` object.
		// Extensions that read `wc_cart_params` on the cart page will not find it
		// under the iAPI cart; this is a documented breaking change (see the
		// migration plan's "Legacy mechanisms" section).
		wp_dequeue_script( self::LEGACY_CART_SCRIPT_HANDLE );
	}

	/**
	 * Inject store + router directives onto the rendered cart shortcode, and
	 * decorate the global interactive controls (coupon, shipping).
	 *
	 * @param string $output The shortcode output.
	 * @param string $tag    The shortcode tag.
	 * @return string
	 */
	public function inject_wrapper_directives( $output, $tag ): string {
		if ( 'woocommerce_cart' !== $tag || ! $this->is_iapi_cart_active() ) {
			return (string) $output;
		}

		$this->hydrate_and_enqueue();

		$processor = new WP_HTML_Tag_Processor( (string) $output );

		// Outer wrapper: bind to the store, mark as the router re-render region,
		// and set up the legacy event bridge on init.
		if ( $processor->next_tag( array( 'class_name' => 'woocommerce' ) ) ) {
			$processor->set_attribute( 'data-wp-interactive', self::STORE_NAMESPACE );
			$processor->set_attribute( 'data-wp-router-region', 'woocommerce-cart' );
			$processor->set_attribute( 'data-wp-init', 'callbacks.setupCartSyncBridge' );
			// Busy state during a mutation (replaces the legacy blockUI overlay).
			$processor->set_attribute( 'data-wp-bind--aria-busy', 'state.isProcessing' );
			$processor->set_attribute( 'data-wp-class--is-cart-updating', 'state.isProcessing' );
		}

		// Decorate the remaining interactive controls.
		while ( $processor->next_tag() ) {
			$tag_name = $processor->get_tag();

			if ( 'BUTTON' === $tag_name && 'apply_coupon' === $processor->get_attribute( 'name' ) ) {
				$processor->set_attribute( 'data-wp-on--click', self::STORE_NAMESPACE . '::actions.applyCoupon' );
				continue;
			}

			if ( 'A' === $tag_name && $processor->has_class( 'woocommerce-remove-coupon' ) ) {
				$coupon = (string) $processor->get_attribute( 'data-coupon' );
				$processor->set_attribute( 'data-wp-on--click', self::STORE_NAMESPACE . '::actions.removeCoupon' );
				$processor->set_attribute( 'data-wp-context', (string) wp_json_encode( array( 'coupon' => $coupon ) ) );
				continue;
			}

			if ( 'A' === $tag_name && $processor->has_class( 'shipping-calculator-button' ) ) {
				$processor->set_attribute( 'data-wp-on--click', self::STORE_NAMESPACE . '::actions.toggleShippingCalculator' );
				continue;
			}

			// Undo/restore link shown in the "item removed" notice.
			if ( 'A' === $tag_name && $processor->has_class( 'restore-item' ) ) {
				$processor->set_attribute( 'data-wp-on--click', self::STORE_NAMESPACE . '::actions.restoreItem' );
				continue;
			}

			// Prevent the full-form POST when JS is active (quantities apply on
			// change); the native submit still works without JS.
			if ( 'FORM' === $tag_name && $processor->has_class( 'woocommerce-cart-form' ) ) {
				$processor->set_attribute( 'data-wp-on--submit', self::STORE_NAMESPACE . '::actions.preventFormSubmit' );
				continue;
			}

			if ( 'FORM' === $tag_name && $processor->has_class( 'woocommerce-shipping-calculator' ) ) {
				$processor->set_attribute( 'data-wp-on--submit', self::STORE_NAMESPACE . '::actions.calculateShipping' );
				continue;
			}

			// Shipping method radios/hidden inputs (name^="shipping_method") and
			// the select variant.
			$name = (string) $processor->get_attribute( 'name' );
			if (
				( 'INPUT' === $tag_name && 0 === strpos( $name, 'shipping_method' ) ) ||
				( 'SELECT' === $tag_name && $processor->has_class( 'shipping_method' ) )
			) {
				$processor->set_attribute( 'data-wp-on--change', self::STORE_NAMESPACE . '::actions.selectShippingRate' );
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Add the quantity-change directive + per-row context to a cart row's
	 * quantity input. Uses the cart item key available in this filter.
	 *
	 * @param string $product_quantity The quantity input HTML.
	 * @param string $cart_item_key    The cart item key.
	 * @param array  $cart_item        The cart item (unused).
	 * @return string
	 */
	public function inject_quantity_directives( $product_quantity, $cart_item_key, $cart_item ): string {
		unset( $cart_item );
		if ( ! $this->is_iapi_cart_active() ) {
			return (string) $product_quantity;
		}

		$processor = new WP_HTML_Tag_Processor( (string) $product_quantity );
		if ( $processor->next_tag(
			array(
				'tag_name'   => 'INPUT',
				'class_name' => 'qty',
			)
		) ) {
			$processor->set_attribute( 'data-wp-on--change', self::STORE_NAMESPACE . '::actions.changeQuantity' );
			$processor->set_attribute( 'data-wp-context', (string) wp_json_encode( array( 'cartItemKey' => $cart_item_key ) ) );
		}
		return $processor->get_updated_html();
	}

	/**
	 * Add the remove-item directive + per-row context to a cart row's remove
	 * link.
	 *
	 * @param string $link          The remove link HTML.
	 * @param string $cart_item_key The cart item key.
	 * @return string
	 */
	public function inject_remove_directives( $link, $cart_item_key ): string {
		if ( ! $this->is_iapi_cart_active() ) {
			return (string) $link;
		}

		$processor = new WP_HTML_Tag_Processor( (string) $link );
		if ( $processor->next_tag( array( 'tag_name' => 'A' ) ) ) {
			$processor->set_attribute( 'data-wp-on--click', self::STORE_NAMESPACE . '::actions.removeItem' );
			$processor->set_attribute( 'data-wp-context', (string) wp_json_encode( array( 'cartItemKey' => $cart_item_key ) ) );
		}
		return $processor->get_updated_html();
	}

	/**
	 * Hydrate the shared 'woocommerce' store server-side and enqueue the iAPI
	 * module.
	 *
	 * @return void
	 */
	private function hydrate_and_enqueue(): void {
		if ( class_exists( BlocksSharedState::class ) ) {
			$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
			BlocksSharedState::load_cart_state( $consent );
			BlocksSharedState::load_store_config( $consent );
		}

		// The module is built from client/blocks/assets/js/classic-cart/frontend.ts
		// (see webpack-config-interactive-blocks.js) and auto-registered by
		// AssetsController::register_script_modules() from the combined asset
		// file. Enqueuing before registration is a safe no-op until the build
		// has run.
		wp_enqueue_script_module( self::STORE_NAMESPACE );

		// Busy-state styling while a mutation is in flight: a single overlay over
		// the whole cart (dim + block pointer events), replacing the legacy
		// blockUI overlay. Note: is-cart-updating is added to the .woocommerce
		// element itself, so the selector is compound (.woocommerce.is-cart-updating),
		// not descendant. Inline-only style (no src).
		//
		// We deliberately keep this coarse overlay rather than disabling each
		// control in place (data-wp-bind--disabled / aria-disabled per element):
		// the spike is about testing feasibility, not polishing the pending-state
		// UX. In-place disabling is the nicer end state but out of scope here.
		wp_register_style( self::STYLE_HANDLE, false, array(), \WC_VERSION );
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_add_inline_style(
			self::STYLE_HANDLE,
			'.woocommerce.is-cart-updating{opacity:.6;pointer-events:none;}'
		);
	}

	/**
	 * Whether the Interactivity API cart should take over on the current request.
	 *
	 * @return bool
	 */
	private function is_iapi_cart_active(): bool {
		return is_cart() && Features::is_enabled( 'experimental-iapi-cart' );
	}
}
