<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * PoC: inject a "Save to wishlist" button after the Add-to-Cart-with-Options
 * form on the single product page and wire it to the existing Saved-for-later
 * shopper list.
 *
 * The button is rendered INSIDE the form so it inherits the parent form's iAPI
 * context (`quantity`, `selectedAttributes`). The matching action lives on the
 * `woocommerce/add-to-cart-with-options` store; we just seed the
 * `woocommerce/shopper-lists` iAPI state here with the REST URL and a starter
 * nonce so the addItem action can hit the Store API.
 *
 * "Wishlist" is a label; data goes into the existing `saved-for-later` slug
 * until a dedicated wishlist slug + variation lands.
 *
 * @internal Just for internal use.
 */
class WishlistFromSingleProduct implements RegisterHooksInterface {

	/**
	 * Register hooks and filters. Piggy-backs on the SFL feature flag because
	 * the iAPI store and action this button targets are themselves gated there.
	 */
	public function register(): void {
		if ( ! FeaturesUtil::feature_is_enabled( 'cart_save_for_later' ) ) {
			return;
		}
		add_filter( 'render_block_woocommerce/add-to-cart-with-options', array( $this, 'inject_button' ), 10, 1 );
	}

	/**
	 * Append a button inside the rendered Add-to-Cart-with-Options form.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @return string
	 */
	public function inject_button( $block_content ) {
		if ( ! is_user_logged_in() ) {
			return $block_content;
		}

		// Only inject if the form rendered as an iAPI interactive form. Legacy
		// mode (cart redirect on, or extensions hooking into the form) skips
		// the `data-wp-on--submit` directive and would not have the form's
		// iAPI context available either.
		if ( false === strpos( $block_content, 'data-wp-interactive="woocommerce/add-to-cart-with-options"' ) ) {
			return $block_content;
		}

		$insertion_point = strrpos( $block_content, '</form>' );
		if ( false === $insertion_point ) {
			return $block_content;
		}

		$prefetched_items = $this->prefetch_saved_for_later_items();
		$this->seed_shopper_lists_state( $prefetched_items );

		// iAPI binds directives only AFTER first paint, so SSR has to already
		// match the intended initial state — otherwise an already-saved product
		// would flash an empty star until hydration runs and flips it. We
		// resolve the initial state here for simple products by parsing the
		// product_id out of the rendered form. For variable products the user
		// hasn't selected attributes on initial paint, so the default "not in
		// wishlist" SSR state is correct without further work.
		$initial_product_id = $this->extract_product_id( $block_content );
		$is_in_wishlist     = $initial_product_id > 0
			&& $this->is_simple_product_in_list( $initial_product_id, $prefetched_items );

		$empty_hidden_attr  = $is_in_wishlist ? ' hidden' : '';
		$filled_hidden_attr = $is_in_wishlist ? '' : ' hidden';
		$aria_pressed       = $is_in_wishlist ? 'true' : 'false';

		// `aria-pressed` carries the toggle state for assistive tech. Both
		// SSR `hidden` attributes and `aria-pressed` start at the resolved
		// value and the matching `data-wp-bind--*` directives keep them in
		// sync after hydration as the user toggles variations.
		$button_html = sprintf(
			'<button type="button" class="wc-block-add-to-wishlist-button" aria-label="%1$s" aria-pressed="%2$s" data-wp-bind--aria-pressed="state.isInWishlist" data-wp-on--click="actions.toggleWishlistFromForm" style="%3$s">'
				. '<span class="wc-block-add-to-wishlist-button__icon wc-block-add-to-wishlist-button__icon--empty" data-wp-bind--hidden="state.isInWishlist"%4$s>%6$s</span>'
				. '<span class="wc-block-add-to-wishlist-button__icon wc-block-add-to-wishlist-button__icon--filled" data-wp-bind--hidden="!state.isInWishlist"%5$s>%7$s</span>'
				. '</button>',
			esc_attr__( 'Add to wishlist', 'woocommerce' ),
			$aria_pressed,
			'background:transparent;border:none;cursor:pointer;padding:8px;color:inherit;vertical-align:middle;display:inline-flex;align-items:center;',
			$empty_hidden_attr,
			$filled_hidden_attr,
			$this->get_star_svg( false ),
			$this->get_star_svg( true )
		);

		return substr( $block_content, 0, $insertion_point ) . $button_html . substr( $block_content, $insertion_point );
	}

