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
	 * WP "data group" identifier used by both the exporter and the eraser.
	 */
	private const GROUP_ID = 'woocommerce-shopper-lists';

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

		$this->add_exporter( self::GROUP_ID, $label, array( $this, 'export_data' ) );
		$this->add_eraser( self::GROUP_ID, $label, array( $this, 'erase_data' ) );
	}

	/**
	 * Export every stored shopper list for the user matching the given email.
	 *
	 * @internal
	 *
	 * @param string $email_address Email address the request applies to.
	 * @param int    $page          Page number. Unused — output is bounded by the
	 *                              per-list item cap, so a single page is sufficient.
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

		$user_id     = (int) $user->ID;
		$controller  = wc_get_container()->get( ShopperListsController::class );
		$group_label = __( 'Shopper Lists', 'woocommerce' );
		$data        = array();

		foreach ( $controller->get_supported_slugs() as $slug ) {
			$list = ShopperList::get_by_slug( $slug, $user_id, true );
			if ( ! $list ) {
				continue;
			}

			$items = $list->get_items();
			if ( empty( $items ) ) {
				continue;
			}

			$data[] = array(
				'group_id'    => self::GROUP_ID,
				'group_label' => $group_label,
				'item_id'     => 'shopper-list-' . $slug,
				'data'        => self::build_list_export_rows( $list->get_slug(), $list->get_date_created_gmt(), $items ),
			);
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
		}

		return $response;
	}

	/**
	 * Build the `data` rows for a single exported list.
	 *
	 * @param string                         $slug             List slug.
	 * @param string                         $date_created_gmt Creation time (MySQL DATETIME, GMT).
	 * @param array<string, ShopperListItem> $items            Items keyed by storage key.
	 *
	 * @return array<int, array{name: string, value: string}>
	 */
	private static function build_list_export_rows( string $slug, string $date_created_gmt, array $items ): array {
		$rows = array(
			array(
				'name'  => __( 'List', 'woocommerce' ),
				'value' => $slug,
			),
			array(
				'name'  => __( 'Created', 'woocommerce' ),
				'value' => $date_created_gmt,
			),
		);

		$index = 1;
		foreach ( $items as $item ) {
			/* translators: %d: position of the item within the list. */
			$row_name = sprintf( __( 'Item %d', 'woocommerce' ), $index );
			$rows[]   = array(
				'name'  => $row_name,
				'value' => self::format_item_for_export( $item ),
			);
			++$index;
		}

		return $rows;
	}

	/**
	 * Render a single item as a human-readable line for the export.
	 *
	 * Uses the snapshot title captured at save time so the export reflects what
	 * the shopper actually saw; falls back to the live product title when the
	 * snapshot is empty (older data, or items added before title-snapshotting).
	 *
	 * @param ShopperListItem $item Item to format.
	 */
	private static function format_item_for_export( ShopperListItem $item ): string {
		$title = $item->get_product_title_at_save();
		if ( '' === $title ) {
			$product = $item->get_product();
			$title   = $product instanceof \WC_Product ? $product->get_name() : '';
		}
		if ( '' === $title ) {
			/* translators: %d: product ID for which no title could be resolved. */
			$title = sprintf( __( 'Product #%d', 'woocommerce' ), $item->get_product_id() );
		}

		return sprintf(
			/* translators: 1: product title, 2: quantity, 3: MySQL DATETIME the item was saved. */
			__( '%1$s × %2$d (added %3$s)', 'woocommerce' ),
			$title,
			$item->get_quantity(),
			$item->get_date_added_gmt()
		);
	}
}
