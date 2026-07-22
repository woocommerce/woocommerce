<?php
/**
 * Plugin Name: WooCommerce Blocks Test WC Bundle Demo
 * Description: Minimal fixture proving a bundle-style Store API extension built entirely on today's extension points and the public woocommerce/cart store surface.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-wc-bundle-demo
 */

/**
 * What this simulates.
 *
 * A "bundle" product made of two independently configurable child products,
 * added to the cart as one unit. Real bundle-style extensions (Product
 * Bundles, Composite Products, …) are not installed in the e2e environment,
 * so this fixture stands in for one, built on nothing but the surface a
 * third-party extension has today: the Store API's `ExtendSchema` (schema
 * extension + add-to-cart processing) and the public (locked)
 * `woocommerce/cart` Interactivity API store (`upsertDraftItem`, direct
 * mutation of a resolved draft, `addItem( payload )`). No WooCommerce core
 * file is changed.
 *
 * How it works.
 *
 * The `[wc_bundle_demo bundle="<id>" child_a="<id>" child_b="<id>"]`
 * shortcode renders two "slot" elements — one per child product — plus an
 * "Add bundle to cart" button. Each slot declares its own literal,
 * namespaced `woocommerce/cart` draft key (`wc-bundle-demo/slot-1` /
 * `wc-bundle-demo/slot-2`) — the same container primitive core blocks use,
 * addressed directly from markup with no registry of any kind — so picking
 * the same product in both slots produces two independent drafts rather
 * than one overwriting the other. A slot's quantity input has no init: its
 * first edit creates the slot's one draft via the store's public
 * `upsertDraftItem` (a creation convenience), and every edit after that is a
 * direct mutation of the already-resolved draft object, not an action call;
 * the slot renders a binding that reads the draft so either write's
 * re-render is observable. The button composes both slots' current drafts
 * by reading `state.draftItems` at its two declared keys directly — under
 * its existing lock consent — into one `cart/add-item` payload for the
 * bundle product, carrying a `wc-bundle-demo/children` prop at the payload
 * root, and posts it verbatim via the store's public `addItem( payload )`;
 * because the compose reads the live collections at click time, it honors
 * any direct writes, and a slot never edited composes nothing (its
 * collection was never created).
 *
 * Server-side, `add_children_to_cart_item_data()` reads that prop off the
 * add-item request and folds it into the cart line's `cart_item_data` (so
 * core's line-identity hashing sees it, and it persists on the session cart
 * item); `get_extended_data()` / `get_extended_schema()` expose it back on
 * the cart-item response as `extensions['wc-bundle-demo'].children`.
 *
 * How to activate it.
 *
 * Activate by its WordPress slug — the @package value above,
 * "woocommerce-blocks-test-wc-bundle-demo" — e.g.
 * requestUtils.activatePlugin( ... ), as the sibling helper plugins here are.
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * The `wc-bundle-demo` fixture: a minimal bundle-style Store API extension
 * plus its client Interactivity API store.
 */
class WC_Bundle_Demo_Fixture {

	/**
	 * The namespace shared by the client Interactivity API store, the
	 * add-item request prop, and the Store API schema extension.
	 *
	 * @var string
	 */
	const EXTENSION_NAMESPACE = 'wc-bundle-demo';

	/**
	 * The `cart/add-item` request prop carrying the bundle's child drafts,
	 * riding at the payload root exactly like any other namespaced
	 * extension prop.
	 *
	 * @var string
	 */
	const CHILDREN_PROP = self::EXTENSION_NAMESPACE . '/children';

	/**
	 * The shortcode tag rendering the demo bundle UI.
	 *
	 * @var string
	 */
	const SHORTCODE = 'wc_bundle_demo';

	/**
	 * Registers every hook the fixture needs.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_script_module' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
		add_action( 'woocommerce_init', array( $this, 'extend_store_api' ) );
	}

	/**
	 * Registers the demo's Interactivity API script module.
	 *
	 * A plain, unbundled ES module: `@wordpress/interactivity` and
	 * `@woocommerce/stores/woocommerce/cart` are both already-registered
	 * script modules (the latter is always registered by WooCommerce's
	 * asset controller, whether or not any WooCommerce block is on the
	 * page), so a third-party script module can depend on them with no
	 * build step of its own — exactly as a real extension does while the
	 * cart store is private.
	 */
	public function register_script_module() {
		wp_register_script_module(
			self::EXTENSION_NAMESPACE,
			plugins_url( 'bundle-demo.js', __FILE__ ),
			array( '@wordpress/interactivity', '@woocommerce/stores/woocommerce/cart' ),
			false
		);
	}

