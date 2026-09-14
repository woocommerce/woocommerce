<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\ImportExport\HposOrderExportHandler;
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
		$order->save();

		return $order;
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
	 * @testdox Should include trashed orders like the core exporter does.
	 */
	public function test_exports_trashed_orders(): void {
		$order    = $this->create_order();
		$order_id = $order->get_id();
		$order->delete();

		$xml = $this->export( 'shop_order' );

		$this->assertStringContainsString( "<wp:post_id>{$order_id}</wp:post_id>", $xml );
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
}
