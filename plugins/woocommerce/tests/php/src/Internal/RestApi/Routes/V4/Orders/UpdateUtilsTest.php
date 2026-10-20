<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Orders;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\UpdateUtils;
use Automattic\WooCommerce\Tests\Helpers\MetaDataAssertionTrait;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_REST_Exception;
use WC_Unit_Test_Case;

/**
 * Tests for the UpdateUtils class.
 */
class UpdateUtilsTest extends WC_Unit_Test_Case {
	use MetaDataAssertionTrait;

	/**
	 * The System Under Test.
	 *
	 * @var TestableUpdateUtils
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TestableUpdateUtils();
	}

	/**
	 * @testdox Should handle incomplete meta_data entries without errors.
	 */
	public function test_update_meta_data_with_incomplete_entries(): void {
		$order = wc_create_order();

		$this->sut->call_update_meta_data( $order, $this->get_incomplete_meta_data_input() );

		$this->assert_incomplete_meta_data_handled_correctly( $order );
	}

	/**
	 * @testdox Should skip meta entries where key is explicitly null.
	 */
	public function test_update_meta_data_with_explicit_null_key(): void {
		$order = wc_create_order();

		$this->sut->call_update_meta_data(
			$order,
			array(
				array(
					'key'   => null,
					'value' => 'null_key_value',
				),
				array(
					'key'   => 'valid_key',
					'value' => 'valid_value',
				),
			)
		);

		$meta_by_key = array();
		foreach ( $order->get_meta_data() as $meta ) {
			$meta_by_key[ $meta->key ] = $meta->value;
		}

		$this->assertEquals( 'valid_value', $meta_by_key['valid_key'] ?? null, 'Valid entry should be saved' );
		$this->assertArrayNotHasKey( '', $meta_by_key, 'Explicit null key should not create a meta data row' );
	}

	/**
	 * @testdox Should reject a new line item for a deleted product.
	 */
	public function test_prepare_line_item_data_rejects_deleted_product_on_create(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();
		$product->delete( true );

		$this->expectException( WC_REST_Exception::class );
		$this->expectExceptionMessage( 'Invalid product ID.' );

		$this->sut->call_prepare_line_item_data( array( 'product_id' => $product_id ) );
	}

	/**
	 * @testdox Should still update an existing line item whose product was deleted.
	 */
	public function test_prepare_line_item_data_updates_item_with_deleted_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$item    = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->save();
		$product_id = $product->get_id();
		$product->delete( true );

		$updated = $this->sut->call_prepare_line_item_data(
			array(
				'id'         => $item->get_id(),
				'product_id' => $product_id,
				'quantity'   => 3,
			),
			'update',
			$item
		);

		$this->assertSame( 3, $updated->get_quantity(), 'The quantity should be updated.' );
	}
}

/**
 * Testable subclass that exposes the protected update_meta_data method.
 *
 * phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
 * phpcs:disable SlevomatCodingStandard.Files.TypeNameMatchesFileName.NoMatchBetweenTypeNameAndFileName
 */
class TestableUpdateUtils extends UpdateUtils {

	/**
	 * Public wrapper for the protected update_meta_data method.
	 *
	 * @param WC_Order $order     Order object.
	 * @param array    $meta_data Meta data array.
	 */
	public function call_update_meta_data( WC_Order $order, array $meta_data ) {
		$this->update_meta_data( $order, $meta_data );
	}

	/**
	 * Public wrapper for the protected prepare_line_item_data method.
	 *
	 * @param array                      $request_data Line item data.
	 * @param string                     $action       'create' or 'update'.
	 * @param WC_Order_Item_Product|null $item         Item being updated.
	 * @return WC_Order_Item_Product
	 */
	public function call_prepare_line_item_data( array $request_data, string $action = 'create', ?WC_Order_Item_Product $item = null ): WC_Order_Item_Product {
		return $this->prepare_line_item_data( $request_data, $action, $item );
	}
}
