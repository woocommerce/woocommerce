<?php

declare(strict_types=1);
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\BlockifiedProductDetails;

use WC_Helper_Product;

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
	 * Set up product and page for each test
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
}
