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
 * @since 11.2.0
 */
class HposOrderExportHandler {

	/**
	 * Custom orders table controller.
	 *
	 * @var CustomOrdersTableController
	 */
	private CustomOrdersTableController $cot_controller;

	/**
	 * Legacy data handler used to backfill imported orders into HPOS.
	 *
	 * @var LegacyDataHandler
	 */
	private LegacyDataHandler $legacy_data_handler;

	/**
	 * Buffered XML output for HPOS orders.
	 *
	 * @var string
	 */
	private string $buffered_items = '';

	/**
	 * Constructor. Registers the export and import hooks.
	 */
	public function __construct() {
		add_action( 'export_wp', array( $this, 'handle_export_wp' ) );
		add_action( 'rss2_head', array( $this, 'handle_rss2_head' ), 999 );
		add_action( 'wp_import_insert_post', array( $this, 'handle_wp_import_insert_post' ), 10, 2 );
	}

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param CustomOrdersTableController $cot_controller      Custom orders table controller.
	 * @param LegacyDataHandler           $legacy_data_handler Legacy data handler.
	 */
	final public function init( CustomOrdersTableController $cot_controller, LegacyDataHandler $legacy_data_handler ): void {
		$this->cot_controller      = $cot_controller;
		$this->legacy_data_handler = $legacy_data_handler;
	}

	/**
	 * Wraps given string in XML CDATA tag.
	 *
	 * @param string $str String to wrap in XML CDATA tag.
	 * @return string
	 */
	private function wxr_cdata( string $str ): string {
		if ( ! seems_utf8( $str ) ) {
			$str = (string) mb_convert_encoding( $str, 'UTF-8', 'auto' );
		}

		return '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';
	}

	/**
	 * Capture HPOS orders as XML output to be injected later.
	 *
	 * @internal
	 *
	 * @param array $args Export arguments.
	 */
	public function handle_export_wp( $args ): void {
		if ( ! is_array( $args ) || 'shop_order' !== ( $args['content'] ?? '' ) ) {
			return;
		}

		if ( ! $this->cot_controller->custom_orders_table_usage_is_enabled() ) {
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

		foreach ( is_array( $orders ) ? $orders : array() as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order instanceof \WC_Order ) {
				$this->export_order_to_xml( $order );
			}
		}

		$this->buffered_items = (string) ob_get_clean();
	}

	/**
	 * Outputs buffered XML into the WXR document.
	 *
	 * @internal
	 */
	public function handle_rss2_head(): void {
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
	private function export_order_to_xml( \WC_Order $order ): void {
		$order_id       = $order->get_id();
		$date_created   = $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '';
		$date_modified  = $order->get_date_modified() ? $order->get_date_modified()->format( 'Y-m-d H:i:s' ) : '';
		$post_author_id = $order->get_customer_id();

		$title = sprintf( 'Order – %s', $date_created );

		$meta = $order->get_meta_data();

		?>
		<item>
			<title><?php echo esc_html( $title ); ?></title>
			<link><?php echo esc_url( get_site_url( null, "/?post_type=shop_order&p={$order_id}" ) ); ?></link>
			<pubDate><?php echo esc_html( (string) mysql2date( 'D, d M Y H:i:s +0000', $date_created, false ) ); ?></pubDate>
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

			<?php
			// Add essential meta keys.
			$essential_meta = array(
				'_order_key'      => $order->get_order_key(),
				'_order_currency' => $order->get_currency(),
				'_order_total'    => (string) $order->get_total(),
				'_billing_email'  => $order->get_billing_email(),
				'_payment_method' => $order->get_payment_method(),
				'_order_version'  => \WC()->version,
			);

			foreach ( $essential_meta as $key => $value ) {
				?>
				<wp:postmeta>
					<wp:meta_key><?php echo $this->wxr_cdata( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_key>
					<wp:meta_value><?php echo $this->wxr_cdata( (string) maybe_serialize( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_value>
				</wp:postmeta>
				<?php
			}

			// Export all other custom meta.
			foreach ( $meta as $meta_item ) {
				$key   = $meta_item->get_data()['key'];
				$value = $meta_item->get_data()['value'];
				?>
				<wp:postmeta>
					<wp:meta_key><?php echo $this->wxr_cdata( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_key>
					<wp:meta_value><?php echo $this->wxr_cdata( (string) maybe_serialize( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_value>
				</wp:postmeta>
				<?php
			}
			?>
		</item>
		<?php
	}

	/**
	 * Checks if an imported order has enough postmeta to be backfilled to HPOS.
	 *
	 * @param int $post_id The post ID of the imported order.
	 * @return bool Whether the postmeta is complete enough for backfilling.
	 */
	private function is_valid_order_for_backfill( int $post_id ): bool {
		$required_keys = array(
			'_order_key',
			'_order_currency',
			'_order_total',
			'_billing_email',
			'_payment_method',
			'_order_version',
		);

		foreach ( $required_keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( false === $value || '' === $value ) {
				error_log( "[HPOS Backfill] Skipping order $post_id: missing meta $key" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return false;
			}
		}

		return true;
	}

	/**
	 * When an order is imported via Tools > Import, backfill it into the HPOS table.
	 *
	 * @internal
	 *
	 * @param int $post_id          The imported post ID.
	 * @param int $original_post_id The post ID in the source site.
	 */
	public function handle_wp_import_insert_post( $post_id, $original_post_id ): void {
		unset( $original_post_id );

		if ( get_post_type( $post_id ) === 'shop_order' ) {
			if ( ! $this->is_valid_order_for_backfill( (int) $post_id ) ) {
				error_log( "HPOS backfill skipped: missing required meta for order $post_id." ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return;
			}

			try {
				$this->legacy_data_handler->backfill_order_to_datastore( (int) $post_id, 'posts', 'hpos' );
			} catch ( \Exception $e ) {
				error_log( 'HPOS import backfill failed for order ' . $post_id . ': ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
}
