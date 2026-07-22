<?php
/**
 * Plugin Name: WooCommerce Blocks Test Navigation Survival
 * Description: Minimal fixture proving cross-page client-side navigation survival of drafts held in the public woocommerce/cart store's keyed global state, on the stock region-based Interactivity API router.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-navigation-survival
 */

declare( strict_types = 1 );

/**
 * What this simulates.
 *
 * Two ordinary block-theme pages, each rendering a purchase surface for the
 * same product, plus a link that drives a genuine client-side
 * `actions.navigate()` round trip between them (the stock, supported
 * region-based Interactivity API router — no experimental full-page mode,
 * no runtime patch). Because both pages wrap their content in one
 * top-level `data-wp-router-region` sharing the same id, the router's
 * region-matching swap keeps the JS runtime and its script modules alive
 * across the navigation instead of reloading the document, so the public
 * `woocommerce/cart` store's global draft state persists exactly as it
 * would for a shopper crossing between any two pages on a real site.
 *
 * How it works.
 *
 * The `[wc_navigation_survival product="<id>" page="a|b" target="<url>"]`
 * shortcode renders:
 *  - one purchase surface with no `woocommerce/cart` context of its own
 *    ("unwrapped") — it resolves whichever collection the store falls back
 *    to when no draft key is declared, exactly like a plain, container-free
 *    Add to Cart with Options form;
 *  - on `page="a"` only, a second surface wrapped in this fixture's own
 *    declared `woocommerce/cart` draft key (the same
 *    `data-wp-context---draft-key` container primitive core blocks use,
 *    addressed directly from markup — see `bundle-demo.php` for the
 *    identical technique); and
 *  - a link whose `data-wp-on--click` imports
 *    `@wordpress/interactivity-router` and calls `actions.navigate()` on
 *    the anchor's own `href` — the same shipped pattern
 *    `product-collection/frontend.ts` uses for its own client-side
 *    pagination links.
 *
 * Both surfaces' quantity inputs have no init: a first edit creates the
 * resolved collection's one draft via the store's public `upsertDraftItem`
 * (a creation convenience), and every edit after that is a direct mutation
 * of the already-resolved draft object; a bound `<span>` re-renders from
 * the same resolved draft either way. Because the unwrapped surface on
 * page A and the unwrapped surface on page B resolve the identical
 * fallback collection for the identical product id, an edit made on one is
 * visible on the other after navigating between them — while the keyed
 * surface's own collection, addressed only by this fixture's own declared
 * key, never leaks into either unwrapped surface.
 *
 * How to activate it.
 *
 * Activate by its WordPress slug — the @package value above,
 * "woocommerce-blocks-test-navigation-survival" — e.g.
 * requestUtils.activatePlugin( ... ), as the sibling helper plugins here
 * are.
 */

/**
 * The `wc-navigation-survival` fixture: two cross-linked purchase surfaces
 * proving draft survival across a genuine client-side navigation.
 */
class WC_Navigation_Survival_Fixture {

	/**
	 * The namespace shared by the client Interactivity API store and its
	 * own default `data-wp-context` bag.
	 *
	 * @var string
	 */
	const NAMESPACE = 'wc-navigation-survival';

	/**
	 * The literal id shared by both pages' top-level `data-wp-router-region`
	 * — the router matches regions by id, so a shared id across two
	 * otherwise-unrelated pages is what makes the swap between them
	 * coherent (rather than the region being emptied as one-sided).
	 *
	 * @var string
	 */
	const ROUTER_REGION_ID = self::NAMESPACE . '-region';

	/**
	 * This fixture's own literal, namespaced `woocommerce/cart` draft key,
	 * declared only by the "keyed" surface (page A only) — the same
	 * container primitive core blocks use, addressed directly from markup
	 * with no registry of any kind.
	 *
	 * @var string
	 */
	const KEYED_DRAFT_KEY = self::NAMESPACE . '/keyed';

	/**
	 * The shortcode tag rendering one page's fixture markup.
	 *
	 * @var string
	 */
	const SHORTCODE = 'wc_navigation_survival';

	/**
	 * Registers every hook the fixture needs.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_script_module' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
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
	 *
	 * `@wordpress/interactivity-router` is declared as a **dynamic**
	 * dependency (`import => 'dynamic'`): the shipped navigation pattern
	 * this fixture reuses (see `product-collection/frontend.ts`) only ever
	 * imports it on demand, from inside the click handler, so it must not
	 * be loaded eagerly — but it still has to be present in this page's
	 * import map for that dynamic `import()` to resolve at all, which a
	 * plain, undeclared dynamic `import()` cannot do on its own. This
	 * mirrors WooCommerce's own build output for
	 * `woocommerce/product-collection.js` verbatim (see
	 * `assets/client/blocks/interactivity-blocks-frontend-assets.php`).
	 */
	public function register_script_module() {
		wp_register_script_module(
			self::NAMESPACE,
			plugins_url( 'navigation-survival.js', __FILE__ ),
			array(
				'@wordpress/interactivity',
				'@woocommerce/stores/woocommerce/cart',
				array(
					'id'     => '@wordpress/interactivity-router',
					'import' => 'dynamic',
				),
			),
			false
		);
	}

