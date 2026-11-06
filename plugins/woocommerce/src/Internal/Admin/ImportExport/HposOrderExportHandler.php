<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStoreMeta;
use WC_Order;
use WC_Order_Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Exports orders from HPOS through WordPress Tools > Export.
 *
 * @since 11.4.0
 */
class HposOrderExportHandler {

	/**
	 * Number of orders loaded at a time.
	 */
	private const BATCH_SIZE = 20;

	/**
	 * The HPOS controller.
	 *
	 * @var CustomOrdersTableController
	 */
	private CustomOrdersTableController $controller;

	/**
	 * The order data synchronizer.
	 *
	 * @var DataSynchronizer
	 */
	private DataSynchronizer $synchronizer;

	/**
	 * The HPOS meta data store.
	 *
	 * @var OrdersTableDataStoreMeta
	 */
	private OrdersTableDataStoreMeta $meta_store;

	/**
	 * Register the export hook.
	 */
	public function __construct() {
		add_action( 'export_wp', array( $this, 'handle_export_wp' ), PHP_INT_MAX );
	}

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param CustomOrdersTableController $controller The HPOS controller.
	 * @param DataSynchronizer            $synchronizer The order data synchronizer.
	 * @param OrdersTableDataStoreMeta    $meta_store The HPOS meta data store.
	 */
	final public function init( CustomOrdersTableController $controller, DataSynchronizer $synchronizer, OrdersTableDataStoreMeta $meta_store ): void {
		$this->controller   = $controller;
		$this->synchronizer = $synchronizer;
		$this->meta_store   = $meta_store;
	}

	/**
	 * Serve the Orders download from HPOS with compatibility mode off.
	 *
	 * @internal
	 *
	 * @param mixed $args WordPress export arguments.
	 */
	public function handle_export_wp( $args ): void {
		if ( ! is_array( $args ) || 'shop_order' !== ( $args['content'] ?? '' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'export' !== $screen->id || ! current_user_can( 'export' ) ) {
			return;
		}

		$post_type = get_post_type_object( 'shop_order' );
		if ( ! $post_type || ! $post_type->can_export || ! $this->controller->custom_orders_table_usage_is_enabled() || $this->synchronizer->data_sync_is_enabled() ) {
			return;
		}

		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		$sitename = '' === $sitename ? '' : $sitename . '.';
		$date     = gmdate( 'Y-m-d' );
		$filename = $sitename . 'WordPress.' . $date . '.xml';

		/** This filter is documented in wp-admin/includes/export.php. */
		$filtered_filename = apply_filters( 'export_wp_filename', $filename, $sitename, $date ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- WordPress core filter.
		$filename          = is_string( $filtered_filename ) && '' !== $filtered_filename ? $filtered_filename : $filename;

		nocache_headers();
		header( 'Content-Type: text/xml; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

		// Core exports post rows after rss2_head, including mirrors that can be stale or deleted in HPOS.
		$this->stream_export();
		exit;
	}

	/**
	 * Stream a WXR document containing the current HPOS orders.
	 */
	private function stream_export(): void {
		global $wpdb;

		echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
		?>
<rss version="2.0"
	xmlns:excerpt="https://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="https://wordpress.org/export/1.2/">
<channel>
		<?php
		self::write_fields(
			array(
				'title'            => get_bloginfo( 'name' ),
				'link'             => home_url(),
				'description'      => get_bloginfo( 'description' ),
				'wp:wxr_version'   => '1.2',
				'wp:base_site_url' => is_multisite() ? network_home_url() : home_url(),
				'wp:base_blog_url' => home_url(),
			)
		);

		$table   = OrdersTableDataStore::get_orders_table_name();
		$last_id = 0;

		// Bound the scan at the newest ID when the export starts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- The table name comes from the order data store.
		$max_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM $table" );
		while ( $last_id < $max_id ) {
			// Select the stored status so unregistered statuses also survive export.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Stream IDs and statuses in primary key order.
			$batch = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, status FROM $table WHERE id > %d AND id <= %d AND type = %s AND status <> %s ORDER BY id LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name comes from the order data store.
					$last_id,
					$max_id,
					'shop_order',
					OrderStatus::AUTO_DRAFT,
					self::BATCH_SIZE
				),
				OBJECT_K
			);
			if ( ! $batch ) {
				break;
			}

			$order_ids = array_map( 'intval', array_keys( $batch ) );
			$last_id   = (int) array_key_last( $batch );

			try {
				$orders = WC_Order_Factory::get_orders( $order_ids );
			} catch ( \Exception $exception ) {
				// An order may have been deleted after its ID was selected.
				$orders = array_map( 'wc_get_order', $order_ids );
			}

			$order_meta                = $this->meta_store->get_meta_data_for_object_ids( $order_ids );
			list( $notes, $note_meta ) = self::get_notes( $order_ids );
			foreach ( $orders as $order ) {
				if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() ) {
					continue;
				}
				$id = $order->get_id();
				$this->export_order_to_xml( $order, $batch[ $id ]->status, $order_meta[ $id ] ?? array(), $notes[ $id ] ?? array(), $note_meta );
			}

			if ( wp_cache_supports( 'flush_runtime' ) ) {
				wp_cache_flush_runtime();
			}
		}

		echo "</channel>\n</rss>\n";
	}

