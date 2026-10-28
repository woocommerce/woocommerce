<?php
/**
 * Onboarding Product Types REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\API\OnboardingProductTypes;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProducts;

/**
 * WC Tests API Onboarding Product Types
 */
class WC_Admin_Tests_API_Onboarding_Product_Types extends WC_REST_Unit_Test_Case {

	/** Reuse only the baseline administrator; explicit actors stay per-test. */
	protected const USE_SHARED_ADMINISTRATOR_FIXTURE = true;

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/product-types';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'pre_http_request', array( $this, 'mock_product_catalog_response' ), 10, 3 );
		$this->user = self::get_shared_administrator_fixture_id();
		wp_set_current_user( $this->user );
	}

	/**
	 * Test that the allowed product types data is returned by the endpoint.
	 */
	public function test_get_product_types() {

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$properties = OnboardingProducts::get_allowed_product_types();
		foreach ( $properties as $key => $property ) {
			$this->assertArrayHasKey( $key, $data );
		}
	}

	/**
	 * Test subscriptions inclusion only for US stores.
	 */
	public function test_subscriptions_inclusion() {
		// The store location is: US.
		update_option( 'woocommerce_default_country', 'US' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( array_key_exists( 'product', $data['subscriptions'] ) );

		// Now the store location is: France.
		update_option( 'woocommerce_default_country', 'FR' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( array_key_exists( 'product', $data['subscriptions'] ) );

		// Now the store location is: Uruguay.
		update_option( 'woocommerce_default_country', 'UY' );

		$request_another_country  = new WP_REST_Request( 'GET', $this->endpoint );
		$response_another_country = $this->server->dispatch( $request_another_country );
		$data_another_country     = $response_another_country->get_data();

		$this->assertTrue( array_key_exists( 'product', $data_another_country['subscriptions'] ) );
	}

	/**
	 * Supply captured product fields for the real onboarding response parser.
	 *
	 * @param mixed  $preempt Existing HTTP preemption.
	 * @param array  $request HTTP request arguments.
	 * @param string $url HTTP request URL.
	 * @return mixed Existing preemption, fixture response, or false for another request.
	 */
	public function mock_product_catalog_response( $preempt, $request, $url ) {
		if ( false !== $preempt ) {
			return $preempt;
		}
		if ( 'woocommerce.com' !== wp_parse_url( $url, PHP_URL_HOST )
			|| '/wp-json/wccom-extensions/1.0/search' !== wp_parse_url( $url, PHP_URL_PATH )
			|| 'GET' !== ( $request['method'] ?? 'GET' ) ) {
			return false;
		}

		return array(
			'headers'  => array(
				'cache-for'    => '',
				'content-type' => 'application/json; charset=UTF-8',
			),
			'body'     => '{"products":[{"id":18618,"price":"&#36;79","excerpt":"Offer add-ons like gift wrapping, special messages or other special options for your products.","link":"https://woocommerce.com/products/product-add-ons/?utm_source=extensionsscreen&utm_medium=product&utm_campaign=wcaddons","slug":"woocommerce-product-addons"},{"id":27147,"price":"&#36;279","excerpt":"WooCommerce Subscriptions is a WooCommerce extension that lets customers subscribe to your products or services and pay on a weekly, monthly, or annual basis. It integrates with over 25 payment gateways for automatic recurring payments and supports automatic rebilling on failed subscription payments. Subscribers can manage their own plan — upgrading, downgrading, suspending, or canceling — and update their shipping address or payment method. It\'s a paid annual extension with a 30-day money-back guarantee.","link":"https://woocommerce.com/products/woocommerce-subscriptions/?utm_source=extensionsscreen&utm_medium=product&utm_campaign=wcaddons","slug":"woocommerce-subscriptions"},{"id":18716,"price":"&#36;79","excerpt":"Offer personalized product bundles, bulk discount packages, and assembled products.","link":"https://woocommerce.com/products/product-bundles/?utm_source=extensionsscreen&utm_medium=product&utm_campaign=wcaddons","slug":"woocommerce-product-bundles"},{"id":390890,"price":"&#36;249","excerpt":"WooCommerce Bookings is the official WooCommerce extension that lets store owners sell appointments, reservations, rentals, or classes with bookable time or date slots. It supports fixed time slots or flexible ranges, group and individual pricing, and resources — staff, rooms, or equipment — each with its own availability to prevent double-booking. It syncs with Google Calendar and works with any WooCommerce payment gateway. It\'s a paid annual extension with a 30-day money-back guarantee.","link":"https://woocommerce.com/products/woocommerce-bookings/?utm_source=extensionsscreen&utm_medium=product&utm_campaign=wcaddons","slug":"woocommerce-bookings"},{"id":958589,"price":"&#36;199","excerpt":"Power your membership association, online magazine, elearning sites, and more with access control to content/products and member discounts.","link":"https://woocommerce.com/products/woocommerce-memberships/?utm_source=extensionsscreen&utm_medium=product&utm_campaign=wcaddons","slug":"woocommerce-memberships"}]}',
			'response' => array(
				'code' => 200,
			),
		);
	}
}
