<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
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
	 * Number of orders loaded per query while streaming the export.
	 */
	private const BATCH_SIZE = 20;

	/**
	 * Custom orders table controller.
	 *
	 * @var CustomOrdersTableController
	 */
	private CustomOrdersTableController $cot_controller;

	/**
	 * Data synchronizer, used to detect whether posts already mirror HPOS.
	 *
	 * @var DataSynchronizer
	 */
	private DataSynchronizer $data_synchronizer;

	/**
	 * Legacy data handler used to backfill imported orders into HPOS.
	 *
	 * @var LegacyDataHandler
	 */
	private LegacyDataHandler $legacy_data_handler;

	/**
	 * Arguments of the export currently in progress, or null outside of an export.
	 *
	 * @var array|null
	 */
	private ?array $export_args = null;

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
	 * @param DataSynchronizer            $data_synchronizer   Data synchronizer.
	 * @param LegacyDataHandler           $legacy_data_handler Legacy data handler.
	 */
	final public function init( CustomOrdersTableController $cot_controller, DataSynchronizer $data_synchronizer, LegacyDataHandler $legacy_data_handler ): void {
		$this->cot_controller      = $cot_controller;
		$this->data_synchronizer   = $data_synchronizer;
		$this->legacy_data_handler = $legacy_data_handler;
	}

	/**
	 * Remembers the export arguments so the orders can be streamed from `rss2_head`.
	 *
	 * @internal
	 *
	 * @param array $args Export arguments.
	 */
	public function handle_export_wp( $args ): void {
		$this->export_args = is_array( $args ) ? $args : null;
	}

	/**
	 * Streams HPOS orders into the WXR document.
	 *
	 * `rss2_head` is the only hook core fires inside the document, so this runs during
	 * regular RSS feeds too. It only emits when `export_wp` set the arguments in this request.
	 *
	 * @internal
	 */
	public function handle_rss2_head(): void {
		$args              = $this->export_args;
		$this->export_args = null;

		if ( null === $args || ! $this->should_export_orders( $args ) ) {
			return;
		}

		$statuses = array_merge( array_keys( wc_get_order_statuses() ), array( OrderStatus::TRASH ) );
		$page     = 1;

		do {
			$order_ids = wc_get_orders(
				array(
					'type'    => 'shop_order',
					'status'  => $statuses,
					'limit'   => self::BATCH_SIZE,
					'page'    => $page,
					'orderby' => 'id',
					'order'   => 'ASC',
					'return'  => 'ids',
				)
			);
			$order_ids = is_array( $order_ids ) ? $order_ids : array();
			$fetched   = count( $order_ids );

			foreach ( $order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order instanceof \WC_Order ) {
					$this->export_order_to_xml( $order );
				}
			}

			++$page;
		} while ( self::BATCH_SIZE === $fetched );
	}

	/**
	 * Whether HPOS orders must be added to the export.
	 *
	 * Core already exports orders from the posts table, which is complete when posts are
	 * authoritative or when sync is on. Only HPOS-authoritative sites with sync off miss them.
	 *
	 * @param array $args Export arguments.
	 * @return bool
	 */
	private function should_export_orders( array $args ): bool {
		if ( ! in_array( $args['content'] ?? '', array( 'all', 'shop_order' ), true ) ) {
			return false;
		}

		return $this->cot_controller->custom_orders_table_usage_is_enabled()
			&& ! $this->data_synchronizer->data_sync_is_enabled();
	}

	/**
	 * Wraps a string in an XML CDATA section, mirroring core's `wxr_cdata()`.
	 *
	 * @param string $str String to wrap.
	 * @return string
	 */
	private static function cdata( string $str ): string {
		$is_valid_utf8 = function_exists( 'wp_is_valid_utf8' )
			? wp_is_valid_utf8( $str )
			: seems_utf8( $str ); // phpcs:ignore WordPress.WP.DeprecatedFunctions.seems_utf8Found -- Fallback for WordPress < 6.9.

		if ( ! $is_valid_utf8 ) {
			$str = (string) mb_convert_encoding( $str, 'UTF-8', 'auto' );
		}

		return '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';
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
			<content:encoded><?php echo self::cdata( '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></content:encoded>
			<excerpt:encoded><?php echo self::cdata( '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></excerpt:encoded>
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
					<wp:meta_key><?php echo self::cdata( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_key>
					<wp:meta_value><?php echo self::cdata( (string) maybe_serialize( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_value>
				</wp:postmeta>
				<?php
			}

			// Export all other custom meta.
			foreach ( $meta as $meta_item ) {
				$key   = $meta_item->get_data()['key'];
				$value = $meta_item->get_data()['value'];
				?>
				<wp:postmeta>
					<wp:meta_key><?php echo self::cdata( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_key>
					<wp:meta_value><?php echo self::cdata( (string) maybe_serialize( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></wp:meta_value>
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
