<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Tracks which shopper-list types are turned on and registers the
 * user-facing pieces that depend on each.
 *
 * @internal Just for internal use.
 */
final class ShopperListsController implements RegisterHooksInterface {

	/**
	 * Known list slugs and the feature flag that controls each.
	 */
	private const SUPPORTED_LISTS = array(
		'saved-for-later' => 'cart_save_for_later',
		'wishlist'        => 'product_wishlist',
	);

	/**
	 * Wishlist My Account endpoint slug. Wrapped in a method (rather than
	 * a constant) so a future filter or settings hook can override it
	 * without touching every call site.
	 */
	public function get_wishlist_endpoint(): string {
		return 'wishlist';
	}

	/**
	 * Whether a given list type is on, or whether any list type is on
	 * when no slug is passed.
	 *
	 * @param string|null $list_slug List slug, or null to ask about any type.
	 */
	public function is_enabled( ?string $list_slug = null ): bool {
		if ( null === $list_slug ) {
			foreach ( self::SUPPORTED_LISTS as $feature ) {
				if ( FeaturesUtil::feature_is_enabled( $feature ) ) {
					return true;
				}
			}
			return false;
		}
		$feature = self::SUPPORTED_LISTS[ $list_slug ] ?? null;
		return null !== $feature && FeaturesUtil::feature_is_enabled( $feature );
	}

	/**
	 * Slugs of all currently-enabled lists, in declaration order.
	 *
	 * @return string[]
	 */
	public function get_enabled_slugs(): array {
		return array_keys(
			array_filter(
				self::SUPPORTED_LISTS,
				static fn( string $feature ): bool => FeaturesUtil::feature_is_enabled( $feature )
			)
		);
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'maybe_flush_rewrite_rules' ), 10, 1 );
		add_action( 'init', array( $this, 'maybe_register_wishlist_endpoint' ), 5 );
	}

	/**
	 * Register the wishlist endpoint.
	 */
	public function maybe_register_wishlist_endpoint(): void {
		if ( ! $this->is_enabled( 'wishlist' ) ) {
			return;
		}

		$endpoint = $this->get_wishlist_endpoint();
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_wishlist_query_var' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_wishlist_menu_item' ) );
		add_filter( 'woocommerce_endpoint_' . $endpoint . '_title', array( $this, 'wishlist_endpoint_title' ) );
		add_action( 'woocommerce_account_' . $endpoint . '_endpoint', array( $this, 'render_wishlist_endpoint' ) );
	}

	/**
	 * Flush rewrite rules when the wishlist feature is turned on or off.
	 *
	 * @param string $feature_id The feature that changed.
	 */
	public function maybe_flush_rewrite_rules( string $feature_id ): void {
		if ( 'product_wishlist' === $feature_id ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		}
	}

	/**
	 * Register the `wishlist` query var.
	 *
	 * @param array $vars Existing query vars keyed by slug.
	 */
	public function add_wishlist_query_var( $vars ): array {
		if ( ! is_array( $vars ) ) {
			return array();
		}

		$endpoint          = $this->get_wishlist_endpoint();
		$vars[ $endpoint ] = $endpoint;
		return $vars;
	}

	/**
	 * Insert the Wishlist link just before the logout link.
	 *
	 * @param array $items Existing menu items keyed by slug.
	 */
	public function add_wishlist_menu_item( $items ): array {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$wishlist_endpoint = $this->get_wishlist_endpoint();
		$wishlist_label    = __( 'Wishlist', 'woocommerce' );

		// Insert the wishlist item before the logout item, or at the end if not present.
		$logout_pos = array_search( 'customer-logout', array_keys( $items ), true );
		if ( false === $logout_pos ) {
			$items[ $wishlist_endpoint ] = $wishlist_label;
		} else {
			$items = array_slice( $items, 0, $logout_pos, true )
				+ array( $wishlist_endpoint => $wishlist_label )
				+ array_slice( $items, $logout_pos, null, true );
		}
		return $items;
	}

	/**
	 * Wishlist endpoint page title.
	 *
	 * @param string $title Default title.
	 */
	public function wishlist_endpoint_title( $title ): string {
		return __( 'Wishlist', 'woocommerce' );
	}

	/**
	 * Render the wishlist endpoint by dispatching to the
	 * `woocommerce/wishlist` block. The block handles the empty state,
	 * logged-out guard, asset enqueues, and item rendering.
	 */
	public function render_wishlist_endpoint(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the block string is a static literal; `do_blocks()` invokes the registered block's render callback, which is responsible for its own escaping.
		echo do_blocks( '<!-- wp:woocommerce/wishlist /-->' );
	}
}