	/**
	 * Read comments and their raw meta in batches, as the WordPress exporter does for each post.
	 *
	 * @param int[] $order_ids The orders in the batch.
	 * @return array{array<int, \stdClass[]>, array<int, \stdClass[]>}
	 */
	private static function get_notes( array $order_ids ): array {
		global $wpdb;

		$id_placeholders = implode( ', ', array_fill( 0, count( $order_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The IN lists contain one placeholder per ID. Table names come from wpdb.
		$notes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->comments WHERE comment_post_ID IN ($id_placeholders) AND comment_approved <> %s ORDER BY comment_ID",
				array_merge( $order_ids, array( 'spam' ) )
			)
		);
		if ( ! $notes ) {
			return array( array(), array() );
		}

		$notes_by_order = array();
		foreach ( $notes as $note ) {
			$notes_by_order[ (int) $note->comment_post_ID ][] = $note;
		}

		$comment_ids     = array_column( $notes, 'comment_ID' );
		$id_placeholders = implode( ', ', array_fill( 0, count( $comment_ids ), '%d' ) );
		$meta_rows       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->commentmeta WHERE comment_id IN ($id_placeholders) ORDER BY meta_id", $comment_ids ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$meta_by_comment = array();
		foreach ( $meta_rows as $meta ) {
			$meta_by_comment[ (int) $meta->comment_id ][] = $meta;
		}

		return array( $notes_by_order, $meta_by_comment );
	}

	/**
	 * Write one order as a WXR item.
	 *
	 * @param WC_Order                $order The order.
	 * @param string                  $status The stored status.
	 * @param \stdClass[]             $meta_rows Raw HPOS metadata.
	 * @param \stdClass[]             $notes Comments on this order.
	 * @param array<int, \stdClass[]> $note_meta Raw metadata by comment ID.
	 */
	private function export_order_to_xml( WC_Order $order, string $status, array $meta_rows, array $notes, array $note_meta ): void {
		$order_id     = $order->get_id();
		$date_created = $order->get_date_created( 'edit' );
		$date_updated = $order->get_date_modified( 'edit' ) ?? $date_created;
		$link         = add_query_arg(
			array(
				'p'         => $order_id,
				'post_type' => 'shop_order',
			),
			home_url( '/' )
		);
		$title        = 'Order #' . $order_id;

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Export filters are documented in wp-admin/includes/export.php.
		echo "<item>\n";
		self::write_fields(
			array(
				'title'                => apply_filters( 'the_title_export', $title ),
				'link'                 => $link,
				'pubDate'              => $date_created ? gmdate( 'D, d M Y H:i:s +0000', $date_created->getTimestamp() ) : '',
				'dc:creator'           => '',
				'guid'                 => $link,
				'description'          => '',
				'content:encoded'      => apply_filters( 'the_content_export', '' ),
				'excerpt:encoded'      => apply_filters( 'the_excerpt_export', $order->get_customer_note( 'edit' ) ),
				'wp:post_id'           => $order_id,
				'wp:post_date'         => $date_created ? $date_created->date( 'Y-m-d H:i:s' ) : '',
				'wp:post_date_gmt'     => $date_created ? gmdate( 'Y-m-d H:i:s', $date_created->getTimestamp() ) : '',
				'wp:post_modified'     => $date_updated ? $date_updated->date( 'Y-m-d H:i:s' ) : '',
				'wp:post_modified_gmt' => $date_updated ? gmdate( 'Y-m-d H:i:s', $date_updated->getTimestamp() ) : '',
				'wp:comment_status'    => 'closed',
				'wp:ping_status'       => 'closed',
				'wp:post_name'         => 'order-' . $order_id,
				'wp:status'            => $status,
				'wp:post_parent'       => $order->get_parent_id( 'edit' ),
				'wp:menu_order'        => 0,
				'wp:post_type'         => 'shop_order',
				'wp:post_password'     => '',
				'wp:is_sticky'         => 0,
			)
		);
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment

		$core_meta = self::get_core_meta( $order );
		foreach ( $core_meta as $key => $value ) {
			self::write_post_meta( $order_id, $key, (string) maybe_serialize( $value ) );
		}

		// Source-site customer and payment token IDs can refer to unrelated accounts on import.
		$excluded_keys = array( '_customer_user', '_payment_tokens', '_edit_lock', '_billing_address_index', '_shipping_address_index', DataSynchronizer::DELETED_RECORD_META_KEY );
		foreach ( $meta_rows as $meta ) {
			$key = $meta->meta_key ?? null;
			if ( is_string( $key ) && '' !== $key && ! array_key_exists( $key, $core_meta ) && ! in_array( $key, $excluded_keys, true ) ) {
				self::write_post_meta( $order_id, $key, (string) $meta->meta_value );
			}
		}

		foreach ( $notes as $note ) {
			echo "<wp:comment>\n";
			self::write_fields(
				array(
					'wp:comment_id'           => $note->comment_ID,
					'wp:comment_author'       => $note->comment_author,
					'wp:comment_author_email' => $note->comment_author_email,
					'wp:comment_author_url'   => sanitize_url( $note->comment_author_url ),
					'wp:comment_author_IP'    => $note->comment_author_IP,
					'wp:comment_date'         => $note->comment_date,
					'wp:comment_date_gmt'     => $note->comment_date_gmt,
					'wp:comment_content'      => $note->comment_content,
					'wp:comment_approved'     => $note->comment_approved,
					'wp:comment_type'         => $note->comment_type,
					'wp:comment_parent'       => $note->comment_parent,
					'wp:comment_user_id'      => $note->user_id,
				)
			);
			foreach ( $note_meta[ (int) $note->comment_ID ] ?? array() as $meta ) {
				/** This filter is documented in wp-admin/includes/export.php. */
				if ( ! apply_filters( 'wxr_export_skip_commentmeta', false, $meta->meta_key, $meta ) ) { // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- WordPress core filter.
					self::write_meta( 'commentmeta', $meta->meta_key, $meta->meta_value );
				}
			}
			echo "</wp:comment>\n";
		}
		echo "</item>\n";
	}

	/**
	 * Project HPOS order properties onto the keys used by legacy order posts.
	 *
	 * @param WC_Order $order The order.
	 * @return array
	 */
	private static function get_core_meta( WC_Order $order ): array {
		$data = $order->get_base_data();
		$meta = array(
			'_order_currency'       => $data['currency'],
			'_order_version'        => $data['version'],
			'_order_total'          => $data['total'],
			'_order_tax'            => $data['cart_tax'],
			'_order_shipping'       => $data['shipping_total'],
			'_order_shipping_tax'   => $data['shipping_tax'],
			'_cart_discount'        => $data['discount_total'],
			'_cart_discount_tax'    => $data['discount_tax'],
			'_date_paid'            => $data['date_paid'] ? $data['date_paid']->getTimestamp() : '',
			'_date_completed'       => $data['date_completed'] ? $data['date_completed']->getTimestamp() : '',
			'_new_order_email_sent' => $order->get_new_order_email_sent( 'edit' ) ? 'true' : 'false',
		);

		foreach ( array( 'order_key', 'payment_method', 'payment_method_title', 'transaction_id', 'customer_ip_address', 'customer_user_agent', 'created_via', 'cart_hash' ) as $field ) {
			$meta[ '_' . $field ] = $data[ $field ];
		}
		foreach ( array( 'prices_include_tax', 'download_permissions_granted', 'recorded_sales', 'recorded_coupon_usage_counts', 'order_stock_reduced' ) as $field ) {
			$meta[ '_' . $field ] = wc_bool_to_string( wc_string_to_bool( $data[ $field ] ) );
		}
		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			foreach ( $data[ $address_type ] as $key => $value ) {
				$meta[ '_' . $address_type . '_' . $key ] = $value;
			}
		}

		return $meta;
	}

