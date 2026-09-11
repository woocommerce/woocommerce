<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\DownloadsWrapper as DownloadsWrapperClass;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

/**
 * Test DownloadsWrapper class.
 */
final class DownloadsWrapper extends \WP_UnitTestCase {
	/**
	 * Enable synchronous product attribute lookup updates for test fixtures.
	 *
	 * @return string
	 */
	public static function enable_direct_attribute_lookup_updates(): string {
		return 'yes';
	}

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		global $wpdb;

		parent::set_up();
		add_filter( 'pre_option_woocommerce_attribute_lookup_direct_updates', array( self::class, 'enable_direct_attribute_lookup_updates' ) );

		/** @var \WC_Product[] $products */
		$products = ( new \WC_Product_Query() )->get_products();
		foreach ( $products as $product ) {
			$product->delete();
		}
		$wpdb->query( "DELETE FROM {$wpdb->wc_product_meta_lookup}" );
	}

	/**
	 * Perform products/options/cache cleanup.
	 */
	public function tear_down() {
		delete_option( 'woocommerce_product_lookup_table_is_generating' );
		wp_cache_delete( 'woocommerce_has_downloadable_products', 'woocommerce' );
		remove_filter( 'pre_option_woocommerce_attribute_lookup_direct_updates', array( self::class, 'enable_direct_attribute_lookup_updates' ) );

		parent::tear_down();
	}

	/**
	 * Test `store_has_downloadable_products`: query product meta lookup table.
	 *
	 * @dataProvider provider_downloadable_products
	 * @param bool $downloadable Whether the product is downloadable.
	 */
	public function test_store_has_downloadable_products_via_product_meta_lookup_table_with_downloadable( bool $downloadable ): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'downloadable' => $downloadable ) );
		$proxy   = new class() extends DownloadsWrapperClass {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function store_has_downloadable_products_proxy(): bool {
				return $this->store_has_downloadable_products();
			}
		};

		$this->assertSame( $downloadable, $proxy->store_has_downloadable_products_proxy() );
	}

	/**
	 * A data provider.
	 *
	 * @return array
	 */
	public function provider_downloadable_products(): array {
		return array(
			array( true ),
			array( false ),
		);
	}

	/**
	 * Test `store_has_downloadable_products`: query post meta table.
	 */
	public function test_store_has_downloadable_products_via_posts_meta_table(): void {
		$proxy = new class() extends DownloadsWrapperClass {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function store_has_downloadable_products_proxy(): bool {
				return $this->store_has_downloadable_products();
			}
		};
		add_option( 'woocommerce_product_lookup_table_is_generating', 'yes' );

		\WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );
		$this->assertTrue( $proxy->store_has_downloadable_products_proxy() );
		$this->assertSame( 'yes', wp_cache_get( 'woocommerce_has_downloadable_products', 'woocommerce' ) );
	}

	/**
	 * Test `store_has_downloadable_products`: picking up the cached value.
	 */
	public function test_store_has_downloadable_products_via_cache(): void {
		$proxy = new class() extends DownloadsWrapperClass {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function store_has_downloadable_products_proxy(): bool {
				return $this->store_has_downloadable_products();
			}
		};
		add_option( 'woocommerce_product_lookup_table_is_generating', 'yes' );
		wp_cache_set( 'woocommerce_has_downloadable_products', 'no', 'woocommerce' );

		\WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );
		$this->assertFalse( $proxy->store_has_downloadable_products_proxy() );
	}

	/**
	 * @testdox Runtime wrapper content requires permission, a downloadable order item, and a download-permitted status.
	 */
	public function test_runtime_render_requires_download_permission(): void {
		$download_directories = wc_get_container()->get( Download_Directories::class );
		$original_mode_option = get_option( 'wc_downloads_approved_directories_mode', null );
		$downloadable_product = null;
		$plain_product        = null;
		$downloadable_order   = null;
		$plain_order          = null;
		$file_exists          = static function (): bool {
			return true;
		};
		$download_directories->set_mode( Download_Directories::MODE_DISABLED );
		add_filter( 'woocommerce_downloadable_file_exists', $file_exists );

		try {
			$downloadable_product = \WC_Helper_Product::create_downloadable_product(
				array(
					array(
						'name' => 'Wrapper download',
						'file' => 'https://example.com/wrapper-download.txt',
					),
				)
			);
			$plain_product        = \WC_Helper_Product::create_simple_product();
			$downloadable_order   = $this->create_order_with_product( $downloadable_product );
			$plain_order          = $this->create_order_with_product( $plain_product );

			$this->assertSame( '', $this->render( $downloadable_order, 'full' ), 'A pending order should not expose downloads.' );

			$plain_order->set_status( 'completed' );
			$plain_order->save();
			$this->assertSame( '', $this->render( $plain_order, 'full' ), 'An order without a downloadable item should not render the wrapper.' );

			$downloadable_order->set_status( 'completed' );
			$downloadable_order->save();
			$this->assertSame( '', $this->render( $downloadable_order, false ), 'View permission is required.' );
			$this->assertSame( '<p>Download marker</p>', $this->render( $downloadable_order, 'full' ), 'A completed downloadable order should render non-empty wrapper content.' );
		} finally {
			if ( $downloadable_order instanceof \WC_Order ) {
				$downloadable_order->delete( true );
			}
			if ( $plain_order instanceof \WC_Order ) {
				$plain_order->delete( true );
			}
			if ( $downloadable_product instanceof \WC_Product ) {
				$downloadable_product->delete( true );
			}
			if ( $plain_product instanceof \WC_Product ) {
				$plain_product->delete( true );
			}
			remove_filter( 'woocommerce_downloadable_file_exists', $file_exists );
			if ( null === $original_mode_option ) {
				delete_option( 'wc_downloads_approved_directories_mode' );
			} else {
				update_option( 'wc_downloads_approved_directories_mode', $original_mode_option );
			}
			wp_cache_delete( 'wc_downloads_approved_directories_mode', 'options' );
		}
	}

	/**
	 * Render runtime wrapper content through a public proxy.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission View permission.
	 * @return string
	 */
	private function render( \WC_Order $order, $permission ): string {
		$proxy = new class() extends DownloadsWrapperClass {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_content_proxy( $order, $permission, $content ) {
				return $this->render_content( $order, $permission, array(), $content );
			}
		};

		return $proxy->render_content_proxy( $order, $permission, '<p>Download marker</p>' );
	}

	/**
	 * Create a persisted order containing one product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return \WC_Order
	 */
	private function create_order_with_product( \WC_Product $product ): \WC_Order {
		$order = wc_create_order( array( 'customer_id' => 0 ) );
		$item  = new \WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );
		$order->set_billing_email( 'wrapper-shopper@example.com' );
		$order->save();

		return $order;
	}
}
