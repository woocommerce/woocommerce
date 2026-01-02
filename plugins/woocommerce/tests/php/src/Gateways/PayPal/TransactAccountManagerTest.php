<?php
/**
 * Class TransactAccountManagerTest file.
 *
 * @package WooCommerce\Tests\Gateways\PayPal
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Gateways\PayPal\TransactAccountManager as PayPalTransactAccountManager;

/**
 * TransactAccountManagerTest class.
 *
 * @package WooCommerce\Tests\Gateways\PayPal
 */
class TransactAccountManagerTest extends \WC_Unit_Test_Case {
	/**
	 * Mock PayPal gateway.
	 *
	 * @var \WC_Gateway_Paypal
	 */
	private \WC_Gateway_Paypal $gateway;

	/**
	 * Account manager instance.
	 *
	 * @var PayPalTransactAccountManager
	 */
	private PayPalTransactAccountManager $account_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mock PayPal gateway.
		$this->gateway = $this->getMockBuilder( \WC_Gateway_Paypal::class )
			->disableOriginalConstructor()
			->getMock();

		// Set default properties.
		$this->gateway->email    = 'test@example.com';
		$this->gateway->testmode = true;

		// Create account manager instance.
		$this->account_manager = new PayPalTransactAccountManager( $this->gateway );
	}

	/**
	 * Test constructor sets gateway.
	 *
	 * @return void
	 */
	public function test_constructor_sets_gateway(): void {
		$account_manager = new PayPalTransactAccountManager( $this->gateway );

		// Use reflection to access the private gateway property.
		$reflection       = new \ReflectionClass( $account_manager );
		$gateway_property = $reflection->getProperty( 'gateway' );
		$gateway_property->setAccessible( true );

		$this->assertSame( $this->gateway, $gateway_property->getValue( $account_manager ) );
	}

	/**
	 * Test do_onboarding when email is empty.
	 *
	 * @return void
	 */
	public function test_do_onboarding_when_email_empty(): void {
		$this->gateway->email = '';

		// Should not throw any errors and should return early.
		$this->account_manager->do_onboarding();

		$this->assertTrue( true );
	}

	/**
	 * Test do_onboarding when Jetpack registration fails.
	 *
	 * @return void
	 */
	public function test_do_onboarding_when_jetpack_registration_fails(): void {
		// Mock the gateway to return a mock Jetpack connection manager.
		$jetpack_manager = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'is_connected', 'try_registration' ) )
			->getMock();

		$jetpack_manager->method( 'is_connected' )
			->willReturn( false );

		$jetpack_manager->method( 'try_registration' )
			->willReturn( new \WP_Error( 'registration_failed', 'Registration failed' ) );

		$this->gateway->method( 'get_jetpack_connection_manager' )
			->willReturn( $jetpack_manager );

		// Should not throw any errors and should return early.
		$this->account_manager->do_onboarding();

		$this->assertTrue( true );
	}

	/**
	 * Test do_onboarding when merchant account creation fails.
	 *
	 * @return void
	 */
	public function test_do_onboarding_when_merchant_account_creation_fails(): void {
		// Mock the gateway to return a mock Jetpack connection manager.
		$jetpack_manager = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'is_connected' ) )
			->getMock();

		$jetpack_manager->method( 'is_connected' )
			->willReturn( true );

		$this->gateway->method( 'get_jetpack_connection_manager' )
			->willReturn( $jetpack_manager );

		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return an error.
		add_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		// Should do nothing. Should not throw any errors.
		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$account_manager->do_onboarding();

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		$this->assertTrue( true );
	}

	/**
	 * Test do_onboarding when provider account creation fails.
	 *
	 * @return void
	 */
	public function test_do_onboarding_when_provider_account_creation_fails(): void {
		// Mock the gateway to return a mock Jetpack connection manager.
		$jetpack_manager = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'is_connected' ) )
			->getMock();

		$jetpack_manager->method( 'is_connected' )
			->willReturn( true );

		$this->gateway->method( 'get_jetpack_connection_manager' )
			->willReturn( $jetpack_manager );

		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return an error.
		add_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		// Should do nothing. Should not throw any errors.
		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$account_manager->do_onboarding();

		// Check that it returns true.
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		$this->assertTrue( true );
	}

	/**
	 * Test get_merchant_account_data returns cached data when available.
	 *
	 * @return void
	 */
	public function test_get_merchant_account_data_returns_cached_data(): void {
		// Return valid cache data.
		add_filter(
			'pre_option_woocommerce_paypal_transact_merchant_account_test',
			array( $this, 'return_valid_merchant_account_cache' )
		);

		$result = $this->account_manager->get_transact_account_data( 'merchant' );

		// Clean up the filter.
		remove_filter(
			'pre_option_woocommerce_paypal_transact_merchant_account_test',
			array( $this, 'return_valid_merchant_account_cache' )
		);

		$expected_merchant_account = $this->return_valid_merchant_account_cache();
		$this->assertEquals( $expected_merchant_account['account'], $result );
	}

	/**
	 * Test get_merchant_account_data returns null when cache is expired.
	 *
	 * @return void
	 */
	public function test_get_merchant_account_data_returns_null_when_cache_expired(): void {
		// Mock cache to return expired data.
		add_filter(
			'pre_option_woocommerce_paypal_transact_merchant_account_test',
			array( $this, 'return_expired_merchant_account_cache' )
		);

		$result = $this->account_manager->get_transact_account_data( 'merchant' );

		// Clean up the filter.
		remove_filter(
			'pre_option_woocommerce_paypal_transact_merchant_account_test',
			array( $this, 'return_expired_merchant_account_cache' )
		);

		$this->assertNull( $result );
	}

	/**
	 * Test get_merchant_account_data fetches when cache is empty and caches fetched data.
	 *
	 * @return void
	 */
	public function test_get_merchant_account_data_fetches_and_caches_data(): void {
		// Return empty cache.
		add_filter(
			'pre_option_woocommerce_paypal_transact_merchant_account_test',
			array( $this, 'return_empty_merchant_account_cache' )
		);

		// Return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Return a successful response, with the merchant account data.
		add_filter( 'pre_http_request', array( $this, 'return_merchant_account_api_success' ) );

		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$result          = $account_manager->get_transact_account_data( 'merchant' );

		// Clean up the filters.
		remove_filter( 'pre_option_woocommerce_paypal_transact_merchant_account_test', array( $this, 'return_empty_merchant_account_cache' ) );
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_merchant_account_api_success' ) );

		// Check that it returns the data.
		$response_data             = json_decode( $this->return_merchant_account_api_success()['body'], true );
		$expected_merchant_account = array( 'public_id' => $response_data['public_id'] );
		$this->assertEquals( $expected_merchant_account, $result );

		// Check that the cache was updated.
		wp_cache_delete( 'woocommerce_paypal_transact_merchant_account_test', 'options' );
		$cached_data = get_option( 'woocommerce_paypal_transact_merchant_account_test' );
		$this->assertEquals( $expected_merchant_account, $cached_data['account'] );
	}


	/**
	 * Test get_provider_account_data returns cached data when available.
	 *
	 * @return void
	 */
	public function test_get_provider_account_data_returns_cached_data(): void {
		// Return valid cache data.
		add_filter(
			'pre_option_woocommerce_paypal_transact_provider_account_test',
			array( $this, 'return_valid_provider_account_cache' )
		);

		$result = $this->account_manager->get_transact_account_data( 'provider' );

		// Clean up the filter.
		remove_filter(
			'pre_option_woocommerce_paypal_transact_provider_account_test',
			array( $this, 'return_valid_provider_account_cache' )
		);

		$expected_provider_account = $this->return_valid_provider_account_cache();
		$this->assertEquals( $expected_provider_account['account'], $result );
	}

	/**
	 * Test get_provider_account_data returns null when cache is expired.
	 *
	 * @return void
	 */
	public function test_get_provider_account_data_returns_null_when_cache_expired(): void {
		// Mock cache to return expired data.
		add_filter(
			'pre_option_woocommerce_paypal_transact_provider_account_test',
			array( $this, 'return_expired_provider_account_cache' )
		);

		$result = $this->account_manager->get_transact_account_data( 'provider' );

		// Clean up the filter.
		remove_filter(
			'pre_option_woocommerce_paypal_transact_provider_account_test',
			array( $this, 'return_expired_provider_account_cache' )
		);

		$this->assertNull( $result );
	}

	/**
	 * Test get_provider_account_data fetches when cache is empty and caches fetched data.
	 *
	 * @return void
	 */
	public function test_get_provider_account_data_fetches_and_caches_data(): void {
		// Return empty cache.
		add_filter(
			'pre_option_woocommerce_paypal_transact_provider_account_test',
			array( $this, 'return_empty_provider_account_cache' )
		);

		// Return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Return a successful response, with the provider account data.
		add_filter( 'pre_http_request', array( $this, 'return_provider_account_api_success' ) );

		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$result          = $account_manager->get_transact_account_data( 'provider' );

		// Clean up the filters.
		remove_filter( 'pre_option_woocommerce_paypal_transact_provider_account_test', array( $this, 'return_empty_provider_account_cache' ) );
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_provider_account_api_success' ) );

		// Check that it returns the data.
		$this->assertTrue( $result );

		// Check that the cache was updated.
		wp_cache_delete( 'woocommerce_paypal_transact_provider_account_test', 'options' );
		$cached_data = get_option( 'woocommerce_paypal_transact_provider_account_test' );
		$this->assertTrue( $cached_data['account'] );
	}

	/**
	 * Test fetch_merchant_account when API request fails.
	 *
	 * @return void
	 */
	public function test_fetch_merchant_account_when_api_request_fails(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return an error.
		add_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$reflection      = new \ReflectionClass( $account_manager );
		$method          = $reflection->getMethod( 'fetch_merchant_account' );
		$method->setAccessible( true );

		$result = $method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		$this->assertNull( $result );
	}

	/**
	 * Test fetch_merchant_account when API response is successful.
	 *
	 * @return void
	 */
	public function test_fetch_merchant_account_when_api_response_successful(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return a successful response.
		add_filter( 'pre_http_request', array( $this, 'return_merchant_account_api_success' ) );

		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$reflection      = new \ReflectionClass( $account_manager );
		$method          = $reflection->getMethod( 'fetch_merchant_account' );
		$method->setAccessible( true );

		$result = $method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_merchant_account_api_success' ) );

		$this->assertEquals( array( 'public_id' => 'test_public_id' ), $result );
	}

	/**
	 * Test fetch_provider_account when API request fails.
	 *
	 * @return void
	 */
	public function test_fetch_provider_account_when_api_request_fails(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return an error.
		add_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		// Create a real account manager instance.
		$account_manager = new PayPalTransactAccountManager( $this->gateway );

		// Use reflection to access the private fetch_provider_account method.
		$reflection = new \ReflectionClass( $account_manager );
		$method     = $reflection->getMethod( 'fetch_provider_account' );
		$method->setAccessible( true );
		$result = $method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		$this->assertFalse( $result );
	}

	/**
	 * Test fetch_provider_account when API response is successful.
	 *
	 * @return void
	 */
	public function test_fetch_provider_account_when_api_response_successful(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return a successful response.
		add_filter( 'pre_http_request', array( $this, 'return_provider_account_api_success' ) );

		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$reflection      = new \ReflectionClass( $account_manager );
		$method          = $reflection->getMethod( 'fetch_provider_account' );
		$method->setAccessible( true );
		$result = $method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_provider_account_api_success' ) );

		// Check that it returns true.
		$this->assertTrue( $result );
	}

	/**
	 * Test create_merchant_account when API request fails.
	 *
	 * @return void
	 */
	public function test_create_merchant_account_when_api_request_fails(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return an error.
		add_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		// Create a real account manager instance.
		$account_manager = new PayPalTransactAccountManager( $this->gateway );

		// Use reflection to access the private create_merchant_account method.
		$reflection    = new \ReflectionClass( $account_manager );
		$create_method = $reflection->getMethod( 'create_merchant_account' );
		$create_method->setAccessible( true );

		$result = $create_method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		// The method should return null when API fails.
		$this->assertNull( $result );
	}

	/**
	 * Test create_merchant_account when API response is successful.
	 *
	 * @return void
	 */
	public function test_create_merchant_account_when_api_response_successful(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return a successful response.
		add_filter( 'pre_http_request', array( $this, 'return_merchant_account_api_success' ) );

		// Create a real account manager instance.
		$account_manager = new PayPalTransactAccountManager( $this->gateway );

		// Use reflection to access the private create_merchant_account method.
		$reflection = new \ReflectionClass( $account_manager );
		$method     = $reflection->getMethod( 'create_merchant_account' );
		$method->setAccessible( true );

		$result = $method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_merchant_account_api_success' ) );

		// Check that it returns the data.
		$this->assertEquals( array( 'public_id' => 'test_public_id' ), $result );
	}

	/**
	 * Test create_provider_account when API request fails.
	 *
	 * @return void
	 */
	public function test_create_provider_account_when_api_request_fails(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return an error.
		add_filter( 'pre_http_request', array( $this, 'return_api_error' ) );

		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$reflection      = new \ReflectionClass( $account_manager );
		$method          = $reflection->getMethod( 'create_provider_account' );
		$method->setAccessible( true );
		$result = $method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_http_request', array( $this, 'return_api_error' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Check that it returns false.
		$this->assertFalse( $result );
	}

	/**
	 * Test create_provider_account when API response is successful.
	 *
	 * @return void
	 */
	public function test_create_provider_account_when_api_response_successful(): void {
		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		// Mock the HTTP request to return a successful response.
		add_filter( 'pre_http_request', array( $this, 'return_provider_account_api_success' ) );

		$account_manager = new PayPalTransactAccountManager( $this->gateway );
		$reflection      = new \ReflectionClass( $account_manager );
		$method          = $reflection->getMethod( 'create_provider_account' );
		$method->setAccessible( true );
		$result = $method->invoke( $account_manager );

		// Clean up the filters.
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_http_request', array( $this, 'return_provider_account_api_success' ) );

		// Check that it returns true.
		$this->assertTrue( $result );
	}

	/**
	 * Helper method to return API error response.
	 *
	 * @return \WP_Error Error response.
	 */
	public function return_api_error(): \WP_Error {
		return new \WP_Error( 'api_error', 'API request failed' );
	}

	/**
	 * Helper method to return successful merchant account API response.
	 *
	 * @return array Success response.
	 */
	public function return_merchant_account_api_success(): array {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'public_id' => 'test_public_id',
				)
			),
		);
	}

	/**
	 * Helper method to return successful provider account API response.
	 *
	 * @return array Success response.
	 */
	public function return_provider_account_api_success(): array {
		return array( 'response' => array( 'code' => 200 ) );
	}

	/**
	 * Helper method to return null site ID for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return array
	 */
	public function return_null_site_id( $value ): array {
		return array( 'id' => null );
	}

	/**
	 * Helper method to return valid site ID for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return array
	 */
	public function return_valid_site_id( $value ): array {
		return array( 'id' => 12345 );
	}

	/**
	 * Helper method to return empty merchant account cache.
	 *
	 * @return false
	 */
	public function return_empty_merchant_account_cache(): bool {
		return false;
	}

	/**
	 * Helper method to return expired merchant account cache.
	 *
	 * @return array
	 */
	public function return_expired_merchant_account_cache(): array {
		return array(
			'account' => array( 'public_id' => 'test_public_id' ),
			'expiry'  => time() - 3600, // Expired 1 hour ago.
		);
	}

	/**
	 * Helper method to return valid merchant account cache.
	 *
	 * @return array
	 */
	public function return_valid_merchant_account_cache(): array {
		return array(
			'account' => array( 'public_id' => 'test_public_id' ),
			'expiry'  => time() + 3600, // Expires in 1 hour.
		);
	}

	/**
	 * Helper method to return empty provider account cache.
	 *
	 * @return false
	 */
	public function return_empty_provider_account_cache(): bool {
		return false;
	}

	/**
	 * Helper method to return expired provider account cache.
	 *
	 * @return array
	 */
	public function return_expired_provider_account_cache(): array {
		return array(
			'account' => true,
			'expiry'  => time() - 3600, // Expired 1 hour ago.
		);
	}

	/**
	 * Helper method to return valid provider account cache.
	 *
	 * @return array
	 */
	public function return_valid_provider_account_cache(): array {
		return array(
			'account' => true,
			'expiry'  => time() + 3600, // Expires in 1 hour.
		);
	}

	/**
	 * Helper method to return valid blog token for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return array
	 */
	public function return_blog_token( $value ): array {
		return array( 'blog_token' => 'IAM.AJETPACKBLOGTOKEN' );
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		// Clean up any options we created.
		delete_option( 'woocommerce_paypal_transact_merchant_account_live' );
		delete_option( 'woocommerce_paypal_transact_merchant_account_test' );
		delete_option( 'woocommerce_paypal_transact_provider_account_live' );
		delete_option( 'woocommerce_paypal_transact_provider_account_test' );

		parent::tearDown();
	}
}