	/**
	 * Renders the `[wc_bundle_demo]` shortcode: two child slots plus an
	 * "Add bundle to cart" button.
	 *
	 * @param array $atts Shortcode attributes: `bundle`, `child_a`, `child_b`
	 *                    product ids.
	 * @return string The rendered markup, or an empty string when any of the
	 *                three product ids fails to resolve to a product.
	 */
	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'bundle'  => 0,
				'child_a' => 0,
				'child_b' => 0,
			),
			$atts,
			self::SHORTCODE
		);

		$bundle_id = absint( $atts['bundle'] );

		if ( ! $bundle_id || ! wc_get_product( $bundle_id ) ) {
			return '';
		}

		$slot_1 = $this->render_slot( 'slot-1', absint( $atts['child_a'] ) );
		$slot_2 = $this->render_slot( 'slot-2', absint( $atts['child_b'] ) );

		if ( '' === $slot_1 || '' === $slot_2 ) {
			return '';
		}

		wp_enqueue_script_module( self::EXTENSION_NAMESPACE );

		$wrapper_context_directive = wp_interactivity_data_wp_context(
			array( 'bundleProductId' => $bundle_id ),
			self::EXTENSION_NAMESPACE
		);

		return sprintf(
			'<div class="wc-bundle-demo" data-wp-interactive="%1$s" %2$s>%3$s%4$s<button type="button" data-wp-on--click="actions.addBundleToCart">%5$s</button></div>',
			esc_attr( self::EXTENSION_NAMESPACE ),
			$wrapper_context_directive,
			$slot_1,
			$slot_2,
			esc_html__( 'Add bundle to cart', 'woocommerce' )
		);
	}

	/**
	 * Renders one child slot: a declared `woocommerce/cart` draft key plus a
	 * quantity input.
	 *
	 * The slot element carries two context bags on one element — its own
	 * `wc-bundle-demo` context (`childId`, `slotId`) and its own literal,
	 * namespaced `woocommerce/cart` draft key (`wc-bundle-demo/slot-1` /
	 * `wc-bundle-demo/slot-2`) that isolates this slot's draft from the
	 * other slot and from any other purchase surface on the page. A second
	 * default `data-wp-context` would silently collide with the first (the
	 * HTML parser keeps only one `data-wp-context` attribute per element),
	 * so the key bag is hand-rolled as the three-hyphen
	 * `data-wp-context---draft-key` form — the same mechanism
	 * `ProductTemplate.php` / `SingleProduct.php` use to declare their own
	 * server-minted key alongside their own default context, and that
	 * `Wishlist.php` / `SavedForLater.php` already ship for a second
	 * namespace (`data-wp-context---notices`).
	 *
	 * The quantity input has no init: its `data-wp-on--change` creates the
	 * slot's one draft on its first edit (the store's public
	 * `upsertDraftItem`, addressed by the slot's declared key) and directly
	 * mutates the already-resolved draft on every edit after that. The
	 * `<span>` renders a binding onto the same draft so either write's
	 * re-render is observable.
	 *
	 * @param string $slot     The slot identifier (`slot-1`/`slot-2`), also
	 *                         the suffix of the slot's own declared draft key.
	 * @param int    $child_id The child product id this slot drafts.
	 * @return string The rendered slot markup, or an empty string when
	 *                `$child_id` does not resolve to a product.
	 */
	private function render_slot( string $slot, int $child_id ): string {
		$product = $child_id ? wc_get_product( $child_id ) : false;

		if ( ! $product ) {
			return '';
		}

		$slot_context_directive = wp_interactivity_data_wp_context(
			array(
				'childId' => $child_id,
				'slotId'  => $slot,
			),
			self::EXTENSION_NAMESPACE
		);

		// Hand-rolled second context bag: JSON_HEX_APOS is required because
		// this markup uses single-quoted attribute values (see the
		// docblock above for why a second default `data-wp-context` cannot
		// be used instead). This declares the slot's own literal,
		// namespaced `woocommerce/cart` draft key, exactly as
		// ProductTemplate.php / SingleProduct.php declare their own
		// server-minted key for their own containers — the extension gets
		// the primitive from markup alone, with zero core changes.
		$draft_key_context_directive = 'data-wp-context---draft-key=\'woocommerce/cart::' . wp_json_encode(
			array( 'draftKey' => self::EXTENSION_NAMESPACE . '/' . $slot ),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		) . '\'';

		return sprintf(
			'<div class="wc-bundle-demo__slot" %1$s %2$s>' .
			'<label>%3$s</label>' .
			'<input type="number" min="0" step="1" value="1" data-wp-on--change="actions.onSlotQuantityChange" />' .
			'<span class="wc-bundle-demo__slot-quantity" data-wp-text="state.slotQuantityText"></span>' .
			'</div>',
			$slot_context_directive,
			$draft_key_context_directive,
			esc_html( $product->get_name() )
		);
	}

	/**
	 * Registers the Store API extension: the `wc-bundle-demo/children`
	 * prop on `cart/add-item` requests, and its readback on the cart-item
	 * response as `extensions['wc-bundle-demo']`.
	 */
	public function extend_store_api() {
		if ( ! class_exists( ExtendSchema::class ) || ! class_exists( StoreApi::class ) ) {
			return;
		}

		$extend_schema = StoreApi::container()->get( ExtendSchema::class );

		$extend_schema->register_endpoint_data(
			array(
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => self::EXTENSION_NAMESPACE,
				'schema_callback' => array( $this, 'get_extended_schema' ),
				'data_callback'   => array( $this, 'get_extended_data' ),
			)
		);

		add_filter( 'woocommerce_store_api_add_to_cart_data', array( $this, 'add_children_to_cart_item_data' ), 10, 2 );
	}

	/**
	 * Folds the request's `wc-bundle-demo/children` prop into the
	 * add-to-cart `cart_item_data`, so core's cart-line hashing sees it and
	 * it persists on the session cart item for `get_extended_data()` to
	 * read back.
	 *
	 * @param array            $add_to_cart_data Cart item data being assembled.
	 * @param \WP_REST_Request $request          The add-item request.
	 * @return array The cart item data, with the children prop added when present.
	 */
	public function add_children_to_cart_item_data( $add_to_cart_data, $request ) {
		$children = $request->get_param( self::CHILDREN_PROP );

		if ( empty( $children ) || ! is_array( $children ) ) {
			return $add_to_cart_data;
		}

		$add_to_cart_data['cart_item_data'][ self::CHILDREN_PROP ] = $this->sanitize_children( $children );

		return $add_to_cart_data;
	}

	/**
	 * Sanitizes the posted children into `{ id, quantity }` pairs.
	 *
	 * @param array $children Raw posted children.
	 * @return array Sanitized `{ id: int, quantity: float }` pairs; entries
	 *               missing an id are dropped.
	 */
	private function sanitize_children( array $children ): array {
		$sanitized = array();

		foreach ( $children as $child ) {
			if ( ! is_array( $child ) || empty( $child['id'] ) ) {
				continue;
			}

			$sanitized[] = array(
				'id'       => absint( $child['id'] ),
				'quantity' => wc_stock_amount( $child['quantity'] ?? 0 ),
			);
		}

		return $sanitized;
	}

	/**
	 * The `data_callback` for the `wc-bundle-demo` extension namespace:
	 * returns the cart line's stored children, if any.
	 *
	 * @param array $cart_item The cart item array.
	 * @return array `{ children: array }`.
	 */
	public function get_extended_data( $cart_item ): array {
		return array(
			'children' => $cart_item[ self::CHILDREN_PROP ] ?? array(),
		);
	}

	/**
	 * The `schema_callback` for the `wc-bundle-demo` extension namespace.
	 *
	 * @return array The `children` property's schema.
	 */
	public function get_extended_schema(): array {
		return array(
			'children' => array(
				'description' => __( 'Bundle child products added alongside this line.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
}

new WC_Bundle_Demo_Fixture();
