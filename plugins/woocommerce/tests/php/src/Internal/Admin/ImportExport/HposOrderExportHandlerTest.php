<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\ImportExport\HposOrderExportHandler;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the HposOrderExportHandler class.
 */
class HposOrderExportHandlerTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * The System Under Test.
	 *
	 * @var HposOrderExportHandler
	 */
	private $sut;

	/**
	 * Enables HPOS as authoritative with sync off.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();
		$this->disable_cot_sync();

		$this->sut = wc_get_container()->get( HposOrderExportHandler::class );
	}

	/**
	 * Restores the default order storage.
	 */
	public function tearDown(): void {
		try {
			$this->clean_up_cot_setup();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Runs the export hooks the way core does and returns the emitted XML.
	 *
	 * @param string $content The `content` export argument.
	 * @return string
	 */
	private function export( string $content ): string {
		$this->sut->handle_export_wp( array( 'content' => $content ) );

		ob_start();
		$this->sut->handle_rss2_head();

		return (string) ob_get_clean();
	}

	/**
	 * Creates and saves an order in the current data store.
	 *
	 * @param string $status Order status.
	 * @return WC_Order
	 */
	private function create_order( string $status = OrderStatus::PROCESSING ): WC_Order {
		$order = new WC_Order();
		$order->set_status( $status );
		$order->set_billing_first_name( 'Jane' );
		$order->set_total( '10.00' );
		$order->save();

		return $order;
	}

	/**
	 * Builds the `<wp:postmeta>` block the exporter emits for a key/value pair.
	 *
	 * @param string $key   Meta key.
	 * @param string $value Meta value.
	 * @return string
	 */
	private function postmeta_xml( string $key, string $value ): string {
		return "<wp:meta_key><![CDATA[{$key}]]></wp:meta_key>\n\t\t\t\t<wp:meta_value><![CDATA[{$value}]]></wp:meta_value>";
	}

	/**
	 * @testdox Should emit one item per HPOS order when exporting orders or all content.
	 * @testWith ["shop_order"]
	 *           ["all"]
	 *
	 * @param string $content The `content` export argument.
	 */
	public function test_exports_hpos_orders( string $content ): void {
		$first  = $this->create_order();
		$second = $this->create_order( OrderStatus::COMPLETED );

		$xml = $this->export( $content );

		$this->assertSame( 2, substr_count( $xml, '<item>' ), 'One item per order expected' );
		$this->assertStringContainsString( "<wp:post_id>{$first->get_id()}</wp:post_id>", $xml );
		$this->assertStringContainsString( "<wp:post_id>{$second->get_id()}</wp:post_id>", $xml );
	}

	/**
	 * @testdox Should emit the post fields the CPT data store would have written.
	 */
	public function test_exports_post_fields(): void {
		update_option( 'timezone_string', 'America/New_York' );

		$order = $this->create_order();
		$order->set_date_created( 1705312800 );
		$order->set_customer_note( 'Leave at the door' );
		$order->save();

		$xml = $this->export( 'shop_order' );

		$this->assertStringContainsString( "<title><![CDATA[Order #{$order->get_id()}]]></title>", $xml );
		$this->assertStringContainsString( '<wp:status><![CDATA[wc-processing]]></wp:status>', $xml );
		$this->assertStringContainsString( '<wp:post_type><![CDATA[shop_order]]></wp:post_type>', $xml );
		$this->assertStringContainsString( '<wp:post_date><![CDATA[2024-01-15 05:00:00]]></wp:post_date>', $xml, 'post_date is site-local time' );
		$this->assertStringContainsString( '<wp:post_date_gmt><![CDATA[2024-01-15 10:00:00]]></wp:post_date_gmt>', $xml, 'post_date_gmt is UTC' );
		$this->assertStringContainsString( '<excerpt:encoded><![CDATA[Leave at the door]]></excerpt:encoded>', $xml, 'Customer note lives in the excerpt' );
		$this->assertStringContainsString( "<wp:post_password><![CDATA[{$order->get_order_key()}]]></wp:post_password>", $xml, 'Order key lives in the password' );
		$this->assertStringContainsString( '<wp:post_parent>0</wp:post_parent>', $xml );
	}

	/**
	 * @testdox Should emit the internal order meta the posts data store reads back.
	 */
	public function test_exports_internal_meta(): void {
		$order = $this->create_order();
		$order->set_customer_id( 7 );
		$order->set_billing_last_name( 'Doe' );
		$order->set_payment_method( 'cod' );
		$order->set_date_paid( 1705312800 );
		$order->set_prices_include_tax( true );
		$order->save();

		$xml = $this->export( 'shop_order' );

		$this->assertStringContainsString( $this->postmeta_xml( '_customer_user', '7' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_billing_first_name', 'Jane' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_billing_last_name', 'Doe' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_payment_method', 'cod' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_order_total', '10.00' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_order_key', $order->get_order_key() ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_date_paid', '1705312800' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_prices_include_tax', 'yes' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_billing_address_index', implode( ' ', $order->get_address( 'billing' ) ) ), $xml );
	}

	/**
	 * @testdox Should emit custom meta, including repeated keys, and honor the core wxr_export_skip_postmeta filter.
	 */
	public function test_exports_custom_meta_and_honors_skip_filter(): void {
		$order = $this->create_order();
		$order->add_meta_data( '_tracking_number', 'ABC123' );
		$order->add_meta_data( '_tracking_number', 'DEF456' );
		$order->add_meta_data( 'gift_wrap', array( 'color' => 'red' ) );
		$order->add_meta_data( '_edit_lock', 'skip me' );
		$order->save();

		add_filter(
			'wxr_export_skip_postmeta',
			function ( $skip, $meta_key ) {
				return '_edit_lock' === $meta_key ? true : $skip;
			},
			10,
			2
		);

		$xml = $this->export( 'shop_order' );

		$this->assertStringContainsString( $this->postmeta_xml( '_tracking_number', 'ABC123' ), $xml );
		$this->assertStringContainsString( $this->postmeta_xml( '_tracking_number', 'DEF456' ), $xml, 'Repeated meta keys keep every row' );
		$this->assertStringContainsString( $this->postmeta_xml( 'gift_wrap', serialize( array( 'color' => 'red' ) ) ), $xml ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$this->assertStringNotContainsString( '_edit_lock', $xml, 'Meta skipped by the filter must not be exported' );
	}

	/**
	 * @testdox Should export order notes as comments with the customer note flag as comment meta.
	 */
	public function test_exports_order_notes_as_comments(): void {
		$order = $this->create_order();
		$order->add_order_note( 'Packed and ready' );
		$order->add_order_note( 'On its way', 1 );

		$xml = $this->export( 'shop_order' );

		$expected_notes = count( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) );
		$this->assertSame( $expected_notes, substr_count( $xml, '<wp:comment>' ), 'One comment per note, including the automatic status note' );
		$this->assertStringContainsString( '<wp:comment_type><![CDATA[order_note]]></wp:comment_type>', $xml );
		$this->assertStringContainsString( '<wp:comment_content><![CDATA[Packed and ready]]></wp:comment_content>', $xml );
		$this->assertStringContainsString( '<wp:comment_content><![CDATA[On its way]]></wp:comment_content>', $xml );
		$this->assertSame( 1, substr_count( $xml, '<wp:meta_key><![CDATA[is_customer_note]]></wp:meta_key>' ), 'Only the customer note carries the flag' );
	}

	/**
	 * Parses the exported items inside a WXR envelope, the way the WordPress importer would.
	 *
	 * @param string $xml Exported items.
	 * @return \DOMXPath
	 */
	private function parse_export( string $xml ): \DOMXPath {
		$document = new \DOMDocument();
		$loaded   = $document->loadXML(
			'<rss xmlns:wp="http://wordpress.org/export/1.2/" xmlns:dc="http://purl.org/dc/elements/1.1/"'
			. ' xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/">'
			. '<channel>' . $xml . '</channel></rss>'
		);
		$this->assertTrue( $loaded, 'Exported items must be well-formed XML' );

		$xpath = new \DOMXPath( $document );
		$xpath->registerNamespace( 'wp', 'http://wordpress.org/export/1.2/' );

		return $xpath;
	}

	/**
	 * @testdox Should emit notes the WordPress importer can recreate as order notes on the imported order.
	 */
	public function test_exported_notes_round_trip_through_comment_insert(): void {
		$order = $this->create_order();
		$order->add_order_note( 'On its way, tracking ]]> included', 1 );

		$xpath          = $this->parse_export( $this->export( 'shop_order' ) );
		$customer_notes = $xpath->query( '//wp:comment[wp:commentmeta/wp:meta_key = "is_customer_note"]' );
		$this->assertSame( 1, $customer_notes->length, 'Exactly one exported comment carries the customer note flag' );
		$exported_note   = $customer_notes->item( 0 );
		$comment_content = $xpath->evaluate( 'string(wp:comment_content)', $exported_note );
		$comment_type    = $xpath->evaluate( 'string(wp:comment_type)', $exported_note );

		// The importer inserts the comment against the new post ID and then writes the comment meta.
		$target     = $this->create_order();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $target->get_id(),
				'comment_content'  => $comment_content,
				'comment_type'     => $comment_type,
				'comment_approved' => 1,
			)
		);
		add_comment_meta( $comment_id, 'is_customer_note', '1' );

		$notes = wc_get_order_notes(
			array(
				'order_id' => $target->get_id(),
				'type'     => 'customer',
			)
		);

		$this->assertCount( 1, $notes );
		$this->assertSame( 'On its way, tracking ]]> included', $notes[0]->content );
		$this->assertTrue( $notes[0]->customer_note );
	}

	/**
	 * @testdox Should include trashed orders with the trash status like the core exporter does.
	 */
	public function test_exports_trashed_orders(): void {
		$order    = $this->create_order();
		$order_id = $order->get_id();
		$order->delete();

		$xml = $this->export( 'shop_order' );

		$this->assertStringContainsString( "<wp:post_id>{$order_id}</wp:post_id>", $xml );
		$this->assertStringContainsString( '<wp:status><![CDATA[trash]]></wp:status>', $xml );
	}

	/**
	 * @testdox Should export refunds with their parent only when exporting all content.
	 */
	public function test_exports_refunds_only_with_all_content(): void {
		$order  = $this->create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 4,
				'reason'   => 'Damaged',
			)
		);
		$this->assertNotWPError( $refund );

		$orders_only = $this->export( 'shop_order' );
		$all_content = $this->export( 'all' );

		$this->assertSame( 1, substr_count( $orders_only, '<item>' ), 'Orders export must not include refunds' );
		$this->assertSame( 2, substr_count( $all_content, '<item>' ), 'All content export includes the refund' );
		$this->assertStringContainsString( '<wp:post_type><![CDATA[shop_order_refund]]></wp:post_type>', $all_content );
		$this->assertStringContainsString( "<wp:post_parent>{$order->get_id()}</wp:post_parent>", $all_content );
		$this->assertStringContainsString( '<excerpt:encoded><![CDATA[Damaged]]></excerpt:encoded>', $all_content );
		$this->assertStringContainsString( $this->postmeta_xml( '_refund_amount', '4' ), $all_content );
	}

	/**
	 * @testdox Should export orders whose status is no longer registered, like the core exporter does.
	 */
	public function test_exports_orders_with_unregistered_status(): void {
		global $wpdb;

		$order = $this->create_order();
		$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'status' => 'wc-legacy-status' ), array( 'id' => $order->get_id() ) );
		wc_get_container()->get( OrdersTableDataStore::class )->clear_cached_data( array( $order->get_id() ) );
		wp_cache_flush();

		$xml = $this->export( 'shop_order' );

		$this->assertStringContainsString( "<wp:post_id>{$order->get_id()}</wp:post_id>", $xml );
		$this->assertStringContainsString( '<wp:status><![CDATA[legacy-status]]></wp:status>', $xml );
	}

	/**
	 * @testdox Should leave out auto-draft orders, like the core exporter does.
	 */
	public function test_ignores_auto_draft_orders(): void {
		$this->create_order( OrderStatus::AUTO_DRAFT );

		$this->assertSame( '', $this->export( 'shop_order' ) );
	}

	/**
	 * @testdox Should page through orders instead of loading them all at once.
	 */
	public function test_streams_orders_in_pages(): void {
		$ids = array();
		for ( $i = 0; $i < 21; $i++ ) {
			$ids[] = $this->create_order()->get_id();
		}

		$xml = $this->export( 'shop_order' );

		$this->assertSame( 21, substr_count( $xml, '<item>' ), 'All orders beyond the first page expected' );
		$this->assertStringContainsString( '<wp:post_id>' . end( $ids ) . '</wp:post_id>', $xml );
	}

	/**
	 * @testdox Should release each exported order's meta cache so a large export does not grow memory.
	 */
	public function test_releases_order_meta_cache_while_streaming(): void {
		$order     = $this->create_order();
		$cache_key = WC_Order::generate_meta_cache_key( $order->get_id(), 'orders' );

		$this->export( 'shop_order' );

		$this->assertFalse( wp_cache_get( $cache_key, 'orders' ), 'Meta cache entry must be gone after the export' );
	}

	/**
	 * @testdox Should honor can_export on the order post type like the core exporter does.
	 */
	public function test_honors_can_export_on_the_order_post_type(): void {
		$this->create_order();
		$post_type_object = get_post_type_object( 'shop_order' );

		$post_type_object->can_export = false;
		try {
			$xml = $this->export( 'shop_order' );
		} finally {
			$post_type_object->can_export = true;
		}

		$this->assertSame( '', $xml );
	}

	/**
	 * @testdox Should emit nothing for content types other than orders.
	 */
	public function test_ignores_other_content_types(): void {
		$this->create_order();

		$this->assertSame( '', $this->export( 'post' ) );
	}

	/**
	 * @testdox Should emit nothing when sync is on because core exports the mirrored posts.
	 */
	public function test_ignores_export_when_sync_is_enabled(): void {
		$this->create_order();
		$this->enable_cot_sync();

		$this->assertSame( '', $this->export( 'shop_order' ) );
	}

	/**
	 * @testdox Should emit nothing when the posts table is authoritative.
	 */
	public function test_ignores_export_when_hpos_is_not_authoritative(): void {
		$this->toggle_cot_feature_and_usage( false );
		$this->create_order();

		$this->assertSame( '', $this->export( 'shop_order' ) );
	}

	/**
	 * @testdox Should emit nothing on rss2_head outside of an export, such as a front-end feed.
	 */
	public function test_ignores_rss2_head_outside_export(): void {
		$this->create_order();

		ob_start();
		$this->sut->handle_rss2_head();

		$this->assertSame( '', (string) ob_get_clean() );
	}

	/**
	 * Inserts an order post with meta the way the WordPress importer does.
	 *
	 * @param string $post_type Post type.
	 * @return int Post ID.
	 */
	private function insert_imported_post( string $post_type = 'shop_order' ): int {
		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_status' => 'shop_order' === $post_type ? 'wc-processing' : 'publish',
				'post_title'  => 'Imported',
			)
		);

		foreach ( array(
			'_order_key'          => 'wc_order_imported',
			'_billing_first_name' => 'Jane',
			'_order_total'        => '10.00',
			'_order_currency'     => 'USD',
		) as $key => $value ) {
			add_post_meta( $post_id, $key, $value );
		}

		return $post_id;
	}

	/**
	 * Runs the importer hooks the way the WordPress importer fires them.
	 *
	 * @param int $post_id Post ID.
	 */
	private function simulate_import( int $post_id ): void {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'no' );

		$this->sut->handle_wp_import_insert_post( $post_id );
		$this->sut->handle_import_end();
		wp_cache_flush();
	}

	/**
	 * @testdox Should backfill imported order posts into HPOS once the import ends.
	 */
	public function test_backfills_imported_orders_into_hpos_at_import_end(): void {
		$post_id    = $this->insert_imported_post();
		$data_store = wc_get_container()->get( OrdersTableDataStore::class );

		$this->sut->handle_wp_import_insert_post( $post_id );
		$this->assertFalse( $data_store->order_exists( $post_id ), 'Nothing is migrated before the import ends' );

		$this->simulate_import( $post_id );
		$order = wc_get_order( $post_id );

		$this->assertTrue( $data_store->order_exists( $post_id ) );
		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( OrderStatus::PROCESSING, $order->get_status() );
		$this->assertSame( 'Jane', $order->get_billing_first_name() );
		$this->assertSame( '10.00', $order->get_total() );
	}

	/**
	 * @testdox Should ignore imported posts that are not orders.
	 */
	public function test_ignores_imported_posts_that_are_not_orders(): void {
		$post_id = $this->insert_imported_post( 'post' );

		$this->simulate_import( $post_id );

		$this->assertFalse( wc_get_container()->get( OrdersTableDataStore::class )->order_exists( $post_id ) );
	}

	/**
	 * @testdox Should not touch the HPOS tables when the posts table is authoritative.
	 */
	public function test_skips_backfill_when_posts_are_authoritative(): void {
		$this->toggle_cot_feature_and_usage( false );
		$post_id = $this->insert_imported_post();

		$this->simulate_import( $post_id );

		$this->assertFalse( wc_get_container()->get( OrdersTableDataStore::class )->order_exists( $post_id ) );
	}
}
