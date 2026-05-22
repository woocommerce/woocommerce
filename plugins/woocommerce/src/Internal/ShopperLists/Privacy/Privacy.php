<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists\Privacy;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListsController;
use Automattic\WooCommerce\Internal\Utilities\Users;

/**
 * GDPR/CCPA privacy exporter and eraser for shopper lists.
 *
 * Surfaces stored shopper-list data to WP's admin "Export/Erase Personal Data"
 * tools so site admins can fulfill data-subject requests. Operates over every
 * supported list type regardless of feature-flag state, so stale data from a
 * now-disabled feature is still surfaced and removed.
 *
 * @internal Just for internal use.
 */
class Privacy extends \WC_Abstract_Privacy {

	/**
	 * Identifier used to register both the exporter and the eraser with WP.
	 */
	private const REGISTRATION_ID = 'woocommerce-shopper-lists';

	/**
	 * Prefix for the per-list-type WP data group IDs (a unique slug is appended,
	 * e.g. `woocommerce-shopper-lists-saved-for-later`).
	 */
	private const GROUP_ID_PREFIX = 'woocommerce-shopper-lists-';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'init', array( $this, 'register_exporters_and_erasers' ) );
	}

	/**
	 * Register the shopper-list exporter and eraser with WordPress.
	 *
	 * @internal
	 */
	public function register_exporters_and_erasers(): void {
		$label = __( 'WooCommerce Shopper Lists', 'woocommerce' );

		$this->add_exporter( self::REGISTRATION_ID, $label, array( $this, 'export_data' ) );
		$this->add_eraser( self::REGISTRATION_ID, $label, array( $this, 'erase_data' ) );
	}

	/**
	 * Export every stored shopper list for the user matching the given email.
	 *
	 * Emits one WP data group per supported list type and one entry per saved
	 * item within it, with per-field `{name, value}` rows — matching the shape
	 * used by `WC_Privacy_Exporters::order_data_exporter()`.
	 *
	 * @internal
	 *
	 * @param string $email_address Email address the request applies to.
	 * @param int    $page          Page number. Unused — output is bounded by
	 *                              the per-list item cap, so a single page is
	 *                              sufficient.
	 *
	 * @return array{data: array<int, array<string, mixed>>, done: bool}
	 */
	public function export_data( string $email_address, int $page = 1 ): array {
		unset( $page );

		$user = get_user_by( 'email', $email_address );
		if ( ! $user instanceof \WP_User ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$user_id    = (int) $user->ID;
		$controller = wc_get_container()->get( ShopperListsController::class );
		$data       = array();

		foreach ( $controller->get_supported_slugs() as $slug ) {
			$list = ShopperList::get_by_slug( $slug, $user_id, true );
			if ( ! $list || ! $list->get_items() ) {
				continue;
			}

			$group_id    = self::GROUP_ID_PREFIX . $slug;
			$group_label = self::group_label_for_slug( $slug );
			foreach ( $list->get_items() as $item ) {
				$data[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item->get_key(),
					'data'        => self::item_export_rows( $item ),
				);
			}
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Erase every stored shopper list for the user matching the given email.
	 *
	 * @internal
	 *
	 * @param string $email_address Email address the request applies to.
	 * @param int    $page          Page number. Unused — see export_data().
	 *
	 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool}
	 */
	public function erase_data( string $email_address, int $page = 1 ): array {
		unset( $page );

		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$user = get_user_by( 'email', $email_address );
		if ( ! $user instanceof \WP_User ) {
			return $response;
		}

		$user_id    = (int) $user->ID;
		$controller = wc_get_container()->get( ShopperListsController::class );

		foreach ( $controller->get_supported_slugs() as $slug ) {
			$meta_key = ShopperList::META_KEY_PREFIX . $slug;
			if ( ! is_array( Users::get_site_user_meta( $user_id, $meta_key ) ) ) {
				continue;
			}

			Users::delete_site_user_meta( $user_id, $meta_key );
			$response['items_removed'] = true;
			$response['messages'][]    = sprintf(
				/* translators: %s: shopper-list label (e.g. "Saved for Later"). */
				__( 'Removed shopper list: %s', 'woocommerce' ),
				self::group_label_for_slug( $slug )
			);
		}

		return $response;
	}

	/**
	 * Build the `data` array (one row per field) for a single saved item.
	 *
	 * Title precedence: current product name (if the product still resolves) →
	 * snapshot captured at save time → `Product #<id>` placeholder. The URL row
	 * is included only when the item is live (publish status on the product
	 * and, for variations, the parent — see `ShopperListItem::is_live()`).
	 *
	 * @param ShopperListItem $item Item to export.
	 *
	 * @return array<int, array{name: string, value: string}>
	 */
	private static function item_export_rows( ShopperListItem $item ): array {
		$rows = array(
			array(
				'name'  => __( 'Product ID', 'woocommerce' ),
				'value' => (string) $item->get_product_id(),
			),
			array(
				'name'  => __( 'Product', 'woocommerce' ),
				'value' => self::resolve_item_title( $item ),
			),
		);

		if ( $item->get_variation_id() > 0 ) {
			$rows[]     = array(
				'name'  => __( 'Variation ID', 'woocommerce' ),
				'value' => (string) $item->get_variation_id(),
			);
			$attributes = self::format_variation_attributes( $item->get_variation_attributes() );
			if ( '' !== $attributes ) {
				$rows[] = array(
					'name'  => __( 'Variation', 'woocommerce' ),
					'value' => $attributes,
				);
			}
		}

		$rows[] = array(
			'name'  => __( 'Quantity', 'woocommerce' ),
			'value' => (string) $item->get_quantity(),
		);
		$rows[] = array(
			'name'  => __( 'Date Added', 'woocommerce' ),
			'value' => $item->get_date_added_gmt(),
		);

		$product = $item->get_product();
		if ( $product instanceof \WC_Product && $item->is_live() ) {
			$rows[] = array(
				'name'  => __( 'URL', 'woocommerce' ),
				'value' => $product->get_permalink(),
			);
		}

		return $rows;
	}

	/**
	 * Resolve a human-readable title for the item.
	 *
	 * @param ShopperListItem $item Item to title.
	 */
	private static function resolve_item_title( ShopperListItem $item ): string {
		$product = $item->get_product();
		$title   = $product instanceof \WC_Product ? $product->get_name() : '';
		if ( '' === $title ) {
			$title = $item->get_product_title_at_save();
		}
		if ( '' === $title ) {
			/* translators: %d: product ID for which no title could be resolved. */
			$title = sprintf( __( 'Product #%d', 'woocommerce' ), $item->get_product_id() );
		}
		return $title;
	}

	/**
	 * Format `[ 'attribute_color' => 'red', ... ]` as a `Color: red, ...` string
	 * suitable for a single row value. Drops the storage `attribute_` prefix and
	 * title-cases the remaining slug.
	 *
	 * @param array<string, string> $attributes Variation attributes as stored.
	 */
	private static function format_variation_attributes( array $attributes ): string {
		$pairs = array();
		foreach ( $attributes as $key => $value ) {
			$name = (string) preg_replace( '/^attribute_/', '', (string) $key );
			$name = ucwords( str_replace( array( '-', '_' ), ' ', $name ) );
			if ( '' === $name || '' === (string) $value ) {
				continue;
			}
			$pairs[] = sprintf( '%s: %s', $name, (string) $value );
		}
		return implode( ', ', $pairs );
	}

	/**
	 * Friendly user-facing label for a list slug — used as the group heading in
	 * the export and in the eraser's per-list messages.
	 *
	 * @param string $slug List slug.
	 */
	private static function group_label_for_slug( string $slug ): string {
		switch ( $slug ) {
			case 'saved-for-later':
				return __( 'Saved for Later', 'woocommerce' );
			case 'wishlist':
				return __( 'Wishlist', 'woocommerce' );
			default:
				return $slug;
		}
	}
}
