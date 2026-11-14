<?php
/**
 * Controller tests for the cart update-customer route.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Behavioural tests for POST /cart/update-customer.
 *
 * Exercises scenarios for updating customer billing and shipping addresses:
 * - Fully updating both addresses in a single request.
 * - Partially updating both addresses while ensuring unprovided fields are preserved.
 * - Updating custom additional checkout fields on both addresses in a single request.
 * - Validation and error handling when invalid address parameters are supplied.
 */
class CartUpdateCustomer extends ControllerTestCase {

	/**
	 * Initial billing address values before each test.
	 *
	 * @var array<string, string>
	 */
	private array $initial_billing = array(
		'first_name' => 'InitialFirst',
		'last_name'  => 'InitialLast',
		'company'    => 'Initial Corp',
		'address_1'  => '100 Initial Way',
		'address_2'  => 'Apt 10',
		'city'       => 'Sacramento',
		'state'      => 'CA',
		'postcode'   => '95814',
		'country'    => 'US',
		'email'      => 'initial.shopper@example.test',
		'phone'      => '555-0101',
	);

	/**
	 * Initial shipping address values before each test.
	 *
	 * @var array<string, string>
	 */
	private array $initial_shipping = array(
		'first_name' => 'InitialShipFirst',
		'last_name'  => 'InitialShipLast',
		'company'    => 'Initial Logistics',
		'address_1'  => '200 Initial Freight Rd',
		'address_2'  => 'Suite 20',
		'city'       => 'Albany',
		'state'      => 'NY',
		'postcode'   => '12207',
		'country'    => 'US',
		'phone'      => '555-0202',
	);

	/**
	 * Additional checkout field IDs registered during tests.
	 *
	 * @var string[]
	 */
	private array $registered_field_ids = array();

