<?php

declare(strict_types=1);
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\BlockifiedProductDetails;

use WC_Helper_Product;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;

/**
 * Tests for the BlockifiedProductDetails block type
 */
class BlockifiedProductDetails extends \WP_UnitTestCase {

	/**
	 * Page ID
	 *
	 * @var @string
	 */
	private static $page_id;

	/**
	 * Product
	 *
	 * @var @WC_Product
	 */
	private static $product;

	/**
	 * @var AssetDataRegistryMock The asset data registry mock.
	 */
	private $registry;

	/**
	 * @var IntegrationRegistry The integration registry, not used, but required to set up a BlockifiedProductDetails block.
	 */
	private $integration_registry;

	/**
	 * @var Api The asset API, not used, but required to set up a BlockifiedProductDetails block.
	 */
	private $asset_api;

	/**
	 * Mock logger instance.
	 *
	 * @var \WC_Logger_Interface $mock_logger
	 */
	private $mock_logger;

	/**
	 * Create Simple Product and Page
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$product = WC_Helper_Product::create_simple_product( false );
		WC_Helper_Product::create_product_review( self::$product );

		self::$page_id = wp_insert_post(
			[
				'post_title'  => 'Test Product Page',
				'post_type'   => 'page',
				'post_status' => 'publish',
			],
			true
		);
	}
	/**
	 * Set up product and page for each test, and create an AssetDataRegistryMock.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		global $post, $product;

		$post = get_post( self::$page_id );
		setup_postdata( $post );
		$product            = self::$product;
		$GLOBALS['product'] = $product;

		$this->asset_api            = Package::container()->get( API::class );
		$this->registry             = new AssetDataRegistryMock( $this->asset_api );
		$this->integration_registry = new IntegrationRegistry();
		$this->mock_logger          = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		add_filter(
			'woocommerce_logging_class',
			array( $this, 'override_wc_logger' )
		);
	}

	/**
	 * Reset postdata after each test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		wp_reset_postdata();
	}

	/**
	 * Delete the product and page after all tests
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		wp_delete_post( self::$page_id, true );
		WC_Helper_Product::delete_product( self::$product->get_id() );
	}


	/**
	 * Test Product Details render function when `woocommerce_product_tabs` hook isn't used
	 * IMPORTANT: The current test doesn't validate the entire HTML, but only the text content inside the HTML.
	 * This is because some ids are generated dynamically via wp_unique_id that it is not straightforward to mock.
	 */
	public function test_product_details_render_with_no_hook() {

		$template = file_get_contents( __DIR__ . '/test_product_details_render_with_no_hook_template.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$serialized_blocks = do_blocks( $template );

		$expected_serialized_blocks                    = file_get_contents( __DIR__ . '/test_product_details_render_with_no_hook_expected_result.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$serialized_blocks_without_whitespace          = wp_strip_all_tags( $serialized_blocks, true );
		$expected_serialized_blocks_without_whitespace = wp_strip_all_tags( $expected_serialized_blocks, true );
		$this->assertEquals( $serialized_blocks_without_whitespace, $expected_serialized_blocks_without_whitespace, '' );
	}

	/**
	 * Test Product Details render function when `woocommerce_product_tabs` hook is used.
	 * IMPORTANT: The current test doesn't validate the entire HTML, but only the text content inside the HTML.
	 * This is because some ids are generated dynamically via wp_unique_id that it is not straightforward to mock.
	 */
	public function test_product_details_render_with_hook() {
		add_filter(
			'woocommerce_product_tabs',
			function ( $tabs ) {
				$tabs['custom_info_tab'] = array(
					'title'    => 'Custom Info',
					'priority' => 50,
					'callback' => function () {
						echo '<p>This is the content for the custom info tab.</p>';
					},
				);

				$tabs['specifications_tab'] = array(
					'title'    => 'Specifications',
					'priority' => 60,
					'callback' => function () {
						echo '<h2>Specifications</h2>
						<p>Here you can list product specifications.</p>';
					},
				);

				return $tabs;
			}
		);

		$template = file_get_contents( __DIR__ . '/test_product_details_render_with_hook_template.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$serialized_blocks = do_blocks( $template );

		$expected_serialized_blocks = file_get_contents( __DIR__ . '/test_product_details_render_with_hook_expected_result.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$serialized_blocks_without_whitespace          = wp_strip_all_tags( $serialized_blocks, true );
		$expected_serialized_blocks_without_whitespace = wp_strip_all_tags( $expected_serialized_blocks, true );

		$this->assertEquals( $serialized_blocks_without_whitespace, $expected_serialized_blocks_without_whitespace, '' );
	}

	public function test_hooked_block() {
		$test_block = [
			"slug" => "custom-info",
			"title" => "Custom Info",
			"content" =>
				"<!-- wp:paragraph --><p>This is the content for the custom info tab.</p><!-- /wp:paragraph -->"
		];

		remove_all_filters( 'hooked_block_types' );
		remove_all_filters( 'hooked_block_' . $test_block['slug'] );

		add_filter( 'woocommerce_product_details_hooked_blocks', function ( $hooked_blocks ) use ( $test_block ) {
			$hooked_blocks[] = $test_block;
			return $hooked_blocks;
		} );

		$hooked_block_types_introspection = new MockAction();
		add_filter( 'hooked_block_types', array( $hooked_block_types_introspection, 'filter' ), 20, 4 );

		// Create a new BlockifiedProductDetails block class with the mocked AssetDataRegistry.
		// This will apply the `woocommerce_product_details_hooked_blocks` filter defined above.
		$block_instance = new BlockifiedProductDetails(
			$this->asset_api,
			$this->registry,
			$this->integration_registry,
			'blockified-product-details-mock'
		);

		$this->assertSame( 1, $hooked_block_types_introspection->get_call_count() );

		$args = $hooked_block_types_introspection->get_args();
		$this->assertSame( array( $test_block['slug'] ), $args[0][1] );
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}
}
