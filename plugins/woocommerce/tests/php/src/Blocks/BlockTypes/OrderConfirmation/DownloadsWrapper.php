<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\DownloadsWrapper as DownloadsWrapperClass;

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
}