	/**
	 * Set up test fixtures with a shippable product and known initial customer addresses.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Shippable Product',
				'regular_price' => 25,
				'weight'        => 2,
			)
		);

		wc_empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		WC()->customer->set_props(
			array(
				'billing_first_name'  => $this->initial_billing['first_name'],
				'billing_last_name'   => $this->initial_billing['last_name'],
				'billing_company'     => $this->initial_billing['company'],
				'billing_address_1'   => $this->initial_billing['address_1'],
				'billing_address_2'   => $this->initial_billing['address_2'],
				'billing_city'        => $this->initial_billing['city'],
				'billing_state'       => $this->initial_billing['state'],
				'billing_postcode'    => $this->initial_billing['postcode'],
				'billing_country'     => $this->initial_billing['country'],
				'billing_email'       => $this->initial_billing['email'],
				'billing_phone'       => $this->initial_billing['phone'],
				'shipping_first_name' => $this->initial_shipping['first_name'],
				'shipping_last_name'  => $this->initial_shipping['last_name'],
				'shipping_company'    => $this->initial_shipping['company'],
				'shipping_address_1'  => $this->initial_shipping['address_1'],
				'shipping_address_2'  => $this->initial_shipping['address_2'],
				'shipping_city'       => $this->initial_shipping['city'],
				'shipping_state'      => $this->initial_shipping['state'],
				'shipping_postcode'   => $this->initial_shipping['postcode'],
				'shipping_country'    => $this->initial_shipping['country'],
				'shipping_phone'      => $this->initial_shipping['phone'],
			)
		);
		WC()->customer->save();
	}

	/**
	 * Tear down test fixtures and clean up registered checkout fields.
	 */
	protected function tearDown(): void {
		try {
			foreach ( $this->registered_field_ids as $field_id ) {
				__internal_woocommerce_blocks_deregister_checkout_field( $field_id );
			}
			$this->registered_field_ids = array();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Post update-customer payload and return the decoded response.
	 *
	 * @param array $body Request body parameters.
	 * @return array{status:int,data:array}
	 */
	private function update_customer( array $body ): array {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( $body );
		$response = rest_get_server()->dispatch( $request );

		return array(
			'status' => $response->get_status(),
			'data'   => $response->get_data(),
		);
	}

	/**
	 * @testdox Fully updating both billing and shipping addresses in a single request updates the customer and cart response.
	 */
	public function test_fully_updating_both_addresses_in_single_request(): void {
		$new_billing = array(
			'first_name' => 'Bruce',
			'last_name'  => 'Wayne',
			'company'    => 'Wayne Enterprises',
			'address_1'  => '1007 Mountain Drive',
			'address_2'  => 'Suite 1',
			'city'       => 'Gotham',
			'state'      => 'NY',
			'postcode'   => '10001',
			'country'    => 'US',
			'email'      => 'bruce@wayne-enterprises.test',
			'phone'      => '555-0199',
		);

		$new_shipping = array(
			'first_name' => 'Clark',
			'last_name'  => 'Kent',
			'company'    => 'Daily Planet',
			'address_1'  => '344 Clinton Street',
			'address_2'  => 'Apt 3B',
			'city'       => 'Metropolis',
			'state'      => 'IL',
			'postcode'   => '62960',
			'country'    => 'US',
			'phone'      => '555-0188',
		);

		$captured_customer = null;
		add_action(
			'woocommerce_store_api_cart_update_customer_from_request',
			function ( $customer ) use ( &$captured_customer ) {
				$captured_customer = $customer;
			}
		);

		$result = $this->update_customer(
			array(
				'billing_address'  => $new_billing,
				'shipping_address' => $new_shipping,
			)
		);

		$this->assertSame( 200, $result['status'], 'Updating both addresses with valid data should succeed.' );
		$this->assertSame( WC()->customer, $captured_customer, 'Fired action should receive current customer instance.' );

		$response_billing  = (array) $result['data']['billing_address'];
		$response_shipping = (array) $result['data']['shipping_address'];

		$this->assertSame( $new_billing, array_intersect_key( $response_billing, $new_billing ) );
		$this->assertSame( $new_shipping, array_intersect_key( $response_shipping, $new_shipping ) );

		$this->assertSame( $new_billing['first_name'], WC()->customer->get_billing_first_name() );
		$this->assertSame( $new_billing['city'], WC()->customer->get_billing_city() );
		$this->assertSame( $new_billing['email'], WC()->customer->get_billing_email() );
		$this->assertSame( $new_shipping['first_name'], WC()->customer->get_shipping_first_name() );
		$this->assertSame( $new_shipping['city'], WC()->customer->get_shipping_city() );
		$this->assertSame( $new_shipping['postcode'], WC()->customer->get_shipping_postcode() );
	}

	/**
	 * @testdox Partially updating both addresses in a single request modifies only targeted fields and preserves unprovided fields.
	 */
	public function test_partially_updating_both_addresses_preserves_unprovided_fields(): void {
		$partial_billing = array(
			'address_1' => '789 Updated Boulevard',
			'city'      => 'San Francisco',
		);

		$partial_shipping = array(
			'company' => 'Updated Express Carriers',
			'phone'   => '555-9876',
		);

		$result = $this->update_customer(
			array(
				'billing_address'  => $partial_billing,
				'shipping_address' => $partial_shipping,
			)
		);

		$this->assertSame( 200, $result['status'], 'Partial update of both addresses should succeed.' );

		$response_billing  = (array) $result['data']['billing_address'];
		$response_shipping = (array) $result['data']['shipping_address'];

		$expected_billing  = array_merge( $this->initial_billing, $partial_billing );
		$expected_shipping = array_merge( $this->initial_shipping, $partial_shipping );

		$this->assertSame( $expected_billing, array_intersect_key( $response_billing, $expected_billing ) );
		$this->assertSame( $expected_shipping, array_intersect_key( $response_shipping, $expected_shipping ) );

		$this->assertSame( '789 Updated Boulevard', WC()->customer->get_billing_address_1() );
		$this->assertSame( 'San Francisco', WC()->customer->get_billing_city() );
		$this->assertSame( $this->initial_billing['email'], WC()->customer->get_billing_email() );
		$this->assertSame( 'Updated Express Carriers', WC()->customer->get_shipping_company() );
		$this->assertSame( '555-9876', WC()->customer->get_shipping_phone() );
		$this->assertSame( $this->initial_shipping['city'], WC()->customer->get_shipping_city() );
	}

	/**
	 * @testdox Updating additional checkout fields for both addresses in the same request persists and returns both values.
	 */
	public function test_updating_additional_checkout_fields_for_both_addresses_in_same_request(): void {
		$billing_field_id  = 'woocommerce-test/billing-tax-identifier';
		$shipping_field_id = 'woocommerce-test/shipping-delivery-dock';

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => $billing_field_id,
				'label'    => 'Billing Tax Identifier',
				'location' => 'address',
				'type'     => 'text',
			)
		);
		$this->registered_field_ids[] = $billing_field_id;

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => $shipping_field_id,
				'label'    => 'Shipping Delivery Dock',
				'location' => 'address',
				'type'     => 'text',
			)
		);
		$this->registered_field_ids[] = $shipping_field_id;

		$checkout_fields = Package::container()->get( CheckoutFields::class );

		$result = $this->update_customer(
			array(
				'billing_address'  => array( $billing_field_id => 'TAX-ID-998877' ),
				'shipping_address' => array( $shipping_field_id => 'DOCK-BAY-4' ),
			)
		);

		$this->assertSame( 200, $result['status'], 'Request with additional checkout fields on both addresses should succeed.' );

		$response_billing  = (array) $result['data']['billing_address'];
		$response_shipping = (array) $result['data']['shipping_address'];

		$this->assertArrayHasKey( $billing_field_id, $response_billing );
		$this->assertSame( 'TAX-ID-998877', $response_billing[ $billing_field_id ] );

		$this->assertArrayHasKey( $shipping_field_id, $response_shipping );
		$this->assertSame( 'DOCK-BAY-4', $response_shipping[ $shipping_field_id ] );

		$this->assertSame(
			'TAX-ID-998877',
			$checkout_fields->get_field_from_object( $billing_field_id, WC()->customer, 'billing' ),
			'Billing additional field should be persisted to the customer.'
		);
		$this->assertSame(
			'DOCK-BAY-4',
			$checkout_fields->get_field_from_object( $shipping_field_id, WC()->customer, 'shipping' ),
			'Shipping additional field should be persisted to the customer.'
		);
	}

	/**
	 * @testdox Invalid parameters across both addresses return a 400 Bad Request listing the invalid fields.
	 */
	public function test_invalid_parameters_across_both_addresses_returns_400(): void {
		$result = $this->update_customer(
			array(
				'billing_address'  => array(
					'country' => 'INVALID_COUNTRY',
				),
				'shipping_address' => array(
					'country' => 'US',
					'state'   => 'INVALID_STATE',
				),
			)
		);

		$this->assertSame( 400, $result['status'], 'Invalid country or state across addresses should be rejected.' );
		$this->assertSame( 'rest_invalid_param', $result['data']['code'] );
		$this->assertArrayHasKey( 'billing_address', $result['data']['data']['params'] );
		$this->assertArrayHasKey( 'shipping_address', $result['data']['data']['params'] );
	}
}
