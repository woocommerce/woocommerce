<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services\CheckoutFieldsSchema;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Blocks\Package;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Opis\JsonSchema\{
	Validator,
	ValidationResult,
	Helper,
	Errors\ErrorFormatter,
};
use WC_Customer;

/**
 * DocumentObjectTests class.
 */
class DocumentObjectTests extends TestCase {
	/**
	 * Trait to use for the test_additional_fields_schema test.
	 *
	 * Technically, the logic of splitting fields lives in the CheckoutTrait class, but we need to use it here
	 * to test the DocumentObject class.
	 */
	use CheckoutTrait;

	/**
	 * Checkout fields controller.
	 * @var CheckoutFields
	 */
	protected $additional_fields_controller;

	/**
	 * Fixture data.
	 * @var FixtureData
	 */
	protected $fixtures;

	/**
	 * Products.
	 * @var array
	 */
	protected $products;

	/**
	 * Coupon.
	 * @var WC_Coupon
	 */
	protected $coupon;

	/**
	 * Setup the test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		// Needed for trait.
		$this->additional_fields_controller = Package::container()->get( CheckoutFields::class );

		$fixtures       = new FixtureData();
		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 3',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 4',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
					'virtual'       => true,
				)
			),
		);

		// Add product #3 as a cross-sell for product #1.
		$this->products[0]->set_cross_sell_ids( array( $this->products[2]->get_id() ) );
		$this->products[0]->save();

		$this->coupon = $fixtures->get_coupon(
			array(
				'code'          => 'test_coupon',
				'discount_type' => 'fixed_cart',
				'amount'        => 1,
			)
		);

		wc_empty_cart();
		$fixtures->shipping_add_flat_rate();
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		wc()->cart->add_to_cart( $this->products[1]->get_id() );
		wc()->cart->apply_coupon( $this->coupon->get_code() );
	}

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
		wc_empty_cart();
	}
	/**
	 * test_default_document_schema.
	 */
	public function test_default_document_schema() {
		$document_object = new DocumentObject();
		$document_object->set_customer( new WC_Customer( 0 ) );
		wc_empty_cart();
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
				'extensions'         => (object) [],
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
				'additional_fields' => (object) [],
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
				'additional_fields' => (object) [],
			]
		);
	}

	/**
	 * Ensures that additional fields in contact locations are under customer and order are under checkout.
	 */
	public function test_additional_fields_schema() {
		\woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'namespace/contact_field',
				'label'    => 'Contact Field',
				'location' => 'contact',
				'type'     => 'text',
			)
		);
		\woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'namespace/order_field',
				'label'    => 'Order Field',
				'location' => 'order',
				'type'     => 'text',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_param(
			'additional_fields',
			[
				'namespace/contact_field' => 'Contact field',
				'namespace/order_field'   => 'Order field',
			]
		);
		$document_object = $this->get_document_object_from_rest_request( $request );
		$document_object->set_customer( new WC_Customer( 0 ) );
		$data = $document_object->get_data();

		$this->assertEquals(
			$data['customer']['additional_fields'],
			[
				'namespace/contact_field' => 'Contact field',
			]
		);
		$this->assertEquals(
			$data['checkout']['additional_fields'],
			[
				'namespace/order_field' => 'Order field',
			]
		);

		$this->additional_fields_controller->deregister_checkout_field( 'namespace/contact_field' );
		$this->additional_fields_controller->deregister_checkout_field( 'namespace/order_field' );
	}

	/**
	 * Get the schema.
	 *
	 * @return array
	 */
	private function get_schema() {
		// Temporary because we can't fetch from the docs top level folder.
		$schema_path = ABSPATH . 'wp-content/plugins/woocommerce/src/Blocks/Domain/Services/CheckoutFieldsSchema/checkout-document-schema.json';
		return json_decode( file_get_contents( $schema_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Test that the document object matches the schema.
	 */
	public function test_document_object_matches_schema() {
		$document_object = new DocumentObject(
			[
				'checkout' => [
					'additional_fields' => [
						'namespace/order_field' => 'Order field',
					],
					'payment_method'    => 'stripe',
					'create_account'    => true,
					'customer_note'     => 'This is a test note',
				],
			]
		);
		$customer        = new WC_Customer( 0 );
		$customer->set_billing_first_name( 'John' );
		$customer->set_billing_last_name( 'Doe' );
		$customer->set_billing_email( 'john.doe@example.com' );
		$customer->set_billing_phone( '1234567890' );
		$customer->set_billing_address( '123 Main St, Anytown, USA' );
		$customer->set_billing_city( 'Anytown' );
		$customer->set_billing_state( 'CA' );
		$customer->set_billing_postcode( '12345' );
		$customer->set_billing_country( 'US' );

		$customer->set_shipping_first_name( 'John' );
		$customer->set_shipping_last_name( 'Doe' );
		$customer->set_shipping_address( '123 Main St, Anytown, USA' );
		$customer->set_shipping_city( 'Anytown' );
		$customer->set_shipping_state( 'CA' );
		$customer->set_shipping_postcode( '12345' );
		$customer->set_shipping_country( 'US' );
		$customer->set_shipping_phone( '1234567890' );

		$document_object->set_customer( $customer );

		$data = $document_object->get_data();

		$validator = new Validator();
		$result    = $validator->validate(
			Helper::toJSON( $data ),
			Helper::toJSON( $this->get_schema() )
		);

		$this->assertTrue( $result->isValid() );
	}
}
