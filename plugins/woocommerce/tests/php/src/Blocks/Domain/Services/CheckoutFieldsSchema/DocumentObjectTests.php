<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services\CheckoutFieldsSchema;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use WC_Customer;

/**
 * DocumentObjectTests class.
 */
class DocumentObjectTests extends TestCase {
	/**
	 * test_validate_selected_shipping_methods.
	 */
	public function test_default_document_schema() {
		$document_object = new DocumentObject();
		$document_object->set_customer( new WC_Customer( 0 ) );
		$data = $document_object->get_data();

		$this->assertEquals(
			$data['cart'],
			[
				'coupons'            => [],
				'shipping_rates'     => [],
				'items'              => [],
				'items_type'         => [],
				'items_count'        => 0,
				'items_weight'       => 0,
				'needs_shipping'     => false,
				'prefers_collection' => false,
				'totals'             => [
					'total_price' => 0,
					'total_tax'   => 0,
				],
				'extensions'         => [],
			]
		);
		$this->assertEquals(
			$data['customer'],
			[
				'id'                => 0,
				'shipping_address'  => [
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => '',
					'postcode'   => '',
					'country'    => '',
					'phone'      => '',
				],
				'billing_address'   => [
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => '',
					'postcode'   => '',
					'country'    => '',
					'email'      => '',
					'phone'      => '',
				],
				'additional_fields' => [],
			]
		);
		$this->assertEquals( $data['checkout'], [] );
	}

	/**
	 * test_override_document_schema.
	 */
	public function test_override_document_schema() {
		$document_object = new DocumentObject(
			[
				'customer' => [
					'id'               => 1,
					'shipping_address' => [
						'first_name' => 'John',
						'last_name'  => 'Doe',
					],
					'billing_address'  => [
						'first_name' => 'Jane',
						'last_name'  => 'Doe',
					],
				],
			]
		);
		$document_object->set_customer( new WC_Customer( 0 ) );
		$data = $document_object->get_data();
		$this->assertEquals(
			$data['customer'],
			[
				'id'                => 1,
				'shipping_address'  => [
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => '',
					'postcode'   => '',
					'country'    => '',
					'phone'      => '',
				],
				'billing_address'   => [
					'first_name' => 'Jane',
					'last_name'  => 'Doe',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => '',
					'postcode'   => '',
					'country'    => '',
					'email'      => '',
					'phone'      => '',
				],
				'additional_fields' => [],
			]
		);
	}
}
