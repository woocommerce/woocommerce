<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\LegacyDataHandler;

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

		// Hook into import to backfill HPOS records.
		add_action( 'wp_import_insert_post', array( $this, 'maybe_backfill_hpos_order' ), 10, 2 );
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
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->buffered_items;
			$this->buffered_items = '';
		}
	}

	/**
	 * Outputs a single order in WXR-compatible XML format.
	 *
	 * @param \WC_Order $order The WooCommerce order object.
	 */
	protected function export_order_to_xml( $order ) {
		$order_id       = $order->get_id();
		$date_created   = $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '';
		$date_modified  = $order->get_date_modified() ? $order->get_date_modified()->format( 'Y-m-d H:i:s' ) : '';
		$post_author_id = $order->get_customer_id();

		$title = sprintf( 'Order – %s', $date_created );

		?>
		<item>
			<title><?php echo esc_html( $title ); ?></title>
			<link><?php echo esc_url( get_site_url( null, "/?post_type=shop_order&p={$order_id}" ) ); ?></link>
			<pubDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', $date_created, false ) ); ?></pubDate>
			<dc:creator><?php echo esc_html( 'admin' ); ?></dc:creator>
			<guid isPermaLink="false"><?php echo esc_html( get_site_url( null, "?post_type=shop_order&p={$order_id}" ) ); ?></guid>
			<description></description>
			<content:encoded><?php echo $this->wxr_cdata( '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></content:encoded>
			<excerpt:encoded><?php echo $this->wxr_cdata( '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></excerpt:encoded>
			<wp:post_id><?php echo (int) $order_id; ?></wp:post_id>
			<wp:post_date><?php echo esc_html( $date_created ); ?></wp:post_date>
			<wp:post_date_gmt><?php echo esc_html( $date_created ); ?></wp:post_date_gmt>
			<wp:comment_status>closed</wp:comment_status>
			<wp:ping_status>closed</wp:ping_status>
			<wp:post_name>order-<?php echo (int) $order_id; ?></wp:post_name>
			<wp:status>publish</wp:status>
			<wp:post_parent>0</wp:post_parent>
			<wp:menu_order>0</wp:menu_order>
			<wp:post_type>shop_order</wp:post_type>
			<wp:post_password></wp:post_password>
			<wp:is_sticky>0</wp:is_sticky>
		</item>
		<?php
	}

	/**
	 * When an order is imported via Tools > Import, backfill it into the HPOS table.
	 *
	 * @param int   $post_id The imported post ID.
	 * @param array $original_post_id The original post object.
	 */
	public function maybe_backfill_hpos_order( $post_id, $original_post_id ) {
		if ( get_post_type( $post_id ) === 'shop_order' ) {
			try {
				$handler = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\LegacyDataHandler::class );
				$handler->backfill_order_to_datastore( $post_id, 'posts', 'hpos' );
			} catch ( \Exception $e ) {
				error_log( 'HPOS import backfill failed for order ' . $post_id . ': ' . $e->getMessage() );
			}
		}
	}
}
