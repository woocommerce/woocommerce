<?php
/**
 * Unit tests for Automattic\WooCommerce\Gateways\PayPal\Buttons class.
 *
 * @package WooCommerce\Tests\Gateways\Paypal
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\Buttons as PayPalButtons;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Class ButtonsTest.
 */
class ButtonsTest extends \WC_Unit_Test_Case {

	/**
	 * The buttons instance.
	 *
	 * @var PayPalButtons
	 */
	private PayPalButtons $buttons;

	/**
	 * Mock gateway instance.
	 *
	 * @var \WC_Gateway_Paypal
	 */
	private \WC_Gateway_Paypal $mock_gateway;

	/**
	 * Original global post.
	 *
	 * @var \WP_Post|null
	 */
	private ?\WP_Post $original_post;

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->markTestSkipped( 'Skipping PayPal Buttons tests.' );

		// Store original global post.
		global $post;
		$this->original_post = $post;

		// Create a mock gateway.
		$this->mock_gateway           = $this->createMock( \WC_Gateway_Paypal::class );
		$this->mock_gateway->email    = 'paypalmerchant@paypal.com';
		$this->mock_gateway->testmode = false;
		$this->mock_gateway->method( 'should_use_orders_v2' )->willReturn( true );
		$this->mock_gateway->method( 'get_option' )->willReturnMap(
			array(
				array( 'paypal_buttons', 'yes', 'yes' ),
				array( 'paymentaction', 'sale', 'sale' ),
			)
		);

