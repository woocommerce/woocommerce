<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsRestController;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Gateway_PayPal;

/**
 * PaymentsRestController API controller test.
 *
 * @class PaymentsRestController
 */
class PaymentsRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/settings/payments';

	/**
	 * @var PaymentsRestController
	 */
	protected PaymentsRestController $sut;

	/**
	 * @var MockObject|Payments
	 */
	protected $mock_service;

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

		$this->mock_service = $this->getMockBuilder( Payments::class )->getMock();

		$this->sut = new PaymentsRestController();
		$this->sut->init( $this->mock_service );
		$this->sut->register_routes( true );
	}

	/**
	 * Test getting payment providers by a user without the needed capabilities.
	 */
	public function test_get_payment_providers_by_user_without_caps() {
		// Arrange.
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => false,
			'install_plugins'    => false,
		);
		add_filter( 'user_has_cap', $filter_callback );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting payment providers by a user without the capability to install plugins.
	 *
	 * This means no suggestions are returned.
	 */
	public function test_get_payment_providers_by_manager_without_install_plugins_cap() {
		// Arrange.
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true,
			'install_plugins'    => false,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->mock_providers();
		$this->mock_extension_suggestions();
		$this->mock_extension_suggestions_categories();

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have the core PayPal gateway registered and the offline PMs group entry.
		$this->assertCount( 2, $data['providers'] );
		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// No suggestions are returned because the user can't install plugins.
		$this->assertCount( 0, $data['suggestions'] );
		// But we do get the suggestion categories.
		$this->assertCount( 3, $data['suggestion_categories'] );

		// Assert that the core PayPal gateway has all the details.
		$provider = $data['providers'][1];
		$this->assertArrayHasKey( 'id', $provider, 'Provider (gateway) `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $provider, 'Provider (gateway) `_order` entry is missing' );
		$this->assertArrayHasKey( 'title', $provider, 'Provider (gateway) `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $provider, 'Provider (gateway) `description` entry is missing' );
		$this->assertArrayHasKey( 'supports', $provider, 'Provider (gateway) `supports` entry is missing' );
		$this->assertIsList( $provider['supports'], 'Provider (gateway) `supports` entry is not a list' );
		$this->assertArrayHasKey( 'state', $provider, 'Provider (gateway) `state` entry is missing' );
		$this->assertArrayHasKey( 'enabled', $provider['state'], 'Provider (gateway) `state[enabled]` entry is missing' );
		$this->assertArrayHasKey( 'needs_setup', $provider['state'], 'Provider (gateway) `state[needs_setup]` entry is missing' );
		$this->assertArrayHasKey( 'test_mode', $provider['state'], 'Provider (gateway) `state[test_mode]` entry is missing' );
		$this->assertArrayHasKey( 'management', $provider, 'Gateway `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $provider['management'], 'Gateway `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $provider['management']['_links'], 'Gateway `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'links', $provider, 'Provider (gateway) `links` entry is missing' );
		$this->assertCount( 1, $provider['links'] );
		$this->assertArrayHasKey( 'plugin', $provider, 'Provider (gateway) `plugin` entry is missing' );
		$this->assertArrayHasKey( 'slug', $provider['plugin'], 'Provider (gateway) `plugin[slug]` entry is missing' );
		$this->assertArrayHasKey( 'file', $provider['plugin'], 'Provider (gateway) `plugin[file]` entry is missing' );
		$this->assertArrayHasKey( 'status', $provider['plugin'], 'Provider (gateway) `plugin[status]` entry is missing' );
		$this->assertArrayHasKey( '_links', $provider, 'Provider (gateway) `_links` entry is missing' );

		// Assert that the offline payment methods have all the details.
		$offline_pm = $data['offline_payment_methods'][0];
		$this->assertArrayHasKey( 'id', $offline_pm, 'Offline payment method `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $offline_pm, 'Offline payment method `_order` entry is missing' );
		$this->assertArrayHasKey( 'title', $offline_pm, 'Offline payment method `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $offline_pm, 'Offline payment method `description` entry is missing' );
		$this->assertArrayHasKey( 'state', $offline_pm, 'Offline payment method `state` entry is missing' );
		$this->assertArrayHasKey( 'enabled', $offline_pm['state'], 'Offline payment method `state[enabled]` entry is missing' );
		$this->assertArrayHasKey( 'needs_setup', $offline_pm['state'], 'Offline payment method `state[needs_setup]` entry is missing' );
		$this->assertArrayHasKey( 'management', $offline_pm, 'Offline payment method `management` entry is missing' );
		$this->assertArrayHasKey( 'icon', $offline_pm, 'Offline payment method `icon` entry is missing' );

		// Assert that the suggestion categories have all the details.
		$suggestion_category = $data['suggestion_categories'][0];
		$this->assertArrayHasKey( 'id', $suggestion_category, 'Suggestion category `id` entry is missing' );
		$this->assertArrayHasKey( '_priority', $suggestion_category, 'Suggestion category `_order` entry is missing' );
		$this->assertArrayHasKey( 'title', $suggestion_category, 'Suggestion category `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $suggestion_category, 'Suggestion category `description` entry is missing' );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting payment providers by a user with the capability to install plugins.
	 *
	 * This means suggestions are returned.
	 */
	public function test_get_payment_providers_by_manager_with_install_plugins_cap() {
		// Arrange.
		$this->mock_providers();
		$this->mock_extension_suggestions( 'US' );
		$this->mock_extension_suggestions_categories();

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true,
			'install_plugins'    => true,
		);
		add_filter( 'user_has_cap', $filter_callback );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have the core PayPal gateway registered, the offline PMs group entry, and the 2 preferred suggestions.
		$this->assertCount( 4, $data['providers'] );
		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// Suggestions are returned because the user can install plugins.
		$this->assertCount( 2, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 3, $data['suggestion_categories'] );

		// Assert that we have the right providers, in the right order.
		$this->assertSame(
			array(
				'_wc_pes_woopayments',
				'_wc_pes_paypal_full_stack',
				PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
				WC_Gateway_Paypal::ID,
			),
			array_column( $data['providers'], 'id' )
		);

		// Assert that the suggestions have all the details.
		foreach ( $data['suggestions'] as $suggestion ) {
			$this->assertArrayHasKey( 'id', $suggestion, 'Suggestion `id` entry is missing' );
			$this->assertArrayHasKey( '_priority', $suggestion, 'Suggestion `_priority` entry is missing' );
			$this->assertIsInteger( $suggestion['_priority'], 'Suggestion `_priority` entry is not an integer' );
			$this->assertArrayHasKey( '_type', $suggestion, 'Suggestion `_type` entry is missing' );
			$this->assertArrayHasKey( 'title', $suggestion, 'Suggestion `title` entry is missing' );
			$this->assertArrayHasKey( 'description', $suggestion, 'Suggestion `description` entry is missing' );
			$this->assertArrayHasKey( 'plugin', $suggestion, 'Suggestion `plugin` entry is missing' );
			$this->assertArrayHasKey( '_type', $suggestion['plugin'], 'Suggestion `plugin[_type]` entry is missing' );
			$this->assertArrayHasKey( 'slug', $suggestion['plugin'], 'Suggestion `plugin[slug]` entry is missing' );
			$this->assertArrayHasKey( 'status', $suggestion['plugin'], 'Suggestion `plugin[status]` entry is missing' );
			$this->assertArrayHasKey( 'icon', $suggestion, 'Suggestion `icon` entry is missing' );
			$this->assertArrayHasKey( 'links', $suggestion, 'Suggestion `links` entry is missing' );
			$this->assertIsArray( $suggestion['links'] );
			$this->assertNotEmpty( $suggestion['links'] );
			$this->assertArrayHasKey( '_type', $suggestion['links'][0], 'Suggestion `link[_type]` entry is missing' );
			$this->assertArrayHasKey( 'url', $suggestion['links'][0], 'Suggestion `link[url]` entry is missing' );
			$this->assertArrayHasKey( 'tags', $suggestion, 'Suggestion `tags` entry is missing' );
			$this->assertIsArray( $suggestion['tags'] );
			$this->assertArrayHasKey( 'category', $suggestion, 'Suggestion `category` entry is missing' );
		}

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting payment providers with an enabled payment gateway.
	 *
	 * This means suggestions are returned.
	 */
	public function test_get_payment_providers_with_enabled_pg() {
		// Arrange.
		$this->mock_providers( false, false, false, true );
		$this->mock_extension_suggestions( 'US' );
		$this->mock_extension_suggestions_categories();

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have the core PayPal gateway registered, the offline PMs group entry, and the 2 preferred suggestions.
		$this->assertCount( 4, $data['providers'] );
		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// Suggestions are returned because the user can install plugins.
		$this->assertCount( 2, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 3, $data['suggestion_categories'] );

		// Assert that we have the right providers, in the right order.
		$this->assertSame(
			array(
				'_wc_pes_woopayments',
				'_wc_pes_paypal_full_stack',
				PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
				WC_Gateway_Paypal::ID,
			),
			array_column( $data['providers'], 'id' )
		);

		// Assert that the PayPal gateway is returned as enabled.
		$gateway = $data['providers'][3];
		$this->assertTrue( $gateway['state']['enabled'] );
	}

	/**
	 * Test getting payment providers with no registered offline PMs.
	 */
	public function test_get_payment_providers_without_offline_pms() {
		// Arrange.
		$this->mock_providers( false, true, false, true );
		$this->mock_extension_suggestions( 'US' );
		$this->mock_extension_suggestions_categories();

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have the core PayPal gateway registered, and the 2 preferred suggestions.
		// There is no offline PMs group entry because there are no offline PMs.
		$this->assertCount( 3, $data['providers'] );
		// We have no offline payment methods.
		$this->assertCount( 0, $data['offline_payment_methods'] );
		// Suggestions are returned because the user can install plugins.
		$this->assertCount( 2, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 3, $data['suggestion_categories'] );

		// Assert that we have the right providers, in the right order.
		$this->assertSame(
			array(
				'_wc_pes_woopayments',
				'_wc_pes_paypal_full_stack',
				WC_Gateway_Paypal::ID,
			),
			array_column( $data['providers'], 'id' )
		);

		// Assert that the PayPal gateway is returned as enabled.
		$gateway = $data['providers'][2];
		$this->assertTrue( $gateway['state']['enabled'] );
	}

	/**
	 * Test getting payment providers with no suggestions.
	 */
	public function test_get_payment_providers_without_suggestions() {
		// Arrange.
		$this->mock_providers( true );
		$this->mock_extension_suggestions_categories();

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have the core PayPal gateway registered, and the offline PMs group entry.
		$this->assertCount( 2, $data['providers'] );
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// No suggestions.
		$this->assertCount( 0, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 3, $data['suggestion_categories'] );

		// Assert that we have the right providers, in the right order.
		$this->assertSame(
			array(
				PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
				WC_Gateway_Paypal::ID,
			),
			array_column( $data['providers'], 'id' )
		);
	}

	/**
	 * Test getting payment providers with no registered payment gateways (regular or offline PM).
	 */
	public function test_get_payment_providers_without_any_pgs() {
		// Arrange.
		$this->mock_providers( false, true, true );
		$this->mock_extension_suggestions( 'US' );
		$this->mock_extension_suggestions_categories();

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have only the 2 preferred suggestions.
		// There is no offline PMs group entry because there are no offline PMs.
		$this->assertCount( 2, $data['providers'] );
		// We have no offline payment methods.
		$this->assertCount( 0, $data['offline_payment_methods'] );
		// Suggestions are returned because the user can install plugins.
		$this->assertCount( 2, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 3, $data['suggestion_categories'] );

		// Assert that we have the right providers, in the right order.
		$this->assertSame(
			array(
				'_wc_pes_woopayments',
				'_wc_pes_paypal_full_stack',
			),
			array_column( $data['providers'], 'id' )
		);
	}

	/**
	 * Test getting payment providers without specifying a location.
	 *
	 * It should default to the store location.
	 */
	public function test_get_payment_providers_with_no_location() {
		// Arrange.
		$this->mock_providers();
		$this->mock_extension_suggestions( 'LI' );
		$this->mock_extension_suggestions_categories();

		update_option( 'woocommerce_default_country', 'LI' ); // Liechtenstein.

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have the core PayPal gateway registered, the offline PMs group entry, and the 2 preferred suggestions.
		$this->assertCount( 4, $data['providers'] );
		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// Suggestions are returned because the user can install plugins.
		// We get all the suggestions.
		$this->assertCount( 2, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 3, $data['suggestion_categories'] );

		// Clean up.
		delete_option( 'woocommerce_default_country' );
	}

	/**
	 * Test getting payment providers with invalid location.
	 */
	public function test_get_payment_providers_with_invalid_location() {
		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'U' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', '12' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'USA' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test updating payment providers order with an invalid order map.
	 *
	 * @dataProvider data_provider_update_providers_order_args_check_failures
	 *
	 * @param mixed $order_map The order map to test.
	 */
	public function test_update_providers_order_args_check_failures( $order_map ) {
		// Arrange.
		$this->mock_service
			->expects( $this->never() )
			->method( 'update_payment_providers_order_map' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers/order' );
		$request->set_body_params(
			array(
				'order_map' => $order_map,
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Data provider for test_update_providers_order_args_check_failures.
	 */
	public function data_provider_update_providers_order_args_check_failures(): array {
		return array(
			array( 1 ),
			array( false ),
			array( 0 => WC_Gateway_Paypal::ID ),
			array( array( WC_Gateway_Paypal::ID ) ),
			array( array( WC_Gateway_Paypal::ID => false ) ),
			array( array( WC_Gateway_Paypal::ID => 'bogus' ) ),
			array( array( WC_Gateway_Paypal::ID => '1.0' ) ),
			array( array( '()/paypal%#' => 1 ) ),
			array(
				array(
					WC_Gateway_Paypal::ID     => '1.1',
					'offline_payment_methods' => 2,
				),
			),
			array(
				array(
					WC_Gateway_Paypal::ID     => '0.1',
					'offline_payment_methods' => 2,
				),
			),
			array(
				array(
					WC_Gateway_Paypal::ID => 1,
					'offline_payment_methods',
				),
			),
		);
	}

	/**
	 * Test updating payment providers order with no order_map param.
	 */
	public function test_update_providers_order_empty_order_map() {
		// Arrange.
		$this->mock_service
			->expects( $this->never() )
			->method( 'update_payment_providers_order_map' );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers/order' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test updating payment providers order when no update is made.
	 */
	public function test_update_providers_order_no_update() {
		// Arrange.
		$this->mock_service
			->expects( $this->once() )
			->method( 'update_payment_providers_order_map' )
			->willReturn( false );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers/order' );
		$request->set_body_params(
			array(
				'order_map' => array(
					'provider1' => 1,
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
	}

	/**
	 * Test updating payment providers order when an update happened.
	 */
	public function test_update_providers_order_updates() {
		// Arrange.
		$this->mock_service
			->expects( $this->once() )
			->method( 'update_payment_providers_order_map' )
			->willReturn( true );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers/order' );
		$request->set_body_params(
			array(
				'order_map' => array(
					'provider1' => 1,
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * Test updating payment providers order by a user without the proper capabilities.
	 */
	public function test_update_providers_order_user_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Assert.
		$this->mock_service
			->expects( $this->never() )
			->method( 'update_payment_providers_order_map' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers/order' );
		$request->set_param( 'order_map', array( 'provider1' => 1 ) );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Test hiding a payment extension suggestion.
	 */
	public function test_hide_payment_extension_suggestion() {
		// Arrange.
		$this->mock_service
			->expects( $this->once() )
			->method( 'hide_payment_extension_suggestion' )
			->with( 'suggestion_id' )
			->willReturn( true );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/suggestion_id/hide' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * Test hiding a payment extension suggestion that fails.
	 */
	public function test_hide_payment_extension_suggestion_failure() {
		// Arrange.
		$this->mock_service
			->expects( $this->once() )
			->method( 'hide_payment_extension_suggestion' )
			->with( 'suggestion_id' )
			->willReturn( false );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/suggestion_id/hide' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
	}

	/**
	 * Test hiding a payment extension suggestion with an invalid suggestion ID.
	 */
	public function test_hide_payment_extension_suggestion_invalid_suggestion_id() {
		// Arrange.
		$this->mock_service
			->expects( $this->once() )
			->method( 'hide_payment_extension_suggestion' )
			->with( 'suggestion_id' )
			->willThrowException( new \Exception() );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/suggestion_id/hide' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test hiding a payment extension suggestion by a user without the proper capabilities.
	 */
	public function test_hide_payment_extension_suggestion_user_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Assert.
		$this->mock_service
			->expects( $this->never() )
			->method( 'hide_payment_extension_suggestion' );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/suggestion_id/hide' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Test dismissing a payment extension suggestion incentive.
	 */
	public function test_dismiss_payment_extension_suggestion_incentive() {
		// Arrange.
		$incentive_id  = 'incentive_id';
		$suggestion_id = 'suggestion_id';

		$this->mock_service
			->expects( $this->once() )
			->method( 'dismiss_extension_suggestion_incentive' )
			->with( $suggestion_id, $incentive_id )
			->willReturn( true );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/' . $suggestion_id . '/incentive/' . $incentive_id . '/dismiss' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * Test dismissing a payment extension suggestion incentive that fails.
	 */
	public function test_dismiss_payment_extension_suggestion_incentive_failure() {
		// Arrange.
		$incentive_id  = 'incentive_id';
		$suggestion_id = 'suggestion_id';

		$this->mock_service
			->expects( $this->once() )
			->method( 'dismiss_extension_suggestion_incentive' )
			->with( $suggestion_id, $incentive_id )
			->willReturn( false );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/' . $suggestion_id . '/incentive/' . $incentive_id . '/dismiss' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
	}

	/**
	 * Test dismissing a payment extension suggestion incentive that throws an exception.
	 */
	public function test_dismiss_payment_extension_suggestion_incentive_exception() {
		// Arrange.
		$incentive_id  = 'incentive_id';
		$suggestion_id = 'suggestion_id';

		$this->mock_service
			->expects( $this->once() )
			->method( 'dismiss_extension_suggestion_incentive' )
			->with( $suggestion_id, $incentive_id )
			->willThrowException( new \Exception() );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/' . $suggestion_id . '/incentive/' . $incentive_id . '/dismiss' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test dismissing a payment extension suggestion incentive by a user without the proper capabilities.
	 */
	public function test_dismiss_payment_extension_suggestion_incentive_user_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Assert.
		$this->mock_service
			->expects( $this->never() )
			->method( 'dismiss_extension_suggestion_incentive' );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/suggestion_id/incentive/incentive_id/dismiss' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Mock the providers.
	 *
	 * @param bool $skip_suggestions       Whether to not include the suggestions.
	 * @param bool $skip_offline_pms       Whether to not include the offline payment methods.
	 * @param bool $skip_paypal            Whether to not include the PayPal gateway.
	 * @param bool $enabled_core_paypal_pg Whether the core PayPal gateway is enabled or not.
	 */
	private function mock_providers( bool $skip_suggestions = false, bool $skip_offline_pms = false, bool $skip_paypal = false, bool $enabled_core_paypal_pg = false ) {
		$mock_providers = array();
		$order          = 0;
		if ( ! $skip_suggestions && current_user_can( 'install_plugins' ) ) {
			$mock_providers[] = array(
				'id'                => '_wc_pes_woopayments',
				'_order'            => $order++,
				'_type'             => PaymentProviders::TYPE_SUGGESTION,
				'title'             => 'Accept payments with Woo',
				'description'       => 'With WooPayments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies. Track cash flow and manage recurring revenue directly from your store’s dashboard - with no setup costs or monthly fees.',
				'short_description' => 'Credit/debit cards, Apple Pay, Google Pay and more.',
				'plugin'            => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce-payments',
					'status' => 'not_installed',
				),
				'image'             => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/onboarding/woopayments.svg',
				'icon'              => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/onboarding/woopayments.svg',
				'links'             => array(
					array(
						'_type' => 'pricing',
						'url'   => 'https://woocommerce.com/document/woopayments/fees-and-debits/',
					),
					array(
						'_type' => 'about',
						'url'   => 'https://woocommerce.com/payments/',
					),
					array(
						'_type' => 'terms',
						'url'   => 'https://woocommerce.com/document/woopayments/our-policies/',
					),
					array(
						'_type' => 'documentation',
						'url'   => 'https://woocommerce.com/document/woopayments/',
					),
					array(
						'_type' => 'support',
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=woopayments',
					),
				),
				'tags'              => array(
					'made_in_woo',
					'preferred',
					'recommended',
				),
				'_links'            => array(
					'hide' => array(
						'href' => 'http://localhost:8888/wp-json/wc-admin/settings/payments/providers/suggestion/woopayments/hide',
					),
				),
			);
			$mock_providers[] = array(
				'id'                => '_wc_pes_paypal_full_stack',
				'_order'            => $order++,
				'_type'             => PaymentProviders::TYPE_SUGGESTION,
				'title'             => 'PayPal Payments',
				'description'       => 'Safe and secure payments using credit cards or your customer&#039;s PayPal account.',
				'short_description' => '',
				'plugin'            => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce-paypal-payments',
					'status' => 'not_installed',
				),
				'image'             => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/onboarding/paypal.png',
				'icon'              => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/payment_methods/72x72/paypal.png',
				'links'             => array(
					array(
						'_type' => 'about',
						'url'   => 'https://woocommerce.com/products/woocommerce-paypal-payments/',
					),
					array(
						'_type' => 'terms',
						'url'   => 'https://www.paypal.com/legalhub/home',
					),
					array(
						'_type' => 'support',
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-paypal-payments',
					),
				),
				'tags'              => array(
					'made_in_woo',
					'preferred',
				),
				'_links'            => array(
					'hide' => array(
						'href' => 'http://localhost:8888/wp-json/wc-admin/settings/payments/providers/suggestion/paypal/hide',
					),
				),
			);
		}

		if ( ! $skip_offline_pms ) {
			$mock_providers[] = array(
				'id'          => '_wc_offline_payment_methods_group',
				'_order'      => $order++,
				'_type'       => PaymentProviders::TYPE_OFFLINE_PMS_GROUP,
				'title'       => 'Offline Payment Methods',
				'description' => 'Allow shoppers to pay offline.',
				'icon'        => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/payment_methods/cod.svg',
			);
			$mock_providers[] = array(
				'id'          => WC_Gateway_BACS::ID,
				'_order'      => $order++,
				'_type'       => PaymentProviders::TYPE_OFFLINE_PM,
				'title'       => 'Direct bank transfer',
				'description' => 'Take payments in person via BACS. More commonly known as direct bank/wire transfer.',
				'supports'    => array(
					'products',
				),
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => 'woocommerce/woocommerce',
					'status' => 'active',
				),
				'icon'        => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/payment_methods/bacs.svg',
				'links'       => array(
					array(
						'_type' => 'about',
						'url'   => 'https://woocommerce.com/',
					),
				),
				'state'       => array(
					'enabled'     => false,
					'needs_setup' => false,
					'test_mode'   => false,
					'dev_mode'    => false,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => 'http://localhost:8888/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs',
						),
					),
				),
			);
			$mock_providers[] = array(
				'id'          => WC_Gateway_Cheque::ID,
				'_order'      => $order++,
				'_type'       => PaymentProviders::TYPE_OFFLINE_PM,
				'title'       => 'Check payments',
				'description' => 'Take payments in person via checks. This offline gateway can also be useful to test purchases.',
				'supports'    => array(
					'products',
				),
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => 'woocommerce/woocommerce',
					'status' => 'active',
				),
				'icon'        => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/payment_methods/cheque.svg',
				'links'       => array(
					array(
						'_type' => 'about',
						'url'   => 'https://woocommerce.com/',
					),
				),
				'state'       => array(
					'enabled'     => false,
					'needs_setup' => false,
					'test_mode'   => false,
					'dev_mode'    => false,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => 'http://localhost:8888/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cheque',
						),
					),
				),
			);
			$mock_providers[] = array(
				'id'          => WC_Gateway_COD::ID,
				'_order'      => $order++,
				'_type'       => PaymentProviders::TYPE_OFFLINE_PM,
				'title'       => 'Cash on delivery',
				'description' => 'Let your shoppers pay upon delivery — by cash or other methods of payment.',
				'supports'    => array(
					'products',
				),
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => 'woocommerce/woocommerce',
					'status' => 'active',
				),
				'icon'        => 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/payment_methods/cod.svg',
				'links'       => array(
					array(
						'_type' => 'about',
						'url'   => 'https://woocommerce.com/',
					),
				),
				'state'       => array(
					'enabled'     => false,
					'needs_setup' => false,
					'test_mode'   => false,
					'dev_mode'    => false,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => 'http://localhost:8888/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cod',
						),
					),
				),
			);
		}

		if ( ! $skip_paypal ) {
			$mock_providers[] = array(
				'id'          => WC_Gateway_Paypal::ID,
				'_order'      => $order++,
				'_type'       => PaymentProviders::TYPE_GATEWAY,
				'title'       => 'PayPal',
				'description' => 'PayPal',
				'supports'    => array( 'products' ),
				'state'       => array(
					'enabled'     => $enabled_core_paypal_pg,
					'needs_setup' => false,
					'test_mode'   => false,
					'dev_mode'    => false,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => 'admin.php?page=wc-settings&tab=checkout&section=paypal',
						),
					),
				),
				'image'       => 'https://example.com/image.png',
				'icon'        => 'https://example.com/icon.png',
				'links'       => array(
					array(
						'_type' => 'about',
						'url'   => 'https://woocommerce.com/paypal',
					),
				),
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => 'woocommerce/woocommerce',
					'status' => 'active',
				),
			);
		}

		$this->mock_service
			->expects( $this->once() )
			->method( 'get_payment_providers' )
			->willReturn( $mock_providers );
	}

	/**
	 * Mock extension suggestions.
	 *
	 * If a location is provided, only when called with that location will return the suggestions.
	 *
	 * @param string|null $location The location to return the suggestions for.
	 */
	private function mock_extension_suggestions( ?string $location = null ) {
		$mocker = $this->mock_service
			->expects( $this->any() )
			->method( 'get_payment_extension_suggestions' );

		if ( ! is_null( $location ) ) {
			$mocker = $mocker->with( $location );

			$this->mock_service
				->expects( $this->any() )
				->method( 'get_country' )
				->willReturn( $location );
		}

		$mocker->willReturn(
			array(
				'preferred' => array(
					array(
						'id'          => 'woopayments',
						'_priority'   => 1,
						'_type'       => 'psp',
						'title'       => 'WooPayments',
						'description' => 'WooPayments',
						'plugin'      => array(
							'_type'  => 'wporg',
							'slug'   => 'woocommerce-payments',
							'status' => 'not_installed',
						),
						'image'       => 'https://example.com/image.png',
						'icon'        => 'https://example.com/icon.png',
						'links'       => array(
							array(
								'_type' => 'link',
								'url'   => 'https://woocommerce.com/payments',
							),
						),
						'tags'        => array( 'preferred' ),
						'category'    => '',
					),
					array(
						'id'          => 'paypal_full_stack',
						'_priority'   => 2,
						'_type'       => 'apm',
						'title'       => 'PayPal',
						'description' => 'PayPal',
						'plugin'      => array(
							'_type'  => 'wporg',
							'slug'   => 'some-slug',
							'status' => 'not_installed',
						),
						'image'       => 'https://example.com/image.png',
						'icon'        => 'https://example.com/icon.png',
						'links'       => array(
							array(
								'_type' => 'link',
								'url'   => 'https://woocommerce.com/payments',
							),
						),
						'tags'        => array( 'preferred' ),
						'category'    => '',
					),
				),
				'other'     => array(
					array(
						'id'          => 'stripe',
						'_priority'   => 0,
						'_type'       => 'psp',
						'title'       => 'Stripe',
						'description' => 'Stripe',
						'plugin'      => array(
							'_type'  => 'wporg',
							'slug'   => 'some-slug',
							'status' => 'not_installed',
						),
						'image'       => 'https://example.com/image.png',
						'icon'        => 'https://example.com/icon.png',
						'links'       => array(
							array(
								'_type' => 'link',
								'url'   => 'https://woocommerce.com/stripe',
							),
						),
						'tags'        => array(),
						'category'    => 'category3',
					),
					array(
						'id'          => 'affirm',
						'_priority'   => 1,
						'_type'       => 'bnpl',
						'title'       => 'Affirm',
						'description' => 'Affirm',
						'plugin'      => array(
							'_type'  => 'wporg',
							'slug'   => 'some-slug',
							'status' => 'not_installed',
						),
						'image'       => 'https://example.com/image.png',
						'icon'        => 'https://example.com/icon.png',
						'links'       => array(
							array(
								'_type' => 'link',
								'url'   => 'https://woocommerce.com/affirm',
							),
						),
						'tags'        => array(),
						'category'    => 'category2',
					),
				),
			)
		);
	}

	/**
	 * Mock extension suggestions categories.
	 */
	private function mock_extension_suggestions_categories() {
		$this->mock_service
			->expects( $this->any() )
			->method( 'get_payment_extension_suggestion_categories' )
			->willReturn(
				array(
					array(
						'id'          => 'category1',
						'_priority'   => 10,
						'title'       => esc_html__( 'Category1', 'woocommerce' ),
						'description' => esc_html__( 'Description.', 'woocommerce' ),
					),
					array(
						'id'          => 'category2',
						'_priority'   => 20,
						'title'       => esc_html__( 'Category2', 'woocommerce' ),
						'description' => esc_html__( 'Description.', 'woocommerce' ),
					),
					array(
						'id'          => 'category3',
						'_priority'   => 30,
						'title'       => esc_html__( 'Category3', 'woocommerce' ),
						'description' => esc_html__( 'Description.', 'woocommerce' ),
					),
				)
			);
	}
}