	/**
	 * Renders the `[wc_navigation_survival]` shortcode: one page's fixture
	 * markup — a shared top-level router region wrapping an unwrapped
	 * purchase surface, a keyed surface (page A only), and a client-side
	 * navigation link to the other page.
	 *
	 * @param array $atts Shortcode attributes: `product` (product id),
	 *                    `page` (`a` or `b`), `target` (the other page's
	 *                    URL), `link_text` (the navigation link's text).
	 * @return string The rendered markup, or an empty string when `product`
	 *                does not resolve to a product, or `target` is empty.
	 */
	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'product'   => 0,
				'page'      => 'a',
				'target'    => '',
				'link_text' => __( 'Navigate', 'woocommerce' ),
			),
			$atts,
			self::SHORTCODE
		);

		$product_id = absint( $atts['product'] );
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product || '' === $atts['target'] ) {
			return '';
		}

		wp_enqueue_script_module( self::NAMESPACE );

		$wrapper_context_directive = wp_interactivity_data_wp_context(
			array( 'productId' => $product_id ),
			self::NAMESPACE
		);

		$unwrapped_surface = $this->render_surface( 'unwrapped' );
		$keyed_surface      = 'b' === $atts['page'] ? '' : $this->render_surface( 'keyed', self::KEYED_DRAFT_KEY );

		return sprintf(
			'<div class="wc-navigation-survival" data-wp-interactive="%1$s" %2$s data-wp-router-region="%3$s">%4$s%5$s<a href="%6$s" data-wp-on--click="actions.navigate">%7$s</a></div>',
			esc_attr( self::NAMESPACE ),
			$wrapper_context_directive,
			esc_attr( self::ROUTER_REGION_ID ),
			$unwrapped_surface,
			$keyed_surface,
			esc_url( $atts['target'] ),
			esc_html( $atts['link_text'] )
		);
	}

	/**
	 * Renders one purchase surface: a quantity input and a bound display,
	 * optionally declaring this fixture's own `woocommerce/cart` draft key.
	 *
	 * A surface with no `$draft_key` declares no `woocommerce/cart` context
	 * of its own ("unwrapped") — it inherits none from its ancestors either
	 * (the wrapper above declares only this fixture's own default-namespace
	 * context), so the store resolves its usual fallback collection for it,
	 * exactly like a plain, container-free Add to Cart with Options form. A
	 * surface given `$draft_key` declares it via the hand-rolled
	 * three-hyphen `data-wp-context---draft-key` form — the same mechanism
	 * `ProductTemplate.php` / `SingleProduct.php` use to declare their own
	 * server-minted key alongside their own default context, and that
	 * `bundle-demo.php`'s slots already ship for their own namespaced keys.
	 *
	 * The quantity input has no init: its `data-wp-on--change` creates this
	 * surface's resolved draft on its first edit (the store's public
	 * `upsertDraftItem`) and directly mutates the already-resolved draft on
	 * every edit after that. Unlike `bundle-demo.php`'s slots, the input's
	 * own `value` is *also* reactively bound (`data-wp-bind--value`), not
	 * just the adjacent `<span>` — this surface must repaint correctly on a
	 * freshly server-rendered instance of itself after a cross-page
	 * navigation (a brand new DOM node, statically carrying only its
	 * server-seeded default), exactly like a real purchase surface's
	 * quantity input does; a bundle-demo slot never remounts like that, so
	 * it never needed the input itself to be reactive, only its
	 * confirmation display.
	 *
	 * @param string      $role      `unwrapped` or `keyed` — used only for a
	 *                               CSS class distinguishing the two
	 *                               surfaces in markup, no directive
	 *                               behavior depends on it.
	 * @param string|null $draft_key This surface's own declared
	 *                               `woocommerce/cart` draft key, or `null`
	 *                               to declare none (unwrapped).
	 * @return string The rendered surface markup.
	 */
	private function render_surface( string $role, ?string $draft_key = null ): string {
		$draft_key_context_directive = '';

		if ( null !== $draft_key ) {
			// Hand-rolled second context bag: JSON_HEX_APOS is required
			// because this markup uses single-quoted attribute values (see
			// `bundle-demo.php`'s `render_slot()` for the identical
			// technique and rationale — a second default `data-wp-context`
			// would silently collide with the wrapper's own).
			$draft_key_context_directive = ' data-wp-context---draft-key=\'woocommerce/cart::' . wp_json_encode(
				array( 'draftKey' => $draft_key ),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			) . '\'';
		}

		return sprintf(
			'<div class="wc-navigation-survival__surface wc-navigation-survival__surface--%1$s"%2$s>' .
			'<label>%3$s</label>' .
			'<input type="number" min="0" step="1" value="1" data-wp-bind--value="state.quantityText" data-wp-on--change="actions.onQuantityChange" />' .
			'<span class="wc-navigation-survival__quantity" data-wp-text="state.quantityText"></span>' .
			'</div>',
			esc_attr( $role ),
			$draft_key_context_directive,
			esc_html( 'keyed' === $role ? __( 'Keyed quantity', 'woocommerce' ) : __( 'Unwrapped quantity', 'woocommerce' ) )
		);
	}
}

new WC_Navigation_Survival_Fixture();
