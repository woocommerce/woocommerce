<?php
declare(strict_types=1);

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
	 * Buffered XML output for HPOS orders.
	 *
	 * @var string
	 */
	protected $buffered_items = '';

	/**
	 * Constructor. Registers the export hooks.
	 */
	public function __construct() {
		add_action( 'export_wp', array( $this, 'maybe_buffer_hpos_orders' ), 10, 1 );
		add_action( 'rss2_head', array( $this, 'output_buffered_orders_after_posts' ), 999 );
	}

	/**
	 * Get class instance.
	 *
	 * @return HposOrderExportHandler
	 */
	public static function get_instance(): HposOrderExportHandler {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wraps given string in XML CDATA tag.
	 *
	 * @param string $str String to wrap in XML CDATA tag.
	 * @return string
	 */
	protected function wxr_cdata( string $str ): string {
		if ( ! seems_utf8( $str ) ) {
			$str = mb_convert_encoding( $str, 'UTF-8', 'auto' );
		}

		return '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';
	}


	/**
	 * Capture HPOS orders as XML output to be injected later.
	 *
	 * @param array $args Export arguments.
	 */
	public function maybe_buffer_hpos_orders( array $args ): void {
		if ( ! isset( $args['content'] ) || 'shop_order' !== $args['content'] ) {
			return;
		}

		$controller = wc_get_container()->get( CustomOrdersTableController::class );

		// Only run this if HPOS is enabled.
		if ( ! $controller->custom_orders_table_usage_is_enabled() ) {
			return;
		}

		ob_start();

		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'return'   => 'ids',
				'orderby'  => 'date_created',
				'order'    => 'ASC',
				'type'     => 'shop_order',
				'paginate' => false,
			)
		);

		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$this->export_order_to_xml( $order );
			}
		}

		$this->buffered_items = ob_get_clean();
	}

	/**
	 * Outputs buffered XML after WordPress native posts export.
	 */
	public function output_buffered_orders_after_posts(): void {
		if ( ! empty( $this->buffered_items ) ) {
			echo wp_kses_post( $this->buffered_items );
			$this->buffered_items = '';
		}
	}

	/**
	 * Outputs a single order in WXR-compatible XML format.
	 *
	 * @param \WC_Order $order The WooCommerce order object.
	 */
	protected function export_order_to_xml( $order ): void {
	}
}
