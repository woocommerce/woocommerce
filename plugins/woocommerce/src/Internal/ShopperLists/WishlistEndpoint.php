<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * PoC: a `/my-account/wishlist/` endpoint that renders the existing
 * `woocommerce/shopper-collection` block (Saved for Later) inside My Account.
 *
 * Purpose: validate that the Shopper Collection block can render outside its
 * current cart-page injection point and still interact correctly with the cart
 * store. For the PoC the data is the user's existing saved-for-later list —
 * a dedicated wishlist slug and per-list config arrive in a follow-up.
 *
 * Adding the endpoint slug to `woocommerce_get_query_vars` is enough to wire
 * both the query var (`WC_Query::add_query_vars()`) and the rewrite endpoint
 * (`WC_Query::add_endpoints()`) — both consult that filter.
 *
 * @internal Just for internal use.
 */
class WishlistEndpoint implements RegisterHooksInterface {

	/**
	 * Endpoint slug.
	 */
	private const ENDPOINT = 'wishlist';

	/**
	 * Register hooks and filters.
	 */
	public function register() {
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_var' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( $this, 'endpoint_title' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render' ) );
	}

	/**
	 * Register the `wishlist` query var. `WC_Query` consults this filter from
	 * both `add_endpoints()` and `add_query_vars()`, so adding the slug here
	 * gives us both the rewrite endpoint and the recognized query var in one
	 * shot.
	 *
	 * @param array $vars Existing query vars keyed by slug.
	 * @return array
	 */
	public function add_query_var( $vars ) {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Insert the Wishlist link into the My Account navigation, just before the
	 * logout link.
	 *
	 * @param array $items Existing menu items keyed by slug.
	 * @return array
	 */
	public function add_menu_item( $items ) {
		$new_items = array();
		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'Wishlist', 'woocommerce' );
			}
			$new_items[ $key ] = $label;
		}
		if ( ! isset( $new_items[ self::ENDPOINT ] ) ) {
			$new_items[ self::ENDPOINT ] = __( 'Wishlist', 'woocommerce' );
		}
		return $new_items;
	}

	/**
	 * Endpoint page title.
	 *
	 * @param string $title Default title (empty for unknown endpoints).
	 * @return string
	 */
	public function endpoint_title( $title ) {
		return __( 'Wishlist', 'woocommerce' );
	}

	/**
	 * Render the Shopper Collection block inside the endpoint. The block is
	 * already gated by the `cart_save_for_later` feature flag at registration
	 * time, so if the flag is off `do_blocks()` parses the comment but renders
	 * nothing — no separate guard needed here.
	 */
	public function render() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup is a static literal; do_blocks() output is rendered HTML.
		echo do_blocks( '<!-- wp:woocommerce/shopper-collection {"listName":"saved-for-later"} /-->' );
	}
}
