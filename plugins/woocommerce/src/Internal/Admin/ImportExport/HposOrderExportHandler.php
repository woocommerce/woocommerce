<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
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
	 * Cost of goods sold controller, used to know whether the COGS meta is in use.
	 *
	 * @var CostOfGoodsSoldController
	 */
	private CostOfGoodsSoldController $cogs_controller;

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
	 * @param CostOfGoodsSoldController   $cogs_controller     Cost of goods sold controller.
	 */
	final public function init( CustomOrdersTableController $cot_controller, DataSynchronizer $data_synchronizer, LegacyDataHandler $legacy_data_handler, CostOfGoodsSoldController $cogs_controller ): void {
		$this->cot_controller      = $cot_controller;
		$this->data_synchronizer   = $data_synchronizer;
		$this->legacy_data_handler = $legacy_data_handler;
		$this->cogs_controller     = $cogs_controller;
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
		$types    = 'all' === $args['content'] ? array( 'shop_order', 'shop_order_refund' ) : array( 'shop_order' );
		$page     = 1;

		do {
			$order_ids = wc_get_orders(
				array(
					'type'    => $types,
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
				if ( $order instanceof \WC_Abstract_Order ) {
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
	 * Outputs one order or refund as a WXR item, shaped like the post the CPT data store would create.
	 *
	 * @param \WC_Abstract_Order $order The order or refund.
	 */
	private function export_order_to_xml( \WC_Abstract_Order $order ): void {
		$order_id     = $order->get_id();
		$is_refund    = $order instanceof \WC_Order_Refund;
		$date_created = $order->get_date_created( 'edit' );
		$date_updated = $order->get_date_modified( 'edit' ) ?? $date_created;
		$created      = $date_created ? $date_created->date( 'Y-m-d H:i:s' ) : '';
		$created_gmt  = $date_created ? gmdate( 'Y-m-d H:i:s', $date_created->getTimestamp() ) : '';
		$updated      = $date_updated ? $date_updated->date( 'Y-m-d H:i:s' ) : '';
		$updated_gmt  = $date_updated ? gmdate( 'Y-m-d H:i:s', $date_updated->getTimestamp() ) : '';
		$link         = get_site_url( null, "?post_type={$order->get_type()}&p={$order_id}" );

		if ( $is_refund ) {
			/* translators: %s: refund ID */
			$title    = sprintf( __( 'Refund #%s', 'woocommerce' ), $order_id );
			$excerpt  = $order->get_reason( 'edit' );
			$password = '';
		} else {
			/* translators: %s: order ID */
			$title    = sprintf( __( 'Order #%s', 'woocommerce' ), $order_id );
			$excerpt  = $order instanceof \WC_Order ? $order->get_customer_note( 'edit' ) : '';
			$password = $order instanceof \WC_Order ? $order->get_order_key( 'edit' ) : '';
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- CDATA sections are escaped by cdata().
		?>
		<item>
			<title><?php echo self::cdata( $title ); ?></title>
			<link><?php echo esc_url( $link ); ?></link>
			<pubDate><?php echo esc_html( (string) mysql2date( 'D, d M Y H:i:s +0000', $created_gmt, false ) ); ?></pubDate>
			<dc:creator><?php echo self::cdata( '' ); ?></dc:creator>
			<guid isPermaLink="false"><?php echo esc_url( $link ); ?></guid>
			<description></description>
			<content:encoded><?php echo self::cdata( '' ); ?></content:encoded>
			<excerpt:encoded><?php echo self::cdata( $excerpt ); ?></excerpt:encoded>
			<wp:post_id><?php echo (int) $order_id; ?></wp:post_id>
			<wp:post_date><?php echo self::cdata( $created ); ?></wp:post_date>
			<wp:post_date_gmt><?php echo self::cdata( $created_gmt ); ?></wp:post_date_gmt>
			<wp:post_modified><?php echo self::cdata( $updated ); ?></wp:post_modified>
			<wp:post_modified_gmt><?php echo self::cdata( $updated_gmt ); ?></wp:post_modified_gmt>
			<wp:comment_status><?php echo self::cdata( 'closed' ); ?></wp:comment_status>
			<wp:ping_status><?php echo self::cdata( 'closed' ); ?></wp:ping_status>
			<wp:post_name><?php echo self::cdata( sanitize_title( $title ) ); ?></wp:post_name>
			<wp:status><?php echo self::cdata( self::get_post_status( $order ) ); ?></wp:status>
			<wp:post_parent><?php echo (int) $order->get_parent_id( 'edit' ); ?></wp:post_parent>
			<wp:menu_order>0</wp:menu_order>
			<wp:post_type><?php echo self::cdata( $order->get_type() ); ?></wp:post_type>
			<wp:post_password><?php echo self::cdata( $password ); ?></wp:post_password>
			<wp:is_sticky>0</wp:is_sticky>
		<?php
		foreach ( $this->get_postmeta( $order ) as $meta_key => $meta_value ) {
			// Same shape as the postmeta row core passes to the filter.
			$meta_row = (object) array(
				'post_id'    => $order_id,
				'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Not a query.
				'meta_value' => $meta_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Not a query.
			);

			/** This filter is documented in wp-admin/includes/export.php */
			if ( apply_filters( 'wxr_export_skip_postmeta', false, $meta_key, $meta_row ) ) { // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Core filter.
				continue;
			}
			?>
			<wp:postmeta>
				<wp:meta_key><?php echo self::cdata( $meta_key ); ?></wp:meta_key>
				<wp:meta_value><?php echo self::cdata( $meta_value ); ?></wp:meta_value>
			</wp:postmeta>
			<?php
		}
		?>
		</item>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Returns the post status the CPT data store would use for the order.
	 *
	 * @param \WC_Abstract_Order $order The order or refund.
	 * @return string
	 */
	private static function get_post_status( \WC_Abstract_Order $order ): string {
		$status = $order->get_status( 'edit' );

		if ( in_array( $status, array( OrderStatus::AUTO_DRAFT, OrderStatus::DRAFT, OrderStatus::TRASH ), true ) ) {
			return $status;
		}

		return in_array( 'wc-' . $status, get_post_stati(), true ) ? 'wc-' . $status : $status;
	}

	/**
	 * Returns the postmeta rows for the order as the CPT data store would write them, followed by custom meta.
	 *
	 * Mirrors `WC_Order_Data_Store_CPT::update_post_meta()` and its parent, so the WordPress importer
	 * can recreate a post that the posts data store, or the posts-to-HPOS migrator, reads back correctly.
	 *
	 * @param \WC_Abstract_Order $order The order or refund.
	 * @return array<string, string> Meta key => meta value.
	 */
	private function get_postmeta( \WC_Abstract_Order $order ): array {
		$meta = array(
			'_order_currency'     => $order->get_currency( 'edit' ),
			'_cart_discount'      => $order->get_discount_total( 'edit' ),
			'_cart_discount_tax'  => $order->get_discount_tax( 'edit' ),
			'_order_shipping'     => $order->get_shipping_total( 'edit' ),
			'_order_shipping_tax' => $order->get_shipping_tax( 'edit' ),
			'_order_tax'          => $order->get_cart_tax( 'edit' ),
			'_order_total'        => $order->get_total( 'edit' ),
			'_order_version'      => $order->get_version( 'edit' ),
			'_prices_include_tax' => $order->get_prices_include_tax( 'edit' ),
		);

		if ( $order instanceof \WC_Order_Refund ) {
			$meta += array(
				'_refund_amount'    => $order->get_amount( 'edit' ),
				'_refunded_by'      => $order->get_refunded_by( 'edit' ),
				'_refunded_payment' => $order->get_refunded_payment( 'edit' ),
				'_refund_reason'    => $order->get_reason( 'edit' ),
			);
		} elseif ( $order instanceof \WC_Order ) {
			$date_paid      = $order->get_date_paid( 'edit' );
			$date_completed = $order->get_date_completed( 'edit' );

			$meta += array(
				'_order_key'                    => $order->get_order_key( 'edit' ),
				'_customer_user'                => $order->get_customer_id( 'edit' ),
				'_payment_method'               => $order->get_payment_method( 'edit' ),
				'_payment_method_title'         => $order->get_payment_method_title( 'edit' ),
				'_transaction_id'               => $order->get_transaction_id( 'edit' ),
				'_customer_ip_address'          => $order->get_customer_ip_address( 'edit' ),
				'_customer_user_agent'          => $order->get_customer_user_agent( 'edit' ),
				'_created_via'                  => $order->get_created_via( 'edit' ),
				'_cart_hash'                    => $order->get_cart_hash( 'edit' ),
				'_date_completed'               => $date_completed ? $date_completed->getTimestamp() : '',
				'_date_paid'                    => $date_paid ? $date_paid->getTimestamp() : '',
				'_completed_date'               => $date_completed ? $date_completed->date( 'Y-m-d H:i:s' ) : '',
				'_paid_date'                    => $date_paid ? $date_paid->date( 'Y-m-d H:i:s' ) : '',
				'_download_permissions_granted' => $order->get_download_permissions_granted( 'edit' ),
				'_recorded_sales'               => $order->get_recorded_sales( 'edit' ),
				'_recorded_coupon_usage_counts' => $order->get_recorded_coupon_usage_counts( 'edit' ),
				'_order_stock_reduced'          => $order->get_order_stock_reduced( 'edit' ),
				'_new_order_email_sent'         => $order->get_new_order_email_sent( 'edit' ) ? 'true' : 'false',
			);

			foreach ( array( 'billing', 'shipping' ) as $address_type ) {
				foreach ( $order->get_address( $address_type ) as $field => $value ) {
					$meta[ "_{$address_type}_{$field}" ] = $value;
				}
				$meta[ "_{$address_type}_address_index" ] = implode( ' ', $order->get_address( $address_type ) );
			}

			if ( $this->cogs_controller->feature_is_enabled() ) {
				$meta['_cogs_total_value'] = $order->get_cogs_total_value();
			}
		}

		$meta = array_map( array( self::class, 'meta_value_to_string' ), $meta );

		foreach ( $order->get_meta_data() as $meta_item ) {
			$data = $meta_item->get_data();
			if ( ! isset( $data['key'] ) || array_key_exists( $data['key'], $meta ) ) {
				continue;
			}
			$meta[ $data['key'] ] = self::meta_value_to_string( $data['value'] ?? '' );
		}

		return $meta;
	}

	/**
	 * Converts a meta value to the string form the postmeta table would hold.
	 *
	 * @param mixed $value Meta value.
	 * @return string
	 */
	private static function meta_value_to_string( $value ): string {
		if ( is_bool( $value ) ) {
			return wc_bool_to_string( $value );
		}
		if ( is_array( $value ) || is_object( $value ) ) {
			return (string) maybe_serialize( $value );
		}

		return (string) $value;
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
