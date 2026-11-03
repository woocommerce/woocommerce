<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi;

use Automattic\WooCommerce\Internal\RestApi\OrderLineItemValidator;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderLineItemValidator class.
 */
class OrderLineItemValidatorTest extends WC_Unit_Test_Case {

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
			OrderLineItemValidator::is_custom_line_item( $line_item ),
			'Unexpected result for ' . wp_json_encode( $line_item )
		);
	}
}
