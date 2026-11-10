<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi;

use Automattic\WooCommerce\Internal\RestApi\OrderLineItemValidationTrait;
use WC_Helper_Product;
use WC_REST_Exception;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderLineItemValidationTrait trait.
 */
class OrderLineItemValidationTraitTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var object
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new class() {
			use OrderLineItemValidationTrait;

			/**
			 * Stands in for the controller's lookup.
			 *
			 * @param array $posted Line item data.
			 * @return int
			 */
			protected function get_product_id( $posted ) {
				return (int) ( $posted['product_id'] ?? 0 );
			}

			/**
			 * Stands in for the controller's removal check.
			 *
			 * @param array $item Line item data.
			 * @return bool
			 */
			protected function item_is_null( $item ) {
				return array_key_exists( 'product_id', $item ) && is_null( $item['product_id'] );
			}

			/**
			 * Public wrapper for is_custom_line_item().
			 *
			 * @param array $line_item Line item data.
			 * @return bool
			 */
			public function call_is_custom_line_item( $line_item ): bool {
				return $this->is_custom_line_item( $line_item );
			}

			/**
			 * Public wrapper for check_new_line_item_products().
			 *
			 * @param mixed $line_items Line items from the request.
			 */
			public function call_check_new_line_item_products( $line_items ): void {
				$this->check_new_line_item_products( $line_items );
			}
		};
	}

	/**
	 * @testdox Should treat a line item as custom only when it has no product reference, a name and a numeric total.
	 * @testWith [{"name": "Gift wrap", "total": "5.00"}, true]
	 *           [{"name": "Gift wrap", "total": "0"}, true]
	 *           [{"product_id": 0, "name": "Gift wrap", "total": "5.00"}, true]
	 *           [{"product_id": 123, "name": "Gift wrap", "total": "5.00"}, false]
	 *           [{"variation_id": 123, "name": "Gift wrap", "total": "5.00"}, false]
	 *           [{"sku": "gift-wrap", "name": "Gift wrap", "total": "5.00"}, false]
	 *           [{"name": "Gift wrap"}, false]
	 *           [{"name": "Gift wrap", "total": null}, false]
	 *           [{"name": "Gift wrap", "total": ""}, false]
	 *           [{"name": "Gift wrap", "total": "abc"}, false]
	 *           [{"total": "5.00"}, false]
	 *           [{"name": " ", "total": "5.00"}, false]
	 *           [{"name": 123, "total": "5.00"}, false]
	 *
	 * @param array<string, mixed> $line_item Line item payload.
	 * @param bool                 $expected  Whether it should count as a custom item.
	 */
	public function test_is_custom_line_item( array $line_item, bool $expected ): void {
		$this->assertSame(
			$expected,
			$this->sut->call_is_custom_line_item( $line_item ),
			'Unexpected result for ' . wp_json_encode( $line_item )
		);
	}

	/**
	 * @testdox Should accept new line items whose product exists.
	 */
	public function test_check_new_line_item_products_accepts_existing_product(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->expectNotToPerformAssertions();

		$this->sut->call_check_new_line_item_products( array( array( 'product_id' => $product->get_id() ) ) );
	}

	/**
	 * @testdox Should reject a new line item for a missing product, even after a valid one.
	 */
	public function test_check_new_line_item_products_rejects_missing_product(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->expectException( WC_REST_Exception::class );
		$this->expectExceptionMessage( 'Invalid product ID.' );
		$this->expectExceptionCode( 400 );

		$this->sut->call_check_new_line_item_products(
			array(
				array( 'product_id' => $product->get_id() ),
				array( 'product_id' => 999999999 ),
			)
		);
	}

	/**
	 * @testdox Should skip line items that won't be saved as new product items.
	 * @testWith [{"id": 7, "product_id": 999999999}]
	 *           [{"product_id": null}]
	 *           [{"product_id": 999999999, "quantity": 0}]
	 *           [{"name": "Gift wrap", "total": "5.00"}]
	 *           ["not a line item"]
	 *
	 * @param mixed $line_item Line item payload.
	 */
	public function test_check_new_line_item_products_skips_items_that_are_not_new_products( $line_item ): void {
		$this->expectNotToPerformAssertions();

		$this->sut->call_check_new_line_item_products( array( $line_item ) );
	}

	/**
	 * @testdox Should do nothing when the request has no line items.
	 * @testWith [null]
	 *           [[]]
	 *
	 * @param mixed $line_items Line items from the request.
	 */
	public function test_check_new_line_item_products_ignores_missing_line_items( $line_items ): void {
		$this->expectNotToPerformAssertions();

		$this->sut->call_check_new_line_item_products( $line_items );
	}
}
