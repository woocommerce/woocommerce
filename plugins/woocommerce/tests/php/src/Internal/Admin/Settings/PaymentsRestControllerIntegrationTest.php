<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsRestController;
use Automattic\WooCommerce\Internal\Admin\Suggestions\Incentives\Incentive;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Gateway_Paypal;

/**
 * PaymentsRestController API controller integration test.
 *
 * @class PaymentsRestController
 */
class PaymentsRestControllerIntegrationTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/settings/payments';

	/**
	 * @var PaymentsRestController
	 */
	protected PaymentsRestController $controller;

	/**
	 * @var Payments
	 */
	protected Payments $service;

	/**
	 * @var PaymentsProviders
	 */
	protected PaymentsProviders $providers_service;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Gateways mock.
	 *
	 * @var callable
	 */
	private $gateways_mock_ref;

	/**
	 * Incentives WPCOM endpoint response mock.
	 *
	 * @var callable
	 */
	private $incentives_response_mock_ref;

	/**
	 * The initial country that is set before running tests in this test suite.
	 *
	 * @var string $initial_country
	 */
	private static string $initial_country = '';

	/**
	 * The initial currency that is set before running tests in this test suite.
	 *
	 * @var string $initial_currency
	 */
	private static string $initial_currency = '';

	/**
	 * Saves values of initial country and currency before running test suite.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::$initial_country  = WC()->countries->get_base_country();
		self::$initial_currency = get_woocommerce_currency();
	}

	/**
	 * Restores initial values of country and currency after running test suite.
	 */
	public static function wpTearDownAfterClass(): void {
		update_option( 'woocommerce_default_country', self::$initial_country );
		update_option( 'woocommerce_currency', self::$initial_currency );

		delete_option( 'woocommerce_paypal_settings' );
	}

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->controller = wc_get_container()->get( PaymentsRestController::class );
		$this->controller->register_routes();

		$this->providers_service = wc_get_container()->get( PaymentsProviders::class );
		$this->service           = wc_get_container()->get( Payments::class );

		$this->load_core_paypal_pg();

		// Mock the response from the WPCOM incentives API.
		$this->incentives_response_mock_ref = function ( $preempt, $parsed_args, $url ) {
			if ( str_contains( $url, 'https://public-api.wordpress.com/wpcom/v2/wcpay/incentives' ) ) {
				return array(
					'success'  => true,
					'body'     => wp_json_encode(
						array(
							array(
								'id'                  => 'promo-discount',
								'promo_id'            => 'promo-discount',
								'type'                => 'welcome_page',
								'cta_label'           => 'Install',
								'tc_url'              => 'https://woocommerce.com/terms-conditions',
								'description'         => 'Description.',
								'task_header_content' => 'Some content.',
								'task_badge'          => 'Save X% on payment processing fees',
							),
							array(
								'id'                => 'promo-discount__wc_settings_payments',
								'promo_id'          => 'promo-discount',
								'type'              => 'wc_settings_payments',
								'description'       => 'Use the native payments solution built and supported by Woo.',
								'cta_label'         => 'Save X%',
								'tc_url'            => 'https://woocommerce.com/terms-conditions',
								'title'             => 'Save X% on processing fees.',
								'short_description' => 'Save X% on processing fees.',
								'badge'             => 'Save X% on processing fees',
							),
						)
					),
					'response' => array(
						'code' => 200,
					),
				);
			}

			return $preempt;
		};

		add_filter( 'pre_http_request', $this->incentives_response_mock_ref, 10, 3 );

		$this->gateways_mock_ref = function ( \WC_Payment_Gateways $wc_payment_gateways ) {
			$mock_gateways = array(
				'woocommerce_payments' => array(
					'enabled'                     => false,
					'account_connected'           => false,
					'needs_setup'                 => true,
					'test_mode'                   => true,
					'dev_mode'                    => true,
					'onboarding_started'          => false,
					'onboarding_completed'        => false,
					'onboarding_test_mode'        => false,
					'plugin_slug'                 => 'woocommerce-payments',
					'plugin_file'                 => 'woocommerce-payments/woocommerce-payments.php',
					'recommended_payment_methods' => array(
						array(
							'id'          => 'card',
							'_order'      => 0,
							'enabled'     => true,
							'required'    => true,
							'title'       => 'Credit/debit card (required)',
							'description' => 'Accepts all major credit and debit cards',
							'icon'        => 'https://example.com/card-icon.png',
						),
						array(
							'id'          => 'woopay',
							'_order'      => 1,
							'enabled'     => false,
							'title'       => 'WooPay',
							'description' => 'WooPay express checkout',
							'icon'        => 'https://example.com/woopay-icon.png',
						),
					),
				),
			);

			$order = 99999;
			foreach ( $mock_gateways as $gateway_id => $gateway_data ) {
				$fake_gateway = new FakePaymentGateway( $gateway_id, $gateway_data );

				$wc_payment_gateways->payment_gateways[ $order++ ] = $fake_gateway;
			}
		};
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', $this->incentives_response_mock_ref );

		$this->unmock_payment_gateways();
		delete_option( 'woocommerce_gateway_order' );
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
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
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

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
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
		// Because the core registers the PayPal PG after the offline PMs, the order we expect is this.
		$this->assertSame(
			array( PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP, WC_Gateway_Paypal::ID ),
			array_column( $data['providers'], 'id' )
		);
		// We have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		$this->assertSame( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID ), array_column( $data['offline_payment_methods'], 'id' ) );
		// No suggestions are returned because the user can't install plugins.
		$this->assertCount( 0, $data['suggestions'] );
		// But we do get the suggestion categories.
		$this->assertCount( 4, $data['suggestion_categories'] );

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
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true,
			'install_plugins'    => true,
		);
		add_filter( 'user_has_cap', $filter_callback );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
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

		// We have the core PayPal gateway registered, the offline PMs group entry, and 2 suggestions.
		$this->assertCount( 4, $data['providers'] );
		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// We only have PSPs because there is no payment gateway enabled.
		$this->assertCount( 4, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 4, $data['suggestion_categories'] );

		// Assert that the preferred suggestions are WooPayments and PayPal (full stack), in this order.
		$this->assertSame( PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::WOOPAYMENTS, $data['providers'][0]['id'] );
		$this->assertSame( PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::PAYPAL_FULL_STACK, $data['providers'][1]['id'] );

		// Assert that the other suggestions are all PSPs.
		$other_suggestions = $data['suggestions'];
		$this->assertSame( array( PaymentsExtensionSuggestions::TYPE_PSP ), array_unique( array_column( $other_suggestions, '_type' ) ) );

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
		$this->enable_core_paypal_pg();

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
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

		// We have the core PayPal gateway registered, the offline PMs group entry, and 2 suggestions.
		$this->assertCount( 4, $data['providers'] );
		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// We get all the suggestions.
		$this->assertCount( 8, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 4, $data['suggestion_categories'] );

		// Assert that the PayPal gateway is returned as enabled.
		$gateway = $data['providers'][3];
		$this->assertTrue( $gateway['state']['enabled'] );

		// Assert that the preferred suggestions are WooPayments and PayPal (full stack), in this order.
		$this->assertSame( PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::WOOPAYMENTS, $data['providers'][0]['id'] );
		$this->assertSame( PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::PAYPAL_FULL_STACK, $data['providers'][1]['id'] );

		// Assert that PayPal Wallet is not in the other suggestions since we have the full stack variant in the preferred suggestions.
		$other_suggestions = $data['suggestions'];
		$this->assertNotContains( PaymentsExtensionSuggestions::PAYPAL_WALLET, array_column( $other_suggestions, 'id' ) );
	}

	/**
	 * Test getting payment providers without specifying a location.
	 *
	 * It should default to the store location.
	 */
	public function test_get_payment_providers_with_no_location() {
		// Arrange.
		$this->enable_core_paypal_pg();

		update_option( 'woocommerce_default_country', 'LI' ); // Liechtenstein.

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'providers', $data );
		$this->assertArrayHasKey( 'offline_payment_methods', $data );
		$this->assertArrayHasKey( 'suggestions', $data );
		$this->assertArrayHasKey( 'suggestion_categories', $data );

		// We have the core PayPal gateway registered, the offline PMs group entry, and 2 suggestions.
		$this->assertCount( 4, $data['providers'] );
		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// We get all the suggestions.
		$this->assertCount( 2, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 4, $data['suggestion_categories'] );

		// Assert that the preferred suggestions are Stripe and PayPal (full stack), in this order.
		$this->assertSame( PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::STRIPE, $data['providers'][0]['id'] );
		$this->assertSame( PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::PAYPAL_FULL_STACK, $data['providers'][1]['id'] );

		// The other suggestion is Mollie.
		$other_suggestions = $data['suggestions'];
		$this->assertSame( PaymentsExtensionSuggestions::MOLLIE, $other_suggestions[0]['id'] );
	}

	/**
	 * Test getting payment providers with an unsupported location.
	 *
	 * It should default to the store location.
	 */
	public function test_get_payment_providers_with_unsupported_location() {
		// Arrange.
		$this->enable_core_paypal_pg();

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'XX' );
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
		// No suggestions are returned.
		$this->assertCount( 0, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 4, $data['suggestion_categories'] );
	}

	/**
	 * Test getting payment providers with invalid location.
	 */
	public function test_get_payment_providers_with_invalid_location() {
		// Arrange.
		$this->enable_core_paypal_pg();

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'U' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', '12' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'USA' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test getting payment providers returns all the data.
	 */
	public function test_get_payment_providers_has_all_the_data() {
		// Arrange.
		// Reset the WooCommerce gateway order.
		delete_option( 'woocommerce_gateway_order' );
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true,
			'install_plugins'    => true,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->enable_core_paypal_pg();
		$this->mock_payment_gateways();

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// We have the core PayPal gateway and the fake WooPayments gateway registered, the offline PMs group entry, and 2 suggestions.
		$this->assertCount( 5, $data['providers'] );
		// Assert that the preferred suggestions are WooPayments and PayPal (full stack).
		// The order is different because of the presence of the fake WooPayments gateway:
		// the WooPayments suggestion gets attached to the fake gateway entry.
		// Under normal circumstances, the WooPayments suggestion would not be present
		// because the WooPayments extension would be identified as active.
		$this->assertSame(
			array(
				PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP,
				WC_Gateway_Paypal::ID,
				PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::WOOPAYMENTS, // The WooPayments suggestion.
				'woocommerce_payments', // The fake WooPayments gateway.
				PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::PAYPAL_FULL_STACK, // Preferred suggestion.
			),
			array_column( $data['providers'], 'id' )
		);

		// We also have the 3 offline payment methods.
		$this->assertCount( 3, $data['offline_payment_methods'] );
		// We get all the suggestions.
		$this->assertCount( 8, $data['suggestions'] );
		// Assert we get the suggestion categories.
		$this->assertCount( 4, $data['suggestion_categories'] );

		// Assert that the WooPayments suggestion has all the details, including the incentive data.
		$suggestion = $data['providers'][2];
		$this->assertArrayHasKey( 'id', $suggestion, 'Provider (suggestion) `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $suggestion, 'Provider (suggestion) `_order` entry is missing' );
		$this->assertArrayHasKey( '_type', $suggestion, 'Provider (suggestion) `_type` entry is missing' );
		$this->assertSame( PaymentsProviders::TYPE_SUGGESTION, $suggestion['_type'], 'Provider (suggestion) `_type` entry is not `' . PaymentsProviders::TYPE_SUGGESTION . '`' );
		$this->assertArrayHasKey( 'title', $suggestion, 'Provider (suggestion) `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $suggestion, 'Provider (suggestion) `description` entry is missing' );
		$this->assertArrayHasKey( 'links', $suggestion, 'Provider (suggestion) `links` entry is missing' );
		$this->assertCount( 5, $suggestion['links'] );
		$this->assertArrayHasKey( 'plugin', $suggestion, 'Provider (suggestion) `plugin` entry is missing' );
		$this->assertArrayHasKey( 'slug', $suggestion['plugin'], 'Provider (suggestion) `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce-payments', $suggestion['plugin']['slug'] );
		$this->assertArrayHasKey( 'status', $suggestion['plugin'], 'Provider (suggestion) `plugin[status]` entry is missing' );
		$this->assertSame( PaymentsProviders::EXTENSION_NOT_INSTALLED, $suggestion['plugin']['status'] );
		$this->assertArrayHasKey( 'tags', $suggestion, 'Provider (suggestion) `tags` entry is missing' );
		$this->assertIsList( $suggestion['tags'], 'Provider (suggestion) `tags` entry is not a list' );
		$this->assertArrayHasKey( '_suggestion_id', $suggestion, 'Provider (suggestion) `_suggestion_id` entry is missing' );
		$this->assertSame( PaymentsExtensionSuggestions::WOOPAYMENTS, $suggestion['_suggestion_id'] );
		$this->assertArrayHasKey( '_incentive', $suggestion, 'Provider (suggestion) `_incentive` entry is missing' );
		$this->assertSame(
			array(
				'id'                => 'promo-discount__wc_settings_payments',
				'promo_id'          => 'promo-discount',
				'title'             => 'Save X% on processing fees.',
				'description'       => 'Use the native payments solution built and supported by Woo.',
				'short_description' => 'Save X% on processing fees.',
				'cta_label'         => 'Save X%',
				'tc_url'            => 'https://woocommerce.com/terms-conditions',
				'badge'             => 'Save X% on processing fees',
				'_dismissals'       => array(),
				'_links'            => array(
					'dismiss' => array(
						'href' => rest_url( self::ENDPOINT . '/suggestion/' . $suggestion['_suggestion_id'] . '/incentive/' . $suggestion['_incentive']['id'] . '/dismiss' ),
					),
				),
			),
			$suggestion['_incentive']
		);
		$this->assertArrayHasKey( '_links', $suggestion, 'Provider (suggestion) `_links` entry is missing' );
		$this->assertArrayHasKey( 'hide', $suggestion['_links'], 'Provider (suggestion) `_links[hide]` entry is missing' );

		// Assert that the fake WooPayments gateway is returned as NOT enabled.
		$provider = $data['providers'][3];
		$this->assertFalse( $provider['state']['enabled'] );
		// Assert that the fake WooPayments gateway has all the details.
		$this->assertArrayHasKey( 'id', $provider, 'Provider (gateway) `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $provider, 'Provider (gateway) `_order` entry is missing' );
		$this->assertArrayHasKey( '_type', $provider, 'Provider (gateway) `_type` entry is missing' );
		$this->assertSame( PaymentsProviders::TYPE_GATEWAY, $provider['_type'], 'Provider (gateway) `_type` entry is not `' . PaymentsProviders::TYPE_GATEWAY . '`' );
		$this->assertArrayHasKey( 'title', $provider, 'Provider (gateway) `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $provider, 'Provider (gateway) `description` entry is missing' );
		$this->assertArrayHasKey( 'supports', $provider, 'Provider (gateway) `supports` entry is missing' );
		$this->assertIsList( $provider['supports'], 'Provider (gateway) `supports` entry is not a list' );
		$this->assertArrayHasKey( 'plugin', $provider, 'Provider (gateway) `plugin` entry is missing' );
		$this->assertArrayHasKey( '_type', $provider['plugin'], 'Provider (gateway) `plugin[_type]` entry is missing' );
		$this->assertArrayHasKey( 'slug', $provider['plugin'], 'Provider (gateway) `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce-payments', $provider['plugin']['slug'] );
		$this->assertArrayHasKey( 'file', $provider['plugin'], 'Provider (gateway) `plugin[file]` entry is missing' );
		$this->assertSame( 'woocommerce-payments/woocommerce-payments', $provider['plugin']['file'] ); // Skips the .php extension.
		$this->assertArrayHasKey( 'status', $provider['plugin'], 'Provider (gateway) `plugin[status]` entry is missing' );
		$this->assertSame( PaymentsProviders::EXTENSION_ACTIVE, $provider['plugin']['status'] );
		$this->assertArrayHasKey( 'links', $provider, 'Provider (gateway) `links` entry is missing' );
		$this->assertCount( 5, $provider['links'] ); // Receives the links from the suggestion.
		$this->assertArrayHasKey( 'state', $provider, 'Provider (gateway) `state` entry is missing' );
		$this->assertArrayHasKey( 'enabled', $provider['state'], 'Provider (gateway) `state[enabled]` entry is missing' );
		$this->assertFalse( $provider['state']['enabled'] );
		$this->assertArrayHasKey( 'account_connected', $provider['state'], 'Provider (gateway) `state[account_connected]` entry is missing' );
		$this->assertFalse( $provider['state']['account_connected'] );
		$this->assertArrayHasKey( 'needs_setup', $provider['state'], 'Provider (gateway) `state[needs_setup]` entry is missing' );
		$this->assertTrue( $provider['state']['needs_setup'] );
		$this->assertArrayHasKey( 'test_mode', $provider['state'], 'Provider (gateway) `state[test_mode]` entry is missing' );
		$this->assertTrue( $provider['state']['test_mode'] );
		$this->assertArrayHasKey( 'dev_mode', $provider['state'], 'Provider (gateway) `state[dev_mode]` entry is missing' );
		$this->assertTrue( $provider['state']['dev_mode'] );
		$this->assertArrayHasKey( 'management', $provider, 'Provider (gateway) `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $provider['management'], 'Provider (gateway) `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $provider['management']['_links'], 'Provider (gateway) `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'onboarding', $provider, 'Provider (gateway) `onboarding` entry is missing' );
		$this->assertArrayHasKey( 'state', $provider['onboarding'], 'Provider (gateway) `onboarding[state]` entry is missing' );
		$this->assertArrayHasKey( 'started', $provider['onboarding']['state'], 'Provider (gateway) `onboarding[state][started]` entry is missing' );
		$this->assertFalse( $provider['onboarding']['state']['started'] );
		$this->assertArrayHasKey( 'completed', $provider['onboarding']['state'], 'Provider (gateway) `onboarding[state][completed]` entry is missing' );
		$this->assertFalse( $provider['onboarding']['state']['completed'] );
		$this->assertArrayHasKey( 'test_mode', $provider['onboarding']['state'], 'Provider (gateway) `onboarding[state][test_mode]` entry is missing' );
		$this->assertFalse( $provider['onboarding']['state']['test_mode'] );
		$this->assertArrayHasKey( '_links', $provider['onboarding'], 'Provider (gateway) `onboarding[_links]` entry is missing' );
		$this->assertArrayHasKey( 'onboard', $provider['onboarding']['_links'], 'Provider (gateway) `onboarding[_links][onboard]` entry is missing' );
		$this->assertArrayHasKey( 'recommended_payment_methods', $provider['onboarding'], 'Provider (gateway) `onboarding[recommended_payment_methods]` entry is missing' );
		$this->assertCount( 2, $provider['onboarding']['recommended_payment_methods'] ); // Receives recommended PMs.
		$this->assertSame(
			array(
				array(
					'id'          => 'card',
					'_order'      => 0,
					'enabled'     => true,
					'required'    => true,
					'title'       => 'Credit/debit card (required)',
					'description' => 'Accepts all major credit and debit cards',
					'icon'        => 'https://example.com/card-icon.png',
				),
				array(
					'id'          => 'woopay',
					'_order'      => 1,
					'enabled'     => false,
					'required'    => false,
					'title'       => 'WooPay',
					'description' => 'WooPay express checkout',
					'icon'        => 'https://example.com/woopay-icon.png',
				),
			),
			$provider['onboarding']['recommended_payment_methods']
		);
		$this->assertArrayHasKey( '_suggestion_id', $provider, 'Provider (gateway) `_suggestion_id` entry is missing' );
		$this->assertSame( PaymentsExtensionSuggestions::WOOPAYMENTS, $provider['_suggestion_id'] );
		$this->assertArrayHasKey( '_incentive', $provider, 'Provider (suggestion) `_incentive` entry is missing' );
		$this->assertSame(
			array(
				'id'                => 'promo-discount__wc_settings_payments',
				'promo_id'          => 'promo-discount',
				'title'             => 'Save X% on processing fees.',
				'description'       => 'Use the native payments solution built and supported by Woo.',
				'short_description' => 'Save X% on processing fees.',
				'cta_label'         => 'Save X%',
				'tc_url'            => 'https://woocommerce.com/terms-conditions',
				'badge'             => 'Save X% on processing fees',
				'_dismissals'       => array(),
				'_links'            => array(
					'dismiss' => array(
						'href' => rest_url( self::ENDPOINT . '/suggestion/' . $provider['_suggestion_id'] . '/incentive/' . $provider['_incentive']['id'] . '/dismiss' ),
					),
				),
			),
			$provider['_incentive']
		);
		$this->assertArrayHasKey( '_links', $provider, 'Provider (gateway) `_links` entry is missing' );

		// Assert that the offline payment methods group has all the details.
		$offline_pms_group = $data['providers'][0];
		$this->assertArrayHasKey( 'id', $offline_pms_group, 'Provider (offline payment methods group) `id` entry is missing' );
		$this->assertSame( PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP, $offline_pms_group['id'] );
		$this->assertArrayHasKey( '_type', $offline_pms_group, 'Provider (offline payment methods group) `_type` entry is missing' );
		$this->assertSame( PaymentsProviders::TYPE_OFFLINE_PMS_GROUP, $offline_pms_group['_type'], 'Provider (offline payment methods group) `_type` entry is not `' . PaymentsProviders::TYPE_OFFLINE_PMS_GROUP . '`' );
		$this->assertArrayHasKey( '_order', $offline_pms_group, 'Provider (offline payment methods group) `_order` entry is missing' );
		$this->assertArrayHasKey( 'title', $offline_pms_group, 'Provider (offline payment methods group) `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $offline_pms_group, 'Provider (offline payment methods group) `description` entry is missing' );
		$this->assertArrayHasKey( 'management', $offline_pms_group, 'Provider (offline payment methods group) `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $offline_pms_group['management'], 'Provider (offline payment methods group) `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $offline_pms_group['management']['_links'], 'Provider (offline payment methods group) `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $offline_pms_group, 'Provider (gateway) `plugin` entry is missing' );
		$this->assertArrayHasKey( '_type', $offline_pms_group['plugin'], 'Provider (offline payment methods group) `plugin[_type]` entry is missing' );
		$this->assertSame( PaymentsProviders::EXTENSION_TYPE_WPORG, $offline_pms_group['plugin']['_type'], 'Provider (offline payment methods group) `plugin[_type]` entry is not `' . PaymentsProviders::EXTENSION_TYPE_WPORG . '`' );
		$this->assertArrayHasKey( 'slug', $offline_pms_group['plugin'], 'Provider (offline payment methods group) `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce', $offline_pms_group['plugin']['slug'] );
		$this->assertArrayHasKey( 'file', $offline_pms_group['plugin'], 'Provider (offline payment methods group) `plugin[file]` entry is missing' );
		$this->assertSame( '', $offline_pms_group['plugin']['file'] ); // Always empty.
		$this->assertArrayHasKey( 'status', $offline_pms_group['plugin'], 'Provider (offline payment methods group) `plugin[status]` entry is missing' );
		$this->assertSame( PaymentsProviders::EXTENSION_ACTIVE, $offline_pms_group['plugin']['status'] );

		// Assert that the PayPal gateway is returned as enabled.
		$provider = $data['providers'][1];
		$this->assertTrue( $provider['state']['enabled'] );
		// Assert that the PayPal gateway has all the details.
		$this->assertArrayHasKey( 'id', $provider, 'Provider (gateway) `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $provider, 'Provider (gateway) `_order` entry is missing' );
		$this->assertArrayHasKey( '_type', $provider, 'Provider (gateway) `_order` entry is missing' );
		$this->assertSame( PaymentsProviders::TYPE_GATEWAY, $provider['_type'], 'Provider (gateway) `_type` entry is not `' . PaymentsProviders::TYPE_GATEWAY . '`' );
		$this->assertArrayHasKey( 'title', $provider, 'Provider (gateway) `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $provider, 'Provider (gateway) `description` entry is missing' );
		$this->assertArrayHasKey( 'supports', $provider, 'Provider (gateway) `supports` entry is missing' );
		$this->assertIsList( $provider['supports'], 'Provider (gateway) `supports` entry is not a list' );
		$this->assertArrayHasKey( 'plugin', $provider, 'Provider (gateway) `plugin` entry is missing' );
		$this->assertArrayHasKey( '_type', $provider['plugin'], 'Provider (gateway) `plugin[_type]` entry is missing' );
		$this->assertArrayHasKey( 'slug', $provider['plugin'], 'Provider (gateway) `plugin[slug]` entry is missing' );
		$this->assertArrayHasKey( 'file', $provider['plugin'], 'Provider (gateway) `plugin[file]` entry is missing' );
		$this->assertArrayHasKey( 'status', $provider['plugin'], 'Provider (gateway) `plugin[status]` entry is missing' );
		$this->assertArrayHasKey( 'links', $provider, 'Provider (gateway) `links` entry is missing' );
		$this->assertCount( 1, $provider['links'] );
		$this->assertArrayHasKey( 'state', $provider, 'Provider (gateway) `state` entry is missing' );
		$this->assertArrayHasKey( 'enabled', $provider['state'], 'Provider (gateway) `state[enabled]` entry is missing' );
		$this->assertArrayHasKey( 'account_connected', $provider['state'], 'Provider (gateway) `state[account_connected]` entry is missing' );
		$this->assertArrayHasKey( 'needs_setup', $provider['state'], 'Provider (gateway) `state[needs_setup]` entry is missing' );
		$this->assertArrayHasKey( 'test_mode', $provider['state'], 'Provider (gateway) `state[test_mode]` entry is missing' );
		$this->assertArrayHasKey( 'dev_mode', $provider['state'], 'Provider (gateway) `state[dev_mode]` entry is missing' );
		$this->assertArrayHasKey( 'management', $provider, 'Provider (gateway) `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $provider['management'], 'Provider (gateway) `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $provider['management']['_links'], 'Provider (gateway) `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'onboarding', $provider, 'Provider (gateway) `onboarding` entry is missing' );
		$this->assertArrayHasKey( 'state', $provider['onboarding'], 'Provider (gateway) `onboarding[state]` entry is missing' );
		$this->assertArrayHasKey( 'started', $provider['onboarding']['state'], 'Provider (gateway) `onboarding[state][started]` entry is missing' );
		$this->assertArrayHasKey( 'completed', $provider['onboarding']['state'], 'Provider (gateway) `onboarding[state][completed]` entry is missing' );
		$this->assertArrayHasKey( 'test_mode', $provider['onboarding']['state'], 'Provider (gateway) `onboarding[state][test_mode]` entry is missing' );
		$this->assertArrayHasKey( '_links', $provider['onboarding'], 'Provider (gateway) `onboarding[_links]` entry is missing' );
		$this->assertArrayHasKey( 'onboard', $provider['onboarding']['_links'], 'Provider (gateway) `onboarding[_links][onboard]` entry is missing' );
		$this->assertArrayHasKey( 'recommended_payment_methods', $provider['onboarding'], 'Provider (gateway) `onboarding[recommended_payment_methods]` entry is missing' );
		$this->assertArrayHasKey( '_links', $provider, 'Provider (gateway) `_links` entry is missing' );

		// Assert that the offline payment methods have all the details.
		$offline_pm = $data['offline_payment_methods'][0];
		$this->assertArrayHasKey( 'id', $offline_pm, 'Offline payment method `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $offline_pm, 'Offline payment method `_order` entry is missing' );
		$this->assertArrayHasKey( '_type', $offline_pm, 'Offline payment method `_type` entry is missing' );
		$this->assertSame( PaymentsProviders::TYPE_OFFLINE_PM, $offline_pm['_type'], 'Offline payment method `_type` entry is not `' . PaymentsProviders::TYPE_OFFLINE_PM . '`' );
		$this->assertArrayHasKey( 'title', $offline_pm, 'Offline payment method `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $offline_pm, 'Offline payment method `description` entry is missing' );
		$this->assertArrayHasKey( 'state', $offline_pm, 'Offline payment method `state` entry is missing' );
		$this->assertArrayHasKey( 'enabled', $offline_pm['state'], 'Offline payment method `state[enabled]` entry is missing' );
		$this->assertArrayHasKey( 'account_connected', $offline_pm['state'], 'Offline payment method `state[account_connected]` entry is missing' );
		$this->assertArrayHasKey( 'needs_setup', $offline_pm['state'], 'Offline payment method `state[needs_setup]` entry is missing' );
		$this->assertArrayHasKey( 'icon', $offline_pm, 'Offline payment method `icon` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $offline_pm, 'Offline payment method `plugin` entry is missing' );
		$this->assertArrayHasKey( '_type', $offline_pm['plugin'], 'Offline payment method `plugin[_type]` entry is missing' );
		$this->assertSame( PaymentsProviders::EXTENSION_TYPE_WPORG, $offline_pm['plugin']['_type'], 'Offline payment method `plugin[_type]` entry is not `' . PaymentsProviders::EXTENSION_TYPE_WPORG . '`' );
		$this->assertArrayHasKey( 'slug', $offline_pm['plugin'], 'Offline payment method `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce', $offline_pm['plugin']['slug'] );
		$this->assertArrayHasKey( 'file', $offline_pm['plugin'], 'Offline payment method `plugin[file]` entry is missing' );
		$this->assertSame( 'woocommerce/woocommerce', $offline_pm['plugin']['file'] ); // Skips the .php extension.
		$this->assertArrayHasKey( 'status', $offline_pm['plugin'], 'Offline payment method `plugin[status]` entry is missing' );
		$this->assertSame( PaymentsProviders::EXTENSION_ACTIVE, $offline_pm['plugin']['status'] );
		$this->assertArrayHasKey( 'management', $offline_pm, 'Offline payment method `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $offline_pm['management'], 'Offline payment method `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $offline_pm['management']['_links'], 'Offline payment method `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'onboarding', $offline_pm, 'Offline payment method `onboarding` entry is missing' );
		$this->assertArrayHasKey( 'state', $offline_pm['onboarding'], 'Offline payment method `onboarding[state]` entry is missing' );
		$this->assertArrayHasKey( 'started', $offline_pm['onboarding']['state'], 'Offline payment method `onboarding[state][started]` entry is missing' );
		$this->assertArrayHasKey( 'test_mode', $offline_pm['onboarding']['state'], 'Offline payment method `onboarding[state][test_mode]` entry is missing' );
		$this->assertArrayHasKey( '_links', $offline_pm['onboarding'], 'Offline payment method `onboarding[_links]` entry is missing' );
		$this->assertArrayHasKey( 'onboard', $offline_pm['onboarding']['_links'], 'Offline payment method `onboarding[_links][onboard]` entry is missing' );

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
	 * Test setting the country.
	 */
	public function test_set_country() {
		// Arrange.
		$country = 'RO';
		$this->service->set_country( 'US' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/country' );
		$request->set_param( 'location', $country );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );

		$this->assertSame( $country, $this->service->get_country() );

		// Clean up.
		$this->service->set_country( 'US' );
	}

	/**
	 * Test setting the country to the same value.
	 */
	public function test_set_country_no_success() {
		// Arrange.
		$country = 'RO';
		$this->service->set_country( $country );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/country' );
		$request->set_param( 'location', $country );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $data['success'] );

		$this->assertSame( $country, $this->service->get_country() );

		// Clean up.
		$this->service->set_country( 'US' );
	}

	/**
	 * Test updating providers order.
	 */
	public function test_update_providers_order() {
		// Arrange.
		$order_map = array_flip(
			array(
				WC_Gateway_Paypal::ID, // We move PayPal at the top.
				PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP,
			)
		);
		update_option(
			PaymentsProviders::PROVIDERS_ORDER_OPTION,
			array_flip(
				array(
					PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP,
					...PaymentsProviders::OFFLINE_METHODS,
					WC_Gateway_Paypal::ID,
				)
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers/order' );
		$request->set_param( 'order_map', $order_map );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );

		$this->assertSame(
			array_flip(
				array(
					WC_Gateway_Paypal::ID,
					PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP,
					...PaymentsProviders::OFFLINE_METHODS,
				)
			),
			get_option( PaymentsProviders::PROVIDERS_ORDER_OPTION )
		);

		// Clean up.
		delete_option( PaymentsProviders::PROVIDERS_ORDER_OPTION );
	}

	/**
	 * Test updating providers order with just offline PMs.
	 */
	public function test_update_providers_order_offline_pms() {
		// Arrange.
		$order_map = array_flip( PaymentsProviders::OFFLINE_METHODS );
		delete_option( PaymentsProviders::PROVIDERS_ORDER_OPTION );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers/order' );
		$request->set_param( 'order_map', $order_map );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );

		$this->assertSame(
			array_flip(
				array(
					PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP,
					...PaymentsProviders::OFFLINE_METHODS,
					WC_Gateway_Paypal::ID,
				)
			),
			get_option( PaymentsProviders::PROVIDERS_ORDER_OPTION )
		);

		// Clean up.
		delete_option( PaymentsProviders::PROVIDERS_ORDER_OPTION );
	}

	/**
	 * Test hiding a payment extension suggestion.
	 */
	public function test_hide_payment_extension_suggestion() {
		// Arrange.
		$suggestion_order_map_id = PaymentsProviders::SUGGESTION_ORDERING_PREFIX . PaymentsExtensionSuggestions::WOOPAYMENTS;

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/' . $suggestion_order_map_id . '/hide' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert that the suggestion is not in the providers list anymore.
		$this->assertNotContains( $suggestion_order_map_id, array_column( $data['providers'], 'id' ) );
		// But it is in the other list.
		$other_suggestions = $data['suggestions'];
		$this->assertContains( PaymentsExtensionSuggestions::WOOPAYMENTS, array_column( $other_suggestions, 'id' ) );

		// Delete the user meta.
		delete_user_meta( $this->store_admin_id, Payments::PAYMENTS_NOX_PROFILE_KEY );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert that the suggestion is in the providers list again.
		$this->assertContains( $suggestion_order_map_id, array_column( $data['providers'], 'id' ) );
	}

	/**
	 * Test dismissing a PES incentive for all contexts.
	 */
	public function test_dismiss_payment_extension_suggestion_incentive_all_contexts() {
		// Arrange.
		$incentive_id = 'promo-discount__wc_settings_payments';

		delete_user_meta( get_current_user_id(), Incentive::PREFIX . 'dismissed' );
		// Reset the WooCommerce gateway order.
		delete_option( 'woocommerce_gateway_order' );
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true,
			'install_plugins'    => true,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->enable_core_paypal_pg();
		$this->mock_payment_gateways();

		// Act.
		// Dismiss it for all contexts - no context param.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/' . PaymentsExtensionSuggestions::WOOPAYMENTS . '/incentive/' . $incentive_id . '/dismiss' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert that the incentive is not in the WooPayments suggestion anymore.
		$suggestion = $data['providers'][2];
		$this->assertArrayNotHasKey( '_incentive', $suggestion );
		// Assert that the incentive is not in the WooPayments gateway anymore.
		$gateway = $data['providers'][3];
		$this->assertArrayNotHasKey( '_incentive', $gateway );

		// Delete the user meta.
		delete_user_meta( get_current_user_id(), Incentive::PREFIX . 'dismissed' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert that the incentive is in the WooPayments suggestion again.
		$suggestion = $data['providers'][2];
		$this->assertArrayHasKey( '_incentive', $suggestion );
		// Assert that the incentive is in the WooPayments gateway again.
		$gateway = $data['providers'][3];
		$this->assertArrayHasKey( '_incentive', $gateway );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
		delete_user_meta( get_current_user_id(), Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test dismissing a PES incentive for a certain context.
	 */
	public function test_dismiss_payment_extension_suggestion_incentive_in_context() {
		// Arrange.
		$incentive_id = 'promo-discount__wc_settings_payments';
		$context      = 'wc_settings_payments__modal';

		delete_user_meta( get_current_user_id(), Incentive::PREFIX . 'dismissed' );
		// Reset the WooCommerce gateway order.
		delete_option( 'woocommerce_gateway_order' );
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true,
			'install_plugins'    => true,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->enable_core_paypal_pg();
		$this->mock_payment_gateways();

		// Act.
		// Dismiss it for all contexts - no context param.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/suggestion/' . PaymentsExtensionSuggestions::WOOPAYMENTS . '/incentive/' . $incentive_id . '/dismiss' );
		$request->set_param( 'context', $context );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert that the incentive is in the WooPayments suggestion with the right dismissals list.
		$suggestion = $data['providers'][2];
		$this->assertArrayHasKey( '_incentive', $suggestion );
		$this->assertEquals( $context, $suggestion['_incentive']['_dismissals'][0]['context'] );
		// Assert that the incentive is in the WooPayments gateway with the right dismissals list.
		$gateway = $data['providers'][3];
		$this->assertArrayHasKey( '_incentive', $gateway );
		$this->assertEquals( $context, $gateway['_incentive']['_dismissals'][0]['context'] );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
		delete_user_meta( get_current_user_id(), Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Load the WC core PayPal gateway but not enable it.
	 */
	private function load_core_paypal_pg() {
		// Make sure the WC core PayPal gateway is loaded.
		update_option(
			'woocommerce_paypal_settings',
			array(
				'_should_load' => 'yes',
				'enabled'      => 'no',
			)
		);
		// Make sure the store currency is supported by the gateway.
		update_option( 'woocommerce_currency', 'USD' );
		WC()->payment_gateways()->init();

		// Reset the controller memo to pick up the new gateway details.
		$this->providers_service->reset_memo();
	}

	/**
	 * Enable the WC core PayPal gateway.
	 */
	private function enable_core_paypal_pg() {
		// Enable the WC core PayPal gateway.
		update_option(
			'woocommerce_paypal_settings',
			array(
				'_should_load' => 'yes',
				'enabled'      => 'yes',
			)
		);
		// Make sure the store currency is supported by the gateway.
		update_option( 'woocommerce_currency', 'USD' );
		WC()->payment_gateways()->init();

		// Reset the service memo to pick up the new gateway details.
		$this->providers_service->reset_memo();
	}

	/**
	 * Mock the WC payment gateways.
	 */
	protected function mock_payment_gateways() {
		// Hook into the payment gateways initialization to mock the gateways.
		add_action( 'wc_payment_gateways_initialized', $this->gateways_mock_ref, 100 );
		// Reinitialize the WC gateways.
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		$this->providers_service->reset_memo();
	}

	/**
	 * Unmock the WC payment gateways.
	 */
	private function unmock_payment_gateways() {
		remove_all_actions( 'wc_payment_gateways_initialized' );
		// Reinitialize the WC gateways.
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		$this->providers_service->reset_memo();
	}

	/**
	 * @dataProvider provider_testing_preferred_offline_provider_visibility
	 *
	 * @param string $selling_context The selling context.
	 * @param bool   $expected_preferred_visible The expected visibility of the preferred provider.
	 */
	public function test_preferred_provider_visibility_based_on_selling_context( $selling_context, $expected_preferred_visible ) {
		// Arrange.
		$this->enable_core_paypal_pg();
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => $selling_context,
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/providers' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$preferred_found = false;
		foreach ( $data['providers'] as $provider ) {
			if ( isset( $provider['tags'] ) && in_array( PaymentsExtensionSuggestions::TAG_PREFERRED_OFFLINE, $provider['tags'], true ) ) {
				$preferred_found = true;
				break;
			}
		}

		if ( $expected_preferred_visible ) {
			$this->assertTrue( $preferred_found );
		} else {
			$this->assertFalse( $preferred_found );
		}
	}

	/**
	 * Provides different merchant selling scenarios based on WC onboarding profile and expected preferred provider visibility.
	 *
	 * @return array[]
	 */
	public function provider_testing_preferred_offline_provider_visibility() {
		return array(
			'Offline only'       => array(
				'no_im_selling_offline',
				true,
			),
			'Online and offline' => array(
				'im_selling_both_online_and_offline',
				true,
			),
			'Online only'        => array(
				'yes_im_selling_online',
				false,
			),
		);
	}
}
