<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods\Controller;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods\Schema\OfflinePaymentMethodSchema;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the Offline Payment Methods REST API controller.
 *
 * @class OfflinePaymentMethodsControllerTest
 */
class OfflinePaymentMethodsControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc/v4/settings/payments/offline-methods';

	/**
	 * @var Controller
	 */
	protected Controller $sut;

	/**
	 * @var MockObject|Payments
	 */
	protected $mock_payments_service;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->mock_payments_service = $this->getMockBuilder( Payments::class )
			->disableOriginalConstructor()
			->getMock();

		$schema = new OfflinePaymentMethodSchema();

		$this->sut = new Controller();
		$this->sut->init( $this->mock_payments_service, $schema );
		$this->sut->register_routes();
	}

	/**
	 * Test getting offline payment methods by a user without the needed capabilities.
	 */
	public function test_get_offline_payment_methods_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Test getting offline payment methods successfully.
	 */
	public function test_get_offline_payment_methods_success() {
		// Arrange.
		$mock_methods = $this->get_mock_offline_payment_methods();

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_payment_providers' )
			->with( 'US' )
			->willReturn( $mock_methods );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Verify top-level structure.
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'payment_methods', $data['groups'] );

		$methods = $data['groups']['payment_methods'];
		$this->assertCount( 3, $methods );

		// Verify structure of first offline payment method.
		$method = reset( $methods );
		$this->assertArrayHasKey( 'id', $method );
		$this->assertArrayHasKey( 'title', $method );
		$this->assertArrayHasKey( 'description', $method );
		$this->assertArrayHasKey( '_order', $method );
		$this->assertArrayHasKey( 'icon', $method );
		$this->assertArrayHasKey( 'state', $method );
		$this->assertArrayHasKey( 'management', $method );

		// Verify state structure.
		$this->assertArrayHasKey( 'enabled', $method['state'] );
		$this->assertArrayHasKey( 'needs_setup', $method['state'] );
		$this->assertArrayHasKey( 'test_mode', $method['state'] );

		// Verify management structure.
		$this->assertArrayHasKey( '_links', $method['management'] );
		$this->assertArrayHasKey( 'settings', $method['management']['_links'] );
		$this->assertArrayHasKey( 'href', $method['management']['_links']['settings'] );
	}

	/**
	 * Test getting offline payment methods without location parameter.
	 * Should fall back to store country.
	 */
	public function test_get_offline_payment_methods_without_location() {
		// Arrange.
		$mock_methods = $this->get_mock_offline_payment_methods();

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_country' )
			->willReturn( 'GB' );

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_payment_providers' )
			->with( 'GB' )
			->willReturn( $mock_methods );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'payment_methods', $data['groups'] );
		$this->assertCount( 3, $data['groups']['payment_methods'] );
	}

	/**
	 * Test getting offline payment methods with empty location.
	 * Should fall back to store country.
	 */
	public function test_get_offline_payment_methods_with_empty_location() {
		// Arrange.
		$mock_methods = $this->get_mock_offline_payment_methods();

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_country' )
			->willReturn( 'CA' );

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_payment_providers' )
			->with( 'CA' )
			->willReturn( $mock_methods );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'location', '' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'payment_methods', $data['groups'] );
		$this->assertCount( 3, $data['groups']['payment_methods'] );
	}

	/**
	 * Test getting offline payment methods when payment provider service throws exception.
	 */
	public function test_get_offline_payment_methods_with_service_exception() {
		// Arrange.
		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_country' )
			->willReturn( 'US' );

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_payment_providers' )
			->willThrowException( new \Exception( 'Service error' ) );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_payment_providers_error', $response->get_data()['code'] );
	}

	/**
	 * Test getting offline payment methods when no offline methods exist.
	 */
	public function test_get_offline_payment_methods_empty_results() {
		// Arrange.
		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_payment_providers' )
			->with( 'US' )
			->willReturn( array() );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'payment_methods', $data['groups'] );
		$this->assertCount( 0, $data['groups']['payment_methods'] );
	}

	/**
	 * Test getting offline payment methods filters out non-offline payment types.
	 */
	public function test_get_offline_payment_methods_filters_non_offline_types() {
		// Arrange.
		$mixed_providers = array_merge(
			$this->get_mock_offline_payment_methods(),
			array(
				array(
					'id'     => 'paypal',
					'_type'  => 'gateway',
					'_order' => 4,
					'title'  => 'PayPal',
				),
				array(
					'id'     => 'stripe_suggestion',
					'_type'  => 'suggestion',
					'_order' => 5,
					'title'  => 'Stripe',
				),
			)
		);

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_payment_providers' )
			->with( 'US' )
			->willReturn( $mixed_providers );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'payment_methods', $data['groups'] );

		// Should only return the 3 offline payment methods, not the gateway or suggestion.
		$this->assertCount( 3, $data['groups']['payment_methods'] );
	}

	/**
	 * Test that the schema is properly registered.
	 */
	public function test_schema_is_registered() {
		// Act.
		$schema = $this->sut->get_public_item_schema();

		// Assert.
		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertSame( 'offline_payment_method', $schema['title'] );
		$this->assertSame( 'object', $schema['type'] );

		// Verify key properties exist for the full response structure.
		$properties = $schema['properties'];
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'values', $properties );
		$this->assertArrayHasKey( 'groups', $properties );

		// Verify nested payment_methods structure exists.
		$this->assertArrayHasKey( 'properties', $properties['groups'] );
		$this->assertArrayHasKey( 'payment_methods', $properties['groups']['properties'] );
	}

	/**
	 * Get mock offline payment methods data.
	 *
	 * @return array
	 */
	private function get_mock_offline_payment_methods(): array {
		return array(
			array(
				'id'          => 'bacs',
				'_order'      => 1,
				'_type'       => 'offline_pm',
				'title'       => 'Direct bank transfer',
				'description' => 'Take payments in person via BACS.',
				'supports'    => array( 'products' ),
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => 'woocommerce/woocommerce.php',
					'status' => 'active',
				),
				'icon'        => 'http://example.com/bacs.svg',
				'state'       => array(
					'enabled'           => false,
					'account_connected' => false,
					'needs_setup'       => false,
					'test_mode'         => false,
					'dev_mode'          => false,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => 'http://example.com/wp-admin/admin.php?page=wc-settings&section=bacs',
						),
					),
				),
				'onboarding'  => array(
					'type'   => 'none',
					'state'  => array(
						'started'   => false,
						'completed' => false,
						'test_mode' => false,
					),
					'_links' => array(
						'onboard' => array(
							'href' => '',
						),
					),
				),
			),
			array(
				'id'          => 'cheque',
				'_order'      => 2,
				'_type'       => 'offline_pm',
				'title'       => 'Check payments',
				'description' => 'Take payments in person via checks.',
				'supports'    => array( 'products' ),
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => 'woocommerce/woocommerce.php',
					'status' => 'active',
				),
				'icon'        => 'http://example.com/cheque.svg',
				'state'       => array(
					'enabled'           => false,
					'account_connected' => false,
					'needs_setup'       => false,
					'test_mode'         => false,
					'dev_mode'          => false,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => 'http://example.com/wp-admin/admin.php?page=wc-settings&section=cheque',
						),
					),
				),
				'onboarding'  => array(
					'type'   => 'none',
					'state'  => array(
						'started'   => false,
						'completed' => false,
						'test_mode' => false,
					),
					'_links' => array(
						'onboard' => array(
							'href' => '',
						),
					),
				),
			),
			array(
				'id'          => 'cod',
				'_order'      => 3,
				'_type'       => 'offline_pm',
				'title'       => 'Cash on delivery',
				'description' => 'Pay with cash upon delivery.',
				'supports'    => array( 'products' ),
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => 'woocommerce/woocommerce.php',
					'status' => 'active',
				),
				'icon'        => 'http://example.com/cod.svg',
				'state'       => array(
					'enabled'           => false,
					'account_connected' => false,
					'needs_setup'       => false,
					'test_mode'         => false,
					'dev_mode'          => false,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => 'http://example.com/wp-admin/admin.php?page=wc-settings&section=cod',
						),
					),
				),
				'onboarding'  => array(
					'type'   => 'none',
					'state'  => array(
						'started'   => false,
						'completed' => false,
						'test_mode' => false,
					),
					'_links' => array(
						'onboard' => array(
							'href' => '',
						),
					),
				),
			),
		);
	}
}
