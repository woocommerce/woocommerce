<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractProductGrid;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductOnSaleMock;

/**
 * Tests for the ProductOnSale block type, focused on the suppression of
 * the standard "added to cart" notice when the legacy add-to-cart link
 * originates from the block rendered on a single product page.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/42156
 */
class ProductOnSaleTest extends \WP_UnitTestCase {

	/**
	 * Mock instance.
	 *
	 * @var ProductOnSaleMock
	 */
	private ProductOnSaleMock $mock;

	/**
	 * Snapshot of $_REQUEST so we can restore between tests.
	 *
	 * @var array
	 */
	private array $original_request = array();

	/**
	 * Setup test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_request = $_REQUEST;
		$this->mock             = new ProductOnSaleMock();
		$this->mock->trigger_filter_registration();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_REQUEST = $this->original_request;

		// Remove any filters we added during the test so they do not leak
		// into subsequent tests.
		remove_all_filters( 'wc_add_to_cart_message_html' );

		parent::tearDown();
	}

	/**
	 * The notice should be suppressed when the marker query arg is
	 * present in the current request.
	 */
	public function test_notice_is_suppressed_when_marker_query_arg_is_present(): void {
		$_REQUEST[ AbstractProductGrid::SINGLE_PRODUCT_GRID_ATC_QUERY_ARG ] = '1';

		$filtered = apply_filters( 'wc_add_to_cart_message_html', 'Added "Foo" to your cart.', array( 1 => 1 ), false );

		$this->assertSame( '', $filtered );
	}

	/**
	 * The notice should pass through unchanged when the marker query arg
	 * is not present in the current request.
	 */
	public function test_notice_passes_through_when_marker_query_arg_is_absent(): void {
		unset( $_REQUEST[ AbstractProductGrid::SINGLE_PRODUCT_GRID_ATC_QUERY_ARG ] );

		$message  = 'Added &ldquo;Foo&rdquo; to your cart.';
		$filtered = apply_filters( 'wc_add_to_cart_message_html', $message, array( 1 => 1 ), false );

		$this->assertSame( $message, $filtered );
	}

	/**
	 * The query arg name is part of the contract between the rendered URL
	 * and the suppression filter, so we lock it in via this test to
	 * guard against accidental renames.
	 */
	public function test_marker_query_arg_constant_is_stable(): void {
		$this->assertSame( '_wc_blocks_grid_atc', AbstractProductGrid::SINGLE_PRODUCT_GRID_ATC_QUERY_ARG );
	}
}
