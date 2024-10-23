<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\AssetsController as TestedAssetsController;

/**
 * Unit tests for the PatternRegistry class.
 */
class AssetsController extends \WP_UnitTestCase {

	/**
	 * Holds the mock Api instance.
	 *
	 * @var Api The mock API.
	 */
	private $api;

	/**
	 * Holds the AssetsController under test.
	 *
	 * @var TestedAssetsController The AssetsController under test.
	 */
	private $block_types_controller;

	/**
	 * Sets up a new TestedAssetsController so it can be tested.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->api               = $this->createMock( Api::class );
		$this->assets_controller = new TestedAssetsController( $this->api );

		// A block checkout or cart page must exist in order to have resource hints.
		$page    = array(
			'name'    => 'block-checkout',
			'title'   => 'Block Checkout',
			'content' => '<!-- wp:woocommerce/checkout -->',
		);
		$page_id = wc_create_page( $page['name'], 'woocommerce_checkout_page_id', $page['title'], $page['content'] );

		// Ensure a product exists in the cart unless the test specifies otherwise.
		$product = \WC_Helper_Product::create_simple_product();
		wc()->cart->add_to_cart( $product->get_id() );

		// Set up some mock dependencies.
		global $wp_scripts;
		$wp_scripts->registered['mock-dependency']     = (object) array(
			'ver'  => '1.2.3',
			'src'  => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/mock-dependency.js',
			'deps' => array(
				'mock-sub-dependency',
			),
		);
		$wp_scripts->registered['mock-sub-dependency'] = (object) array(
			'ver'  => '1.2.3',
			'src'  => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/mock-sub-dependency.js',
			'deps' => array(),
		);
	}

	/**
	 * Clean up mock dependencies and pages after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		wp_delete_post( get_option( 'woocommerce_checkout_page_id' ), true );
		delete_option( 'woocommerce_checkout_page_id' );

		wc()->cart->empty_cart();

		global $wp_scripts;
		unset( $wp_scripts->registered['mock-dependency'] );
		unset( $wp_scripts->registered['mock-sub-dependency'] );
	}

	/**
	 * Tests that no additional resource hints are added on non-prefetch, non-prerender relations.
	 *
	 * @return void
	 */
	public function test_no_additional_resource_hints_added() {
		$mock_urls = array(
			array(
				'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/blocks/mock.js?ver=1.1.0',
				'as'   => 'script',
			),
		);
		$urls      = $this->assets_controller->add_resource_hints( $mock_urls, 'mock_relation' );

		$this->assertEquals( $mock_urls, $urls );
	}

	/**
	 * Tests that no additional resource hints are added on non-prefetch, non-prerender relations.
	 *
	 * @return void
	 */
	public function test_no_additional_resource_hints_added_on_non_prefetch_prerender_relation() {
		$mock_urls = array(
			array(
				'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/blocks/mock.js?ver=1.1.0',
				'as'   => 'script',
			),
		);
		$urls      = $this->assets_controller->add_resource_hints( $mock_urls, 'mock_relation' );

		$this->assertEquals( $mock_urls, $urls );
	}

	/**
	 * Tests that no additional resource hints are added on empty carts.
	 *
	 * @return void
	 */
	public function test_no_additional_resource_hints_added_on_empty_cart() {
		wc()->cart->empty_cart();
		$mock_urls = array(
			array(
				'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/blocks/mock.js?ver=1.1.0',
				'as'   => 'script',
			),
		);
		$urls      = $this->assets_controller->add_resource_hints( $mock_urls, 'prefetch' );

		$this->assertEquals( $mock_urls, $urls );
	}

	/**
	 * Tests that no additional resource hints are added on empty carts.
	 *
	 * @return void
	 */
	public function test_additional_resource_hints_added_when_block_cart_exists() {
		$this->api->expects( $this->once() )
			->method( 'get_script_data' )
			->willReturn(
				array(
					'version'      => '1.2.3',
					'src'          => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/checkout.js',
					'dependencies' => array(
						'mock-dependency',
					),
				)
			);

		$mock_urls = array(
			array(
				'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/blocks/mock.js?ver=1.1.0',
				'as'   => 'script',
			),
		);
		$urls      = $this->assets_controller->add_resource_hints( $mock_urls, 'prefetch' );

		$this->assertEquals(
			array_merge(
				$mock_urls,
				array(
					array(
						'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/checkout.js?ver=1.2.3',
						'as'   => 'script',
					),
					array(
						'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/mock-dependency.js?ver=1.2.3',
						'as'   => 'script',
					),
					array(
						'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/mock-sub-dependency.js?ver=1.2.3',
						'as'   => 'script',
					),
				)
			),
			$urls,
		);
	}

	/**
	 * Tests that the additional resource hints uses the cache when available.
	 *
	 * @return void
	 */
	public function test_additional_resource_hints_cache() {
		$mock_cache = array(
			'files'   => array(
				'checkout-frontend' => array(
					'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/mock-cached.js?ver=1.2.3',
					'as'   => 'script',
				),
			),
			'version' => array(
				'woocommerce' => WOOCOMMERCE_VERSION,
				'wordpress'   => get_bloginfo( 'version' ),
			),
		);
		set_site_transient( 'woocommerce_block_asset_resource_hints', $mock_cache );

		$urls = $this->assets_controller->add_resource_hints( array(), 'prefetch' );

		$this->assertEquals(
			$mock_cache['files']['checkout-frontend'],
			$urls,
		);
	}

	/**
	 * Tests that the additional resource hints don't use the cache when the version is invalid.
	 *
	 * @return void
	 */
	public function test_additional_resource_hints_invalid_cache() {
		$this->api->expects( $this->once() )
			->method( 'get_script_data' )
			->willReturn(
				array(
					'version'      => '1.2.3',
					'src'          => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/checkout.js',
					'dependencies' => array(),
				)
			);

		$mock_cache = array(
			'files'   => array(
				'checkout-frontend' => array(
					'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/mock-cached.js?ver=1.2.3',
					'as'   => 'script',
				),
			),
			'version' => array(
				'woocommerce' => WOOCOMMERCE_VERSION,
				'wordpress'   => '0.1.0-old',
			),
		);
		set_site_transient( 'woocommerce_block_asset_resource_hints', $mock_cache );

		$urls = $this->assets_controller->add_resource_hints( array(), 'prefetch' );

		$this->assertEquals(
			array(
				array(
					'href' => 'http://test.local/wp-content/plugins/woocommerce/assets/client/block/checkout.js?ver=1.2.3',
					'as'   => 'script',
				),
			),
			$urls,
		);
	}
}