		$this->buttons = new PayPalButtons( $this->mock_gateway );
	}

	/**
	 * Tear down the test environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_paypal_client_id_live' );
		delete_option( 'woocommerce_paypal_client_id_sandbox' );

		// Restore original global post.
		global $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $this->original_post;

		// Remove any filters that might have been added.
		remove_all_filters( 'woocommerce_is_checkout' );
		remove_all_filters( 'woocommerce_is_cart' );
		remove_all_filters( 'woocommerce_is_product' );

		parent::tearDown();
	}

	/**
	 * Test get_options returns correct structure with common options and specific options.
	 *
	 * @return void
	 */
	public function test_get_options_returns_correct_structure(): void {
		// Mock get_client_id and get_page_type to return test values.
		$buttons = $this->getMockBuilder( PayPalButtons::class )
			->setConstructorArgs( array( $this->mock_gateway ) )
			->onlyMethods( array( 'get_client_id', 'get_page_type' ) )
			->getMock();

		$buttons->method( 'get_client_id' )->willReturn( 'test_client_id' );
		$buttons->method( 'get_page_type' )->willReturn( 'checkout' );

		$options = $buttons->get_options();

		$this->assertIsArray( $options );
		$this->assertEquals( 'test_client_id', $options['client-id'] );
		$this->assertEquals( 'Woo_Cart_CoreUpgrade', $options['partner-attribution-id'] );
		$this->assertEquals( 'checkout', $options['page-type'] );
		$this->assertEquals( 'capture', $options['intent'] );
		$this->assertEquals( 'USD', $options['currency'] );
		$this->assertEquals( 'venmo,paylater', $options['enable-funding'] );
		$this->assertEquals( 'buttons,funding-eligibility,messages', $options['components'] );
		$this->assertEquals( 'card,applepay', $options['disable-funding'] );
		$this->assertEquals( 'paypalmerchant@paypal.com', $options['merchant-id'] );
	}

	/**
	 * Test get_common_options returns correct default values.
	 *
	 * @return void
	 */
	public function test_get_common_options_returns_correct_defaults(): void {
		// Mock get_client_id to return a test client ID.
		$buttons = $this->getMockBuilder( PayPalButtons::class )
			->setConstructorArgs( array( $this->mock_gateway ) )
			->onlyMethods( array( 'get_client_id' ) )
			->getMock();

		$buttons->method( 'get_client_id' )->willReturn( 'test_client_id' );

		$common_options = $buttons->get_common_options();

		$this->assertIsArray( $common_options );
		$this->assertEquals( 'test_client_id', $common_options['client-id'] );
		$this->assertEquals( 'buttons,funding-eligibility,messages', $common_options['components'] );
		$this->assertEquals( 'card,applepay', $common_options['disable-funding'] );
		$this->assertEquals( 'venmo,paylater', $common_options['enable-funding'] );
		$this->assertEquals( 'USD', $common_options['currency'] );
		$this->assertEquals( 'capture', $common_options['intent'] );
		$this->assertEquals( 'paypalmerchant@paypal.com', $common_options['merchant-id'] );
	}

	/**
	 * Test get_page_type returns correct values based on page context.
	 *
	 * @param bool   $is_cart Whether the current page is a cart page.
	 * @param string $expected_page_type The expected page type.
	 * @return void
	 *
	 * @dataProvider provider_page_type_scenarios
	 */
	public function test_get_page_type_returns_correct_value( $is_cart, $expected_page_type ): void {
		// Mock WordPress conditional functions using filters.
		if ( $is_cart ) {
			add_filter( 'woocommerce_is_cart', '__return_true' );
		} else {
			add_filter( 'woocommerce_is_cart', '__return_false' );
		}

		$page_type = $this->buttons->get_page_type();

		$this->assertEquals( $expected_page_type, $page_type );
	}

	/**
	 * Data provider for page type test scenarios.
	 *
	 * @return array
	 */
	public function provider_page_type_scenarios(): array {
		return array(
			'cart_page'     => array(
				'is_cart'            => true,
				'expected_page_type' => 'cart',
			),
			'checkout_page' => array(
				'is_cart'            => false,
				'expected_page_type' => 'checkout',
			),
		);
	}

	/**
	 * Test get_client_id returns null when Orders v2 is not enabled.
	 *
	 * @return void
	 */
	public function test_get_client_id_returns_null_when_orders_v2_disabled(): void {
		$this->mock_gateway->method( 'should_use_orders_v2' )->willReturn( false );

		$buttons = new PayPalButtons( $this->mock_gateway );

		$this->assertNull( $buttons->get_client_id() );
	}

	/**
	 * Test get_client_id returns cached value when available.
	 *
	 * @return void
	 */
	public function test_get_client_id_returns_cached_value(): void {
		$this->mock_gateway->testmode = false;

		// Set cached client ID.
		update_option( 'woocommerce_paypal_client_id_live', 'cached_client_id' );

		$client_id = $this->buttons->get_client_id();

		$this->assertEquals( 'cached_client_id', $client_id );
	}

	/**
	 * Test get_client_id uses sandbox option when testmode is enabled.
	 *
	 * @return void
	 */
	public function test_get_client_id_uses_sandbox_option_in_testmode(): void {
		$this->mock_gateway->testmode = true;

		// Set sandbox client ID.
		update_option( 'woocommerce_paypal_client_id_sandbox', 'sandbox_client_id' );

		$client_id = $this->buttons->get_client_id();

		$this->assertEquals( 'sandbox_client_id', $client_id );
	}

	/**
	 * Test get_client_id fetches from API when not cached.
	 *
	 * @return void
	 */
	public function test_get_client_id_fetches_from_api_when_not_cached(): void {
		$mock_request = $this->createMock( \WC_Gateway_Paypal_Request::class );
		$mock_request->method( 'fetch_paypal_client_id' )->willReturn( 'test_client_id' );

		$buttons = new PayPalButtons( $this->mock_gateway );

		$reflection       = new \ReflectionClass( $buttons );
		$request_property = $reflection->getProperty( 'request' );
		$request_property->setAccessible( true );
		$request_property->setValue( $buttons, $mock_request );

		$client_id = $buttons->get_client_id();

		$this->assertEquals( 'test_client_id', $client_id );
		$this->assertEquals( 'test_client_id', get_option( 'woocommerce_paypal_client_id_live' ) );
	}

	/**
	 * Test get_client_id returns null when API fails.
	 *
	 * @return void
	 */
	public function test_get_client_id_returns_null_when_api_fails(): void {
		$mock_request = $this->createMock( \WC_Gateway_Paypal_Request::class );
		$mock_request->method( 'fetch_paypal_client_id' )->willReturn( '' );

		$buttons = new PayPalButtons( $this->mock_gateway );

		// Use reflection to set the request property.
		$reflection       = new \ReflectionClass( $buttons );
		$request_property = $reflection->getProperty( 'request' );
		$request_property->setAccessible( true );
		$request_property->setValue( $buttons, $mock_request );

		$client_id = $buttons->get_client_id();

		$this->assertNull( $client_id );
	}

	/**
	 * Test get_current_page_for_app_switch returns URL for allowed pages.
	 *
	 * @dataProvider provider_app_switch_url_scenarios
	 *
	 * @param string $page_type The page type.
	 * @param bool   $is_checkout Whether the current page is checkout.
	 * @param bool   $is_cart Whether the current page is cart.
	 * @param string $post_type The post type.
	 * @param bool   $expected_contains Whether the expected contains.
	 * @return void
	 */
	public function test_get_current_page_for_app_switch(
		string $page_type,
		bool $is_checkout,
		bool $is_cart,
		string $post_type,
		bool $expected_contains
	): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'  => "Test {$page_type}",
				'post_type'   => $post_type,
				'post_status' => 'publish',
			)
		);

		// Set global post.
		global $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $post_id );

		// Mock the appropriate page type.
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'is_checkout' => fn() => $is_checkout,
			)
		);

		if ( $is_cart ) {
			add_filter( 'woocommerce_is_cart', '__return_true' );
		} else {
			add_filter( 'woocommerce_is_cart', '__return_false' );
		}

		$url = $this->buttons->get_current_page_for_app_switch();

		if ( $expected_contains ) {
			$this->assertNotEmpty( $url );
			$this->assertStringContainsString( (string) $post_id, $url );
		} else {
			$this->assertEquals( '', $url );
		}

		// Clean up.
		wp_delete_post( $post_id, true );

		wc_get_container()->get( LegacyProxy::class )->reset();

		if ( $is_cart ) {
			remove_filter( 'woocommerce_is_cart', '__return_true' );
		} else {
			remove_filter( 'woocommerce_is_cart', '__return_false' );
		}
	}

	/**
	 * Data provider for app switch URL test scenarios.
	 *
	 * @return array
	 */
	public function provider_app_switch_url_scenarios(): array {
		return array(
			'checkout_page' => array(
				'page_type'         => 'checkout',
				'is_checkout'       => true,
				'is_cart'           => false,
				'post_type'         => 'page',
				'expected_contains' => true,
			),
			'cart_page'     => array(
				'page_type'         => 'cart',
				'is_checkout'       => false,
				'is_cart'           => true,
				'post_type'         => 'page',
				'expected_contains' => true,
			),
			'other_page'    => array(
				'page_type'         => 'other',
				'is_checkout'       => false,
				'is_cart'           => false,
				'post_type'         => 'page',
				'expected_contains' => false,
			),
		);
	}

	/**
	 * Test get_current_page_for_app_switch returns empty string for other pages.
	 *
	 * @return void
	 */
	public function test_get_current_page_for_app_switch_returns_empty_for_other_pages(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'  => 'Test Page',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		global $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $post_id );

		// Mock all page types to return false.
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'is_checkout' => fn() => false,
			)
		);
		add_filter( 'woocommerce_is_cart', '__return_false' );

		$url = $this->buttons->get_current_page_for_app_switch();

		$this->assertEquals( '', $url );

		// Clean up.
		wp_delete_post( $post_id, true );
		wc_get_container()->get( LegacyProxy::class )->reset();
		remove_filter( 'woocommerce_is_cart', '__return_false' );
	}

	/**
	 * Test is_enabled returns the correct boolean value.
	 *
	 * @dataProvider provider_is_enabled_scenarios
	 *
	 * @param bool   $orders_v2_enabled Whether Orders v2 should be enabled.
	 * @param string $buttons_option    The buttons option value ('yes' or 'no').
	 * @param bool   $expected_result   The expected result from is_enabled().
	 * @param string $description       Description of the test scenario.
	 * @return void
	 */
	public function test_is_enabled_returns_correct_value(
		bool $orders_v2_enabled,
		string $buttons_option,
		bool $expected_result,
		string $description
	): void {
		// Create a fresh mock gateway for each test scenario.
		$mock_gateway           = $this->createMock( \WC_Gateway_Paypal::class );
		$mock_gateway->email    = 'paypalmerchant@paypal.com';
		$mock_gateway->testmode = false;

		$mock_gateway->method( 'should_use_orders_v2' )->willReturn( $orders_v2_enabled );
		$mock_gateway->method( 'get_option' )->with( 'paypal_buttons', 'yes' )->willReturn( $buttons_option );

		$buttons = new PayPalButtons( $mock_gateway );

		$this->assertEquals( $expected_result, $buttons->is_enabled(), $description );
	}

	/**
	 * Data provider for is_enabled test scenarios.
	 *
	 * @return array
	 */
	public function provider_is_enabled_scenarios(): array {
		return array(
			'enabled_when_orders_v2_and_buttons_enabled' => array(
				'orders_v2_enabled' => true,
				'buttons_option'    => 'yes',
				'expected_result'   => true,
				'description'       => 'Should be enabled when Orders v2 is enabled and buttons option is yes',
			),
			'disabled_when_buttons_option_no'            => array(
				'orders_v2_enabled' => true,
				'buttons_option'    => 'no',
				'expected_result'   => false,
				'description'       => 'Should be disabled when buttons option is no',
			),
			'disabled_when_orders_v2_disabled'           => array(
				'orders_v2_enabled' => false,
				'buttons_option'    => 'yes',
				'expected_result'   => false,
				'description'       => 'Should be disabled when Orders v2 is disabled',
			),
			'disabled_when_both_disabled'                => array(
				'orders_v2_enabled' => false,
				'buttons_option'    => 'no',
				'expected_result'   => false,
				'description'       => 'Should be disabled when both Orders v2 and buttons are disabled',
			),
		);
	}
}
