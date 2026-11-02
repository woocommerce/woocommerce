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
 * @internal Just for internal use.
 */
class Privacy extends \WC_Abstract_Privacy {

	/**
	 * Identifier used to register both the exporter and the eraser with WP.
	 */
	private const REGISTRATION_ID = 'woocommerce-shopper-lists';

	/**
	 * Prefix for the per-list-type WP data group IDs.
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
	 * @internal
	 *
	 * @param string $email_address Email address the request applies to.
	 *
	 * @return array{data: array<int, array<string, mixed>>, done: bool}
	 */
	public function export_data( string $email_address ): array {
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
			$list = ShopperList::get_by_slug_raw( $slug, $user_id );
			if ( ! $list || ! $list->get_items() ) {
				continue;
			}

			$group_id    = self::GROUP_ID_PREFIX . $slug;
			$group_label = sprintf(
				/* translators: %s: shopper-list slug. */
				__( 'Shopper List: %s', 'woocommerce' ),
				$slug
			);
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
	 *
	 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool}
	 */
	public function erase_data( string $email_address ): array {
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
			if ( ! Users::delete_site_user_meta( $user_id, ShopperList::META_KEY_PREFIX . $slug ) ) {
				continue;
			}

			$response['items_removed'] = true;
			$response['messages'][]    = sprintf(
				/* translators: %s: shopper-list slug. */
				__( 'Shopper List: %s', 'woocommerce' ),
				$slug
			);
		}

		return $response;
	}

	/**
	 * Build the per-field `{name, value}` rows for a single saved item.
	 *
	 * @param ShopperListItem $item Item to export.
	 *
	 * @return array<int, array{name: string, value: string}>
	 */
	private static function item_export_rows( ShopperListItem $item ): array {
		$product = $item->get_product();
		$title   = ( $item->is_live() && $product instanceof \WC_Product )
			? $product->get_title()
			: $item->get_product_title_at_save();

		$rows = array(
			array(
				'name'  => __( 'Product ID', 'woocommerce' ),
				'value' => (string) $item->get_product_id(),
			),
			array(
				'name'  => __( 'Product', 'woocommerce' ),
				'value' => $title,
			),
		);

		if ( $item->get_variation_id() > 0 ) {
			$rows[]     = array(
				'name'  => __( 'Variation ID', 'woocommerce' ),
				'value' => (string) $item->get_variation_id(),
			);
			$attributes = wc_get_formatted_variation( $item->get_variation_attributes(), true );
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

		if ( $item->is_live() && $product instanceof \WC_Product ) {
			$rows[] = array(
				'name'  => __( 'URL', 'woocommerce' ),
				'value' => $product->get_permalink(),
			);
		}

		return $rows;
	}
}
