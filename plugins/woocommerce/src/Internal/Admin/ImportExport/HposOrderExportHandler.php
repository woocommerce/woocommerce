<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the export of HPOS (High-Performance Order Storage) orders
 * via the WordPress Tools > Export functionality.
 *
 * @since {$version}
 */
class HposOrderExportHandler {

	/**
	 * Class instance.
	 *
	 * @var HposOrderExportHandler|null
	 */
	protected static $instance = null;

	/**
	 * Constructor. Registers the export hook.
	 */
	public function __construct() {
		add_action( 'export_wp', array( $this, 'maybe_output_hpos_orders' ), 10, 1 );
	}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into the WordPress export system and output HPOS orders if applicable.
	 *
	 * @param array $args Export arguments.
	 */
	public function maybe_output_hpos_orders( $args ) {
		if ( ! isset( $args['content'] ) || 'shop_order' !== $args['content'] ) {
			return;
		}

		$controller = wc_get_container()->get( CustomOrdersTableController::class );

		// Only run this if HPOS is enabled.
		if ( ! $controller->custom_orders_table_usage_is_enabled() ) {
			return;
		}

		$orders = wc_get_orders(
			array(
				'limit'        => -1,
				'return'       => 'ids',
				'orderby'      => 'date_created',
				'order'        => 'ASC',
				'type'         => 'shop_order',
				'paginate'     => false,
				'date_created' => '>' . gmdate( 'Y-m-d H:i:s', 0 ),
			)
		);

		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$this->export_order_to_xml( $order );
			}
		}
	}

	/**
	 * Outputs orders in XML format.
	 *
	 * @param \WC_Order $order The WooCommerce order object.
	 */
	protected function export_order_to_xml( $order ) {
	}
}