	/**
	 * Pull the form's `add-to-cart` hidden input value out of the rendered
	 * HTML. AddToCartWithOptions emits this for every product type, so it's
	 * the most reliable handle on which product the block is rendering for
	 * (more reliable than `get_queried_object_id()` since the block can be
	 * embedded outside the single-product template).
	 *
	 * @param string $block_content Rendered block HTML.
	 * @return int Product ID, or 0 when not found.
	 */
	private function extract_product_id( string $block_content ): int {
		if ( ! preg_match( '/name="add-to-cart"\s+value="(\d+)"/', $block_content, $matches ) ) {
			return 0;
		}
		return (int) $matches[1];
	}

	/**
	 * Whether the given product is in the prefetched list with no variation
	 * captured (i.e. it was saved as a simple/external product). Variable
	 * products require a selected variation, which only the JS-side getter
	 * has access to on initial render.
	 *
	 * @param int                              $product_id Product ID to look for.
	 * @param array<int, array<string, mixed>> $items      Prefetched items.
	 * @return bool
	 */
	private function is_simple_product_in_list( int $product_id, array $items ): bool {
		foreach ( $items as $item ) {
			if ( (int) ( $item['product_id'] ?? 0 ) !== $product_id ) {
				continue;
			}
			$variation = $item['variation'] ?? array();
			if ( ! is_array( $variation ) || 0 === count( $variation ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Inline star SVG, outlined when not filled, solid when filled. Both use
	 * `currentColor` so the surrounding button's text color drives the icon
	 * color — the same convention `ShopperCollection::get_remove_icon_svg()`
	 * follows.
	 *
	 * @param bool $filled Whether to render the filled variant.
	 * @return string
	 */
	private function get_star_svg( bool $filled ): string {
		$path   = 'M12 2.6l2.85 5.77 6.37.93-4.61 4.49 1.09 6.35L12 17.13l-5.7 3.01 1.09-6.35-4.61-4.49 6.37-.93L12 2.6z';
		$fill   = $filled ? 'currentColor' : 'none';
		$stroke = 'currentColor';
		$markup = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">'
			. '<path d="' . $path . '" fill="' . $fill . '" stroke="' . $stroke . '" stroke-width="1.5" stroke-linejoin="round"/>'
			. '</svg>';
		return $markup;
	}

	/**
	 * Seed the `woocommerce/shopper-lists` iAPI state with the REST URL, a
	 * starter nonce, and the user's current saved-for-later items so the
	 * "already in wishlist" check has correct data on first paint.
	 *
	 * Mirrors `ShopperCollection::prefetch_list_items()` rather than calling
	 * it directly to avoid coupling this PoC to a block class.
	 *
	 * @param array<int, array<string, mixed>> $items Prefetched items to seed.
	 */
	private function seed_shopper_lists_state( array $items ): void {
		wp_interactivity_state(
			'woocommerce/shopper-lists',
			array(
				'restUrl' => get_rest_url(),
				'nonce'   => wp_create_nonce( 'wc_store_api' ),
				'lists'   => array(
					'saved-for-later' => array(
						'items'     => $items,
						'isLoading' => false,
						'error'     => null,
					),
				),
			)
		);
	}

	/**
	 * Fetch the current user's saved-for-later items via the Store API so the
	 * client-side getter can compare against them on first paint.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function prefetch_saved_for_later_items(): array {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/shopper-lists/saved-for-later/items' );
		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return array();
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return array();
		}

		// Normalize stdClass entries (prices, images) into plain nested arrays
		// so they round-trip through `wp_interactivity_state` cleanly.
		$decoded = json_decode( (string) wp_json_encode( $data ), true );
		return is_array( $decoded ) ? $decoded : array();
	}
}
