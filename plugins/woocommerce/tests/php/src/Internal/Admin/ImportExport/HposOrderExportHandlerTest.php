<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\ImportExport\HposOrderExportHandler;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use DOMDocument;
use DOMXPath;
use ReflectionMethod;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the HPOS order exporter.
 */
class HposOrderExportHandlerTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * The System Under Test.
	 *
	 * @var HposOrderExportHandler
	 */
	private HposOrderExportHandler $sut;

	/**
	 * Enable HPOS with compatibility mode off.
	 */
	public function setUp(): void {
		parent::setUp();
		OrderHelper::create_order_custom_table_if_not_exist();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();
		$this->sut = wc_get_container()->get( HposOrderExportHandler::class );
	}

	/**
	 * @testdox Leaves the response to WordPress when the order post type disables export.
	 */
	public function test_honors_order_post_type_export_opt_out(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertTrue( current_user_can( 'export' ) );
		set_current_screen( 'export' );
		$post_type  = get_post_type_object( 'shop_order' );
		$can_export = $post_type->can_export;

		add_filter(
			'export_wp_filename',
			function () {
				$this->fail( 'The HPOS handler started an export for a disabled post type.' );
			}
		);

		try {
			$post_type->can_export = false;
			$this->expectOutputString( '' );
			$this->sut->handle_export_wp( array( 'content' => 'shop_order' ) );
		} finally {
			$post_type->can_export = $can_export;
			set_current_screen( 'front' );
		}
	}

	/**
	 * @testdox Exports each current order once across batches, even when legacy copies remain.
	 */
	public function test_exports_only_current_orders_across_batches(): void {
		$this->enable_cot_sync();
		$mirrored = $this->create_order();
		$deleted  = $this->create_order();
		$this->assertSame( 'shop_order', get_post_type( $mirrored->get_id() ) );

		$this->disable_cot_sync();
		$deleted_id = $deleted->get_id();
		$deleted->delete( true );
		$this->assertSame( 'shop_order', get_post_type( $deleted_id ) );

		$expected_ids = array( (string) $mirrored->get_id() );
		for ( $i = 0; $i < 21; ++$i ) {
			$expected_ids[] = (string) $this->create_order()->get_id();
		}
		$legacy_status_id = (int) end( $expected_ids );
		global $wpdb;
		$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'status' => 'wc-legacy-status' ), array( 'id' => $legacy_status_id ) );
		$this->create_order( OrderStatus::AUTO_DRAFT );

		$xpath = $this->export();
		$ids   = array();
		foreach ( $xpath->query( '/rss/channel/item/wp:post_id' ) as $node ) {
			$ids[] = $node->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
		}
		$this->assertSame( $expected_ids, $ids );
		$this->assertSame( 'wc-legacy-status', $xpath->evaluate( 'string(/rss/channel/item[last()]/wp:status)' ) );
	}

	/**
	 * @testdox Preserves order fields, repeated metadata and raw note metadata in valid WXR.
	 */
	public function test_exports_order_fields_and_metadata(): void {
		update_option( 'timezone_string', 'America/New_York' );

		$order = $this->create_order();
		$order->set_date_created( 1705312800 );
		$order->set_customer_id( 123 );
		$order->set_billing_first_name( '山田 & Jane' );
		$order->set_shipping_city( 'New York' );
		$order->set_customer_note( 'Gift ]]> & card 🎁' );
		$order->add_meta_data( 'label', 'A' );
		$order->add_meta_data( 'label', 'B' );
		$order->add_meta_data( '_export_skip', 'secret' );
		$order->save();

		global $wpdb;
		$wpdb->insert(
			OrdersTableDataStore::get_meta_table_name(),
			array(
				'order_id'   => $order->get_id(),
				'meta_key'   => null, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Insert one legacy fixture row.
				'meta_value' => 'Value without a key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Insert one legacy fixture row.
			)
		);
		wp_cache_flush();

		$note_id = $order->add_order_note( 'Packed ]]> & ready', 1 );
		add_comment_meta( $note_id, '123', array( 'carrier' => 'A & B' ) );
		add_comment_meta( $note_id, '_export_skip', 'secret' );

		$skip = static function ( $skip, $key ) {
			return $skip || '_export_skip' === $key;
		};
		add_filter( 'wxr_export_skip_postmeta', $skip, 10, 2 );
		add_filter( 'wxr_export_skip_commentmeta', $skip, 10, 2 );
		add_filter( 'woocommerce_order_get_total', 'floatval' );

		$xpath = $this->export();
		$item  = $xpath->query( '/rss/channel/item' )->item( 0 );
		$this->assertSame( 'wc-processing', $xpath->evaluate( 'string(wp:status)', $item ) );
		$this->assertSame( '2024-01-15 05:00:00', $xpath->evaluate( 'string(wp:post_date)', $item ) );
		$this->assertSame( '2024-01-15 10:00:00', $xpath->evaluate( 'string(wp:post_date_gmt)', $item ) );
		$this->assertSame( 'Gift ]]> & card 🎁', $xpath->evaluate( 'string(excerpt:encoded)', $item ) );
		$this->assertSame( '山田 & Jane', $xpath->evaluate( 'string(wp:postmeta[wp:meta_key = "_billing_first_name"]/wp:meta_value)', $item ) );
		$this->assertSame( 'New York', $xpath->evaluate( 'string(wp:postmeta[wp:meta_key = "_shipping_city"]/wp:meta_value)', $item ) );
		$this->assertSame( '12.50', $xpath->evaluate( 'string(wp:postmeta[wp:meta_key = "_order_total"]/wp:meta_value)', $item ) );
		$this->assertSame( 2.0, $xpath->evaluate( 'count(wp:postmeta[wp:meta_key = "label"])', $item ) );
		$this->assertSame( 0.0, $xpath->evaluate( 'count(wp:postmeta[wp:meta_key = "_customer_user"] | .//wp:meta_key[. = "_export_skip"])', $item ) );
		$this->assertSame( 0.0, $xpath->evaluate( 'count(wp:postmeta[wp:meta_value = "Value without a key"])', $item ) );

		$note = $xpath->query( 'wp:comment[wp:commentmeta/wp:meta_key = "123"]', $item )->item( 0 );
		$this->assertNotNull( $note );
		$this->assertSame( 'Packed ]]> & ready', $xpath->evaluate( 'string(wp:comment_content)', $note ) );
		$this->assertSame( '1', $xpath->evaluate( 'string(wp:commentmeta[wp:meta_key = "is_customer_note"]/wp:meta_value)', $note ) );
		$this->assertSame( array( 'carrier' => 'A & B' ), maybe_unserialize( $xpath->evaluate( 'string(wp:commentmeta[wp:meta_key = "123"]/wp:meta_value)', $note ) ) );
	}

	/**
	 * Create a fixture order.
	 *
	 * @param string $status The order status.
	 * @return WC_Order
	 */
	private function create_order( string $status = OrderStatus::PROCESSING ): WC_Order {
		$order = new WC_Order();
		$order->set_status( $status );
		$order->set_total( '12.50' );
		$order->save();
		return $order;
	}

	/**
	 * Parse the stream without sending HTTP headers or ending the test process.
	 *
	 * @return DOMXPath
	 */
	private function export(): DOMXPath {
		$stream = new ReflectionMethod( $this->sut, 'stream_export' );
		if ( PHP_VERSION_ID < 80100 ) {
			$stream->setAccessible( true );
		}

		ob_start();
		try {
			$stream->invoke( $this->sut );
		} finally {
			$xml = (string) ob_get_clean();
		}

		$document = new DOMDocument();
		$this->assertTrue( $document->loadXML( $xml ) );
		$xpath = new DOMXPath( $document );
		$xpath->registerNamespace( 'wp', 'https://wordpress.org/export/1.2/' );
		$xpath->registerNamespace( 'excerpt', 'https://wordpress.org/export/1.2/excerpt/' );
		return $xpath;
	}
}
