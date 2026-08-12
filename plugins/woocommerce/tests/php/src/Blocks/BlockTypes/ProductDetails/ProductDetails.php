<?php

declare(strict_types=1);
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductDetails;

use WC_Helper_Product;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductDetailsNoRegisterMock;

/**
 * Tests for the ProductDetails block type
 */
class ProductDetails extends \WP_UnitTestCase {

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
			array(
				'post_title'  => 'Test Product Page',
				'post_type'   => 'page',
				'post_status' => 'publish',
			),
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

		$template = file_get_contents( __DIR__ . '/template.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$serialized_blocks = do_blocks( $template );

		$expected_serialized_blocks                    = file_get_contents( __DIR__ . '/render_with_no_hook_expected_result.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$serialized_blocks_without_whitespace          = wp_strip_all_tags( $serialized_blocks, true );
		$expected_serialized_blocks_without_whitespace = wp_strip_all_tags( $expected_serialized_blocks, true );
		$this->assertEquals( $serialized_blocks_without_whitespace, $expected_serialized_blocks_without_whitespace, '' );
	}

	/**
	 * @return array
	 */
	public function render_with_hook_provider() {
		return array(
			'woocommerce_accordion' => array(
				'template.html',
				'render_with_hook_expected_result.html',
			),
			'core_accordion'        => array(
				'template_wp69.html',
				'render_with_hook_expected_result_wp69.html',
			),
		);
	}

	/**
	 * Test Product Details render function when `woocommerce_product_tabs` hook is used.
	 * IMPORTANT: The current test doesn't validate the entire HTML, but only the text content inside the HTML.
	 * This is because some ids are generated dynamically via wp_unique_id that it is not straightforward to mock.
	 *
	 * @dataProvider render_with_hook_provider
	 *
	 * @param string $template_file Template file.
	 * @param string $expected_file Expected result file.
	 */
	public function test_product_details_render_with_hook( $template_file, $expected_file ) {
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

		$template = file_get_contents( __DIR__ . '/' . $template_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$serialized_blocks = do_blocks( $template );

		$expected_serialized_blocks = file_get_contents( __DIR__ . '/' . $expected_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$serialized_blocks_without_whitespace          = wp_strip_all_tags( $serialized_blocks, true );
		$expected_serialized_blocks_without_whitespace = wp_strip_all_tags( $expected_serialized_blocks, true );

		$this->assertEquals( $serialized_blocks_without_whitespace, $expected_serialized_blocks_without_whitespace, '' );
	}

	/**
	 * @return array
	 */
	public function hooked_blocks_provider() {
		return array(
			'woocommerce_accordion' => array(
				'woocommerce/accordion-group',
				'woocommerce/accordion-item',
				'woocommerce/accordion-header',
				'woocommerce/accordion-panel',
			),
			'core_accordion'        => array(
				'core/accordion',
				'core/accordion-item',
				'core/accordion-heading',
				'core/accordion-panel',
			),
		);
	}

	/**
	 * Test the `woocommerce_product_details_hooked_blocks` hook. This hook allows developers to
	 * specify a title and block markup that will be automatically wrapped in the required
	 * Accordion Item block and appended to the Product Details' Accordion Group block.
	 *
	 * @dataProvider hooked_blocks_provider
	 *
	 * @param string $accordion_group_name Accordion group block name.
	 * @param string $accordion_item_name Accordion item block name.
	 * @param string $accordion_header_name Accordion header block name.
	 * @param string $accordion_panel_name Accordion panel block name.
	 */
	public function test_hooked_blocks( $accordion_group_name, $accordion_item_name, $accordion_header_name, $accordion_panel_name ) {
		$test_block = array(
			'slug'    => 'custom-info',
			'title'   => 'Custom Info',
			'content' => '<!-- wp:paragraph --><p>This is the content for the custom info tab.</p><!-- /wp:paragraph -->',
		);

		add_filter(
			'woocommerce_product_details_hooked_blocks',
			function ( $hooked_blocks ) use ( $test_block ) {
				$hooked_blocks[] = $test_block;
				return $hooked_blocks;
			}
		);

		new ProductDetailsNoRegisterMock();

		// Next, we apply the `hooked_block_types` and `hooked_block_{$slug}` filters.
		// We pretend that we're in the `last_child` position of the `woocommerce/accordion-group` block.

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$hooked_block_types = apply_filters( 'hooked_block_types', array(), 'last_child', $accordion_group_name, null );
		$this->assertSame( array( $test_block['slug'] ), $hooked_block_types );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$hooked_block_custom_info = apply_filters(
			'hooked_block_' . $test_block['slug'],
			array(
				'blockName'    => $test_block['slug'],
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerContent' => array(),
			), // $parsed_hooked_block
			$test_block['slug'],
			'last_child',
			array(
				'blockName'    => $accordion_group_name,
				'attrs'        => array(
					'metadata' => array(
						'isDescendantOfProductDetails' => true,
					),
				),
				'innerBlocks'  => array(),
				'innerContent' => array(),
			), // $parsed_anchor_block
			null
		);
		$this->assertSame( $accordion_item_name, $hooked_block_custom_info['blockName'] );
		$this->assertCount( 2, $hooked_block_custom_info['innerBlocks'] );

		$this->assertSame( $accordion_header_name, $hooked_block_custom_info['innerBlocks'][0]['blockName'] );
		$this->assertStringContainsString( $test_block['title'], $hooked_block_custom_info['innerBlocks'][0]['innerHTML'] );

		$this->assertSame( $accordion_panel_name, $hooked_block_custom_info['innerBlocks'][1]['blockName'] );
		$this->assertSame( parse_blocks( $test_block['content'] ), $hooked_block_custom_info['innerBlocks'][1]['innerBlocks'] );
	}
}
