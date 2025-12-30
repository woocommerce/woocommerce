<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the FilledMiniCartContentsBlock block type.
 */
class FilledMiniCartContentsBlockTest extends \WP_UnitTestCase {

	/**
	 * Test products.
	 *
	 * @var array
	 */
	private $products;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		StoreApi::container();

		$fixtures       = new FixtureData();
		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product',
					'stock_status'  => 'instock',
					'regular_price' => 10,
				)
			),
		);

		WC()->cart->empty_cart();
		add_filter( 'woocommerce_is_rest_api_request', '__return_false', 1 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();

		WC()->cart->empty_cart();
		remove_filter( 'woocommerce_is_rest_api_request', '__return_false', 1 );
		remove_all_actions( 'woocommerce_store_api_cart_errors' );

		$this->reset_blocks_shared_state();
	}

	/**
	 * Reset the static state in BlocksSharedState between tests.
	 */
	private function reset_blocks_shared_state(): void {
		$reflection = new \ReflectionClass( BlocksSharedState::class );
		$property   = $reflection->getProperty( 'blocks_shared_cart_state' );
		$property->setValue( null, null );
	}

	/**
	 * @testdox Should render error notices in data-wp-context when cart has errors.
	 */
	public function test_renders_error_notices_in_context_when_cart_has_errors(): void {
		// WordPress warns about interactivity directives in SVG tags during server-side render.
		$this->setExpectedIncorrectUsage( 'WP_Interactivity_API_Directives_Processor::skip_to_tag_closer' );

		WC()->cart->add_to_cart( $this->products[0]->get_id(), 2 );

		add_action(
			'woocommerce_store_api_cart_errors',
			function ( $errors ) {
				$errors->add( 'test_error_code', 'Test error message for mini cart' );
			},
			10,
			2
		);

		$block  = parse_blocks( '<!-- wp:woocommerce/filled-mini-cart-contents-block --><!-- /wp:woocommerce/filled-mini-cart-contents-block -->' );
		$output = render_block( $block[0] );

		$this->assertStringContainsString( 'wc-block-components-notices', $output, 'Output should contain notices container' );

		$processor = new \WP_HTML_Tag_Processor( $output );
		$found     = $processor->next_tag(
			array(
				'tag_name'   => 'div',
				'class_name' => 'wc-block-components-notices',
			)
		);

		$this->assertTrue( $found, 'Notices container div should exist' );

		$context_attr_value = $processor->get_attribute( 'data-wp-context' );
		$this->assertNotNull( $context_attr_value, 'data-wp-context attribute should be present' );

		$context = json_decode( $context_attr_value, true );
		$this->assertIsArray( $context, 'Context should be valid JSON array' );
		$this->assertArrayHasKey( 'notices', $context, 'Context should have notices key' );
		$this->assertNotEmpty( $context['notices'], 'Notices array should not be empty when cart has errors' );

		$notice = $context['notices'][0];
		$this->assertEquals( 'Test error message for mini cart', $notice['notice'], 'Notice message should match error message' );
		$this->assertEquals( 'error', $notice['type'], 'Notice type should be error' );
		$this->assertTrue( $notice['dismissible'], 'Notice should be dismissible' );
		$this->assertStringStartsWith( 'store-notice-', $notice['id'], 'Notice ID should have correct prefix' );
	}

	/**
	 * @testdox Should render empty notices array when cart has no errors.
	 */
	public function test_renders_empty_notices_when_cart_has_no_errors(): void {
		WC()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		$block  = parse_blocks( '<!-- wp:woocommerce/filled-mini-cart-contents-block --><!-- /wp:woocommerce/filled-mini-cart-contents-block -->' );
		$output = render_block( $block[0] );

		$processor = new \WP_HTML_Tag_Processor( $output );
		$processor->next_tag(
			array(
				'tag_name'   => 'div',
				'class_name' => 'wc-block-components-notices',
			)
		);

		$context = json_decode( $processor->get_attribute( 'data-wp-context' ), true );
		$this->assertEmpty( $context['notices'], 'Notices array should be empty when cart has no errors' );
	}

	/**
	 * @testdox Should render multiple notices when cart has multiple errors.
	 */
	public function test_renders_multiple_notices_for_multiple_errors(): void {
		// TODO: Should we hardcode the path? Because server side rendering does not support SVG, we will get a _doing_it_wrong warning,
		// which WooCommerce should probably not do because it will confuse devs.
		$this->setExpectedIncorrectUsage( 'WP_Interactivity_API_Directives_Processor::skip_to_tag_closer' );

		WC()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		add_action(
			'woocommerce_store_api_cart_errors',
			function ( $errors ) {
				$errors->add( 'first_error', 'First error message' );
				$errors->add( 'second_error', 'Second error message' );
			},
			10,
			2
		);

		$block  = parse_blocks( '<!-- wp:woocommerce/filled-mini-cart-contents-block --><!-- /wp:woocommerce/filled-mini-cart-contents-block -->' );
		$output = render_block( $block[0] );

		$processor = new \WP_HTML_Tag_Processor( $output );
		$processor->next_tag(
			array(
				'tag_name'   => 'div',
				'class_name' => 'wc-block-components-notices',
			)
		);

		$context = json_decode( $processor->get_attribute( 'data-wp-context' ), true );
		$this->assertCount( 2, $context['notices'], 'Should have two notices for two errors' );
		$this->assertEquals( 'First error message', $context['notices'][0]['notice'] );
		$this->assertEquals( 'Second error message', $context['notices'][1]['notice'] );
	}
}
