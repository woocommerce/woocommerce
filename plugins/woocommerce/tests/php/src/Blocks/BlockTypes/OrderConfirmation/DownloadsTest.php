<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Downloads as DownloadsBlock;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
use WC_Customer_Download;
use WC_Data_Store;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the order-confirmation downloads table.
 */
final class DownloadsTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Download rows render for authorized processing and completed orders with the exact names, URLs, and table classes.
	 */
	public function test_download_table_requires_entitlement_and_permission(): void {
		$download_directories  = wc_get_container()->get( Download_Directories::class );
		$original_mode_option  = get_option( 'wc_downloads_approved_directories_mode', null );
		$original_grant_option = get_option( 'woocommerce_downloads_grant_access_after_payment', null );
		$product               = null;
		$order                 = null;
		$file_exists           = static function (): bool {
			return true;
		};
		$download_directories->set_mode( Download_Directories::MODE_DISABLED );
		update_option( 'woocommerce_downloads_grant_access_after_payment', 'yes' );
		add_filter( 'woocommerce_downloadable_file_exists', $file_exists );

		try {
			$product = \WC_Helper_Product::create_downloadable_product(
				array(
					array(
						'name' => 'Single 1',
						'file' => 'https://example.com/single-1.txt',
					),
					array(
						'name' => 'Single 2',
						'file' => 'https://example.com/single-2.txt',
					),
				)
			);
			$order   = $this->create_order( $product );

			$this->assertSame( '', $this->render( $order, 'full' ), 'Pending orders should not render a downloads table.' );

			$order->set_status( 'processing' );
			$order->save();
			wc_downloadable_product_permissions( $order->get_id(), true );
			$order = $this->reload_order( $order );

			$this->assert_download_table( $order, 'processing' );

			$order->set_status( 'completed' );
			$order->save();
			$order = $this->reload_order( $order );

			$this->assert_download_table( $order, 'completed' );

			$this->assertSame( '', $this->render( $order, false ), 'Order details permission is required even after entitlement is granted.' );
		} finally {
			if ( $order instanceof WC_Order ) {
				$this->delete_download_permissions( $order );
				$order->delete( true );
			}
			if ( $product instanceof WC_Product ) {
				$product->delete( true );
			}
			remove_filter( 'woocommerce_downloadable_file_exists', $file_exists );
			if ( null === $original_mode_option ) {
				delete_option( 'wc_downloads_approved_directories_mode' );
			} else {
				update_option( 'wc_downloads_approved_directories_mode', $original_mode_option );
			}
			wp_cache_delete( 'wc_downloads_approved_directories_mode', 'options' );
			if ( null === $original_grant_option ) {
				delete_option( 'woocommerce_downloads_grant_access_after_payment' );
			} else {
				update_option( 'woocommerce_downloads_grant_access_after_payment', $original_grant_option );
			}
		}
	}

	/**
	 * Assert the rendered download table for one entitled order status.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $status Expected order status.
	 */
	private function assert_download_table( WC_Order $order, string $status ): void {
		$downloads      = $order->get_downloadable_items();
		$download_names = array_column( $downloads, 'download_name' );
		sort( $download_names );

		$this->assertSame( $status, $order->get_status() );
		$this->assertCount( 2, $downloads, 'The real order should expose both granted download entitlements.' );
		$this->assertSame( array( 'Single 1', 'Single 2' ), $download_names, 'The exact two unique fixture download names should be granted.' );

		foreach ( $downloads as $download ) {
			$this->assertNotSame( '', $download['download_url'], 'A granted entitlement must have a non-empty URL.' );
		}

		$content = $this->render( $order, 'full' );

		$this->assertStringContainsString( 'wc-block-order-confirmation-downloads__table', $content );
		$this->assertSame( 3, substr_count( $content, '<tr>' ), 'The table should contain one header row and two entitlement rows.' );
		$this->assertSame( 2, substr_count( $content, '<td class="download-file"' ), 'Each entitlement should render a file cell.' );
		$this->assertSame( 2, substr_count( $content, 'class="woocommerce-MyAccount-downloads-file button alt"' ), 'Each entitlement should render the expected download-link classes.' );

		foreach ( $downloads as $download ) {
			$escaped_download_url = esc_url( $download['download_url'] );

			$this->assertNotSame( '', $escaped_download_url, 'A granted entitlement must retain a non-empty URL after escaping.' );
			$this->assertStringContainsString( esc_html( $download['download_name'] ), $content );
			$this->assertStringContainsString( $escaped_download_url, $content );
		}
	}

	/**
	 * Reload an order from persistence.
	 *
	 * @param WC_Order $order Order object.
	 * @return WC_Order
	 */
	private function reload_order( WC_Order $order ): WC_Order {
		$reloaded_order = wc_get_order( $order->get_id() );
		$this->assertInstanceOf( WC_Order::class, $reloaded_order );

		return $reloaded_order;
	}

	/**
	 * Render the downloads table through a public proxy.
	 *
	 * @param WC_Order     $order Order object.
	 * @param string|false $permission View permission.
	 * @return string
	 */
	private function render( WC_Order $order, $permission ): string {
		$proxy = new class() extends DownloadsBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_content_proxy( $order, $permission ) {
				return $this->render_content( $order, $permission );
			}
		};

		return $proxy->render_content_proxy( $order, $permission );
	}

	/**
	 * Create a guest order containing the downloadable product.
	 *
	 * @param WC_Product $product Product object.
	 * @return WC_Order
	 */
	private function create_order( WC_Product $product ): WC_Order {
		$order = wc_create_order( array( 'customer_id' => 0 ) );
		$item  = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );
		$order->set_billing_email( 'downloads-shopper@example.com' );
		$order->save();

		return $order;
	}

	/**
	 * Delete every download grant associated with the fixture order.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function delete_download_permissions( WC_Order $order ): void {
		$download_store = WC_Data_Store::load( 'customer-download' );
		$downloads      = $download_store->get_downloads( array( 'order_id' => $order->get_id() ) );

		foreach ( $downloads as $download ) {
			if ( $download instanceof WC_Customer_Download ) {
				$download->delete( true );
			}
		}
	}
}