	/**
	 * Apply the core post-meta export filter before writing a row.
	 *
	 * @param int    $order_id The order ID.
	 * @param string $key The meta key.
	 * @param string $value The stored meta value.
	 */
	private static function write_post_meta( int $order_id, string $key, string $value ): void {
		$meta = (object) array(
			'meta_id'    => 0,
			'post_id'    => $order_id,
			'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Shape expected by the WordPress export filter.
			'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Shape expected by the WordPress export filter.
		);

		/** This filter is documented in wp-admin/includes/export.php. */
		if ( ! apply_filters( 'wxr_export_skip_postmeta', false, $key, $meta ) ) { // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- WordPress core filter.
			self::write_meta( 'postmeta', $meta->meta_key, $meta->meta_value );
		}
	}

	/**
	 * Write a post or comment meta element.
	 *
	 * @param string $element The element name.
	 * @param mixed  $key The meta key.
	 * @param mixed  $value The meta value.
	 */
	private static function write_meta( string $element, $key, $value ): void {
		echo '<wp:' . esc_attr( $element ) . ">\n";
		self::write_fields(
			array(
				'wp:meta_key'   => $key,
				'wp:meta_value' => $value,
			)
		);
		echo '</wp:' . esc_attr( $element ) . ">\n";
	}

	/**
	 * Write XML text elements, treating even CDATA delimiters in data as literal text.
	 *
	 * @param array $fields Element names and values.
	 */
	private static function write_fields( array $fields ): void {
		foreach ( $fields as $tag => $value ) {
			$text = is_scalar( $value ) ? (string) $value : '';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- htmlspecialchars escapes XML text, including CDATA delimiters and invalid code points.
			printf( "<%1\$s>%2\$s</%1\$s>\n", esc_attr( $tag ), htmlspecialchars( $text, ENT_XML1 | ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED, 'UTF-8' ) );
		}
	}
}
