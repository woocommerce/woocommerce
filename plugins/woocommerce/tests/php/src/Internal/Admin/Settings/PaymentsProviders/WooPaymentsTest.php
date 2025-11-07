<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\Jetpack\Connection\Manager as WPCOM_Connection_Manager;
use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsRestController;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * WooPayments payment gateway provider service test.
 *
 * @class WooPayments
 */
class WooPaymentsTest extends WC_Unit_Test_Case {

	/**
	 * @var WooPaymentsRestController|MockObject
	 */
	protected $mock_rest_controller;

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * @var WPCOM_Connection_Manager|MockObject
	 */
	protected $mock_wpcom_connection_manager;

	/**
	 * @var object&MockObject
	 */
	protected $mock_woopayments_container;

	/**
	 * @var object&MockObject
	 */
	protected $mock_woopayments_account_service;

	/**
	 * @var object&MockObject
	 */
	protected $mock_woopayments_mode;

	/**
	 * @var WooPayments
	 */
	protected WooPayments $sut;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->setup_woopayments_container_mock();
		$this->setup_woopayments_mode_mock();
		$this->setup_wpcom_connection_mock();
		$this->setup_woopayments_account_service_mock();
		// Mock the WC WooPayments REST controller.
		$this->setup_woopayments_reset_controller_mock();
		// Finally, set up the mockable proxy.
		$this->setup_legacy_proxy_mocks();

		$this->sut = new WooPayments( $this->mockable_proxy );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		$this->mockable_proxy->reset();

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$container->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * Test get_details without country code.
	 */
	public function test_get_details_without_country_code() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'                     => true,
				'account_connected'           => true,
				'needs_setup'                 => true,
				'test_mode'                   => true,
				'dev_mode'                    => true,
				'onboarding_started'          => true,
				'onboarding_completed'        => true,
				'test_mode_onboarding'        => true,
				'plugin_slug'                 => 'woocommerce-payments',
				'plugin_file'                 => 'woocommerce-payments/woocommerce-payments.php',
				'method_title'                => 'WooPayments has a very long title that should be truncated after some length like this',
				'method_description'          => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
				'supports'                    => array( 'products', 'something', 'bogus' ),
				'icon'                        => 'https://example.com/icon.png',
				'recommended_payment_methods' => array(
					// Basic PM.
					array(
						'id'       => 'basic',
						// No order, should be last.
						'enabled'  => true,
						'title'    => 'Title',
						'category' => PaymentGateway::PAYMENT_METHOD_CATEGORY_SECONDARY,
					),
					// Basic PM with priority instead of order.
					array(
						'id'       => 'basic2',
						'priority' => 30,
						'enabled'  => false,
						'title'    => 'Title',
						'category' => 'unknown', // This should be ignored and replaced with the default category (primary).
					),
					array(
						'id'          => 'card',
						'order'       => 20,
						'enabled'     => true,
						'required'    => true,
						'title'       => '<b>Credit/debit card (required)</b>', // All tags should be stripped.
						// Paragraphs and line breaks should be stripped.
						'description' => '<p><strong>Accepts</strong> <b>all major</b></br><em>credit</em> and <a href="#" target="_blank">debit cards</a>.</p>',
						'icon'        => 'https://example.com/card-icon.png',
						// No category means it should be primary (default category).
					),
					array(
						'id'          => 'woopay',
						'order'       => 10,
						'enabled'     => false,
						'title'       => 'WooPay',
						'description' => 'WooPay express checkout',
						// Not a good URL.
						'icon'        => 'not_good_url/icon.svg',
						'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY,
					),
					// Invalid PM, should be ignored. No data.
					array(),
					// Invalid PM, should be ignored. No ID.
					array( 'title' => 'Card' ),
					// Invalid PM, should be ignored. No title.
					array( 'id' => 'card' ),
				),
			),
		);

		// Arrange the WooPayments mode.
		$this->mock_woopayments_mode
			->method( 'is_test' )
			->willReturn( true );
		$this->mock_woopayments_mode
			->method( 'is_test_mode_onboarding' )
			->willReturn( true );
		$this->mock_woopayments_mode
			->method( 'is_dev' )
			->willReturn( true );

		// Arrange the WPCOM connection as fully working.
		$this->mock_wpcom_connection();

		// Arrange the version constant to meet the minimum requirements for the native in-context onboarding.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$container->replace( WooPaymentsRestController::class, $this->mock_rest_controller );

		try {
			// Act.
			$gateway_details = $this->sut->get_details( $fake_gateway, 999 );

			// Assert - Use targeted assertions for resilient testing.
			// Basic gateway details.
			$this->assertSame( 'woocommerce_payments', $gateway_details['id'] );
			$this->assertSame( 999, $gateway_details['_order'] );
			$this->assertSame( 'WooPayments has a very long title that should be truncated after some length', $gateway_details['title'] );
			$this->assertStringStartsWith( 'Lorem ipsum dolor sit amet', $gateway_details['description'] );
			$this->assertSame( 'https://example.com/icon.png', $gateway_details['icon'] );
			$this->assertSame( array( 'products', 'something', 'bogus' ), $gateway_details['supports'] );
			$this->assertSame( array(), $gateway_details['links'] );

			// State.
			$this->assertArrayHasKey( 'state', $gateway_details );
			$this->assertTrue( $gateway_details['state']['enabled'] );
			$this->assertTrue( $gateway_details['state']['account_connected'] );
			$this->assertTrue( $gateway_details['state']['needs_setup'] );
			$this->assertTrue( $gateway_details['state']['test_mode'] );
			$this->assertTrue( $gateway_details['state']['dev_mode'] );

			// Management.
			$this->assertArrayHasKey( 'management', $gateway_details );
			$this->assertArrayHasKey( '_links', $gateway_details['management'] );
			$this->assertArrayHasKey( 'settings', $gateway_details['management']['_links'] );
			$this->assertStringContainsString( 'admin.php?page=wc-settings', $gateway_details['management']['_links']['settings']['href'] );

			// Plugin.
			$this->assertArrayHasKey( 'plugin', $gateway_details );
			$this->assertSame( PaymentsProviders::EXTENSION_TYPE_WPORG, $gateway_details['plugin']['_type'] );
			$this->assertSame( 'woocommerce-payments', $gateway_details['plugin']['slug'] );
			$this->assertSame( 'woocommerce-payments/woocommerce-payments', $gateway_details['plugin']['file'] );
			$this->assertSame( PaymentsProviders::EXTENSION_ACTIVE, $gateway_details['plugin']['status'] );

			// Onboarding - Type.
			$this->assertArrayHasKey( 'onboarding', $gateway_details );
			$this->assertSame( PaymentGateway::ONBOARDING_TYPE_NATIVE_IN_CONTEXT, $gateway_details['onboarding']['type'] );

			// Onboarding - State.
			$this->assertArrayHasKey( 'state', $gateway_details['onboarding'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['supported'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['started'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['completed'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['test_mode'] );
			$this->assertFalse( $gateway_details['onboarding']['state']['test_drive_account'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['wpcom_has_working_connection'] );

			// Onboarding - Messages.
			$this->assertArrayHasKey( 'messages', $gateway_details['onboarding'] );
			$this->assertArrayHasKey( 'not_supported', $gateway_details['onboarding']['messages'] );
			$this->assertNull( $gateway_details['onboarding']['messages']['not_supported'] );

			// Onboarding - Links.
			$this->assertArrayHasKey( '_links', $gateway_details['onboarding'] );
			$this->assertArrayHasKey( 'onboard', $gateway_details['onboarding']['_links'] );
			$this->assertStringContainsString( '/woopayments/onboarding', $gateway_details['onboarding']['_links']['onboard']['href'] );
			$this->assertArrayHasKey( 'reset', $gateway_details['onboarding']['_links'] );
			$this->assertStringContainsString( '/onboarding/reset', $gateway_details['onboarding']['_links']['reset']['href'] );

			// Onboarding - Recommended payment methods.
			$this->assertArrayHasKey( 'recommended_payment_methods', $gateway_details['onboarding'] );
			$recommended_pms = $gateway_details['onboarding']['recommended_payment_methods'];
			$this->assertCount( 4, $recommended_pms );

			// Check first payment method (woopay) - ordered first, disabled, invalid icon removed.
			$this->assertSame( 'woopay', $recommended_pms[0]['id'] );
			$this->assertSame( 0, $recommended_pms[0]['_order'] );
			$this->assertFalse( $recommended_pms[0]['enabled'] );
			$this->assertFalse( $recommended_pms[0]['required'] );
			$this->assertSame( 'WooPay', $recommended_pms[0]['title'] );
			$this->assertSame( '', $recommended_pms[0]['icon'] ); // Invalid URL removed.
			$this->assertSame( PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY, $recommended_pms[0]['category'] );

			// Check second payment method (card) - required, HTML tags stripped but content preserved.
			$this->assertSame( 'card', $recommended_pms[1]['id'] );
			$this->assertSame( 1, $recommended_pms[1]['_order'] );
			$this->assertTrue( $recommended_pms[1]['enabled'] );
			$this->assertTrue( $recommended_pms[1]['required'] );
			$this->assertSame( 'Credit/debit card (required)', $recommended_pms[1]['title'] );
			$this->assertStringContainsString( 'Accepts', $recommended_pms[1]['description'] );
			$this->assertStringContainsString( 'debit cards', $recommended_pms[1]['description'] );
			$this->assertSame( 'https://example.com/card-icon.png', $recommended_pms[1]['icon'] );

			// Check third payment method (basic2) - uses priority, category normalized.
			$this->assertSame( 'basic2', $recommended_pms[2]['id'] );
			$this->assertSame( 2, $recommended_pms[2]['_order'] );
			$this->assertFalse( $recommended_pms[2]['enabled'] );
			$this->assertSame( PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY, $recommended_pms[2]['category'] ); // 'unknown' normalized to primary.

			// Check fourth payment method (basic) - no order, placed last, secondary category.
			$this->assertSame( 'basic', $recommended_pms[3]['id'] );
			$this->assertSame( 3, $recommended_pms[3]['_order'] );
			$this->assertTrue( $recommended_pms[3]['enabled'] );
			$this->assertSame( PaymentGateway::PAYMENT_METHOD_CATEGORY_SECONDARY, $recommended_pms[3]['category'] );
		} finally {
			// Clean up.
			Constants::clear_constants();
			$container->reset_replacement( WooPaymentsRestController::class );
		}
	}

	/**
	 * Test get_details with country code integrates WooPaymentsService.
	 */
	public function test_get_details_with_country_code_integrates_service() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'              => true,
				'account_connected'    => true,
				'onboarding_started'   => true,
				'onboarding_completed' => false,
				'test_mode_onboarding' => false,
				'plugin_slug'          => 'woocommerce-payments',
				'plugin_file'          => 'woocommerce-payments/woocommerce-payments.php',
			),
		);

		// Arrange the WooPayments mode.
		$this->mock_woopayments_mode
			->method( 'is_test' )
			->willReturn( true );
		$this->mock_woopayments_mode
			->method( 'is_test_mode_onboarding' )
			->willReturn( false );
		$this->mock_woopayments_mode
			->method( 'is_dev' )
			->willReturn( true );

		// Arrange the WPCOM connection as fully working.
		$this->mock_wpcom_connection();

		// Arrange the version constant to meet the minimum requirements.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		// Mock the WooPaymentsService to return onboarding details.
		$mock_service = $this->createMock( WooPaymentsService::class );
		$mock_service
			->expects( $this->once() )
			->method( 'get_onboarding_details' )
			->with( 'US', '/some/rest/for/woopayments/onboarding' )
			->willReturn(
				array(
					'state'    => array(
						'supported' => true,
						'started'   => true,
						'completed' => false,
					),
					'messages' => array(
						'not_supported' => null,
						'custom'        => 'Custom message from service',
					),
					'steps'    => array(
						array(
							'id'    => 'step1',
							'title' => 'Step 1',
						),
					),
					'context'  => array(
						'custom_context' => 'value',
					),
				)
			);

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$container->replace( WooPaymentsRestController::class, $this->mock_rest_controller );
		$container->replace( WooPaymentsService::class, $mock_service );

		try {
			// Act.
			$gateway_details = $this->sut->get_details( $fake_gateway, 0, 'US' );

			// Assert that the service details are merged.
			$this->assertArrayHasKey( 'onboarding', $gateway_details );
			$this->assertArrayHasKey( 'state', $gateway_details['onboarding'] );
			$this->assertArrayHasKey( 'supported', $gateway_details['onboarding']['state'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['supported'] );

			// Assert that the onboarding state was merged from the service.
			// The service returns started=true and completed=false, which overrides the gateway values.
			$this->assertFalse( $gateway_details['onboarding']['state']['completed'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['started'] );

			// Assert messages exist (the parent class sets not_supported).
			$this->assertArrayHasKey( 'messages', $gateway_details['onboarding'] );
			$this->assertArrayHasKey( 'not_supported', $gateway_details['onboarding']['messages'] );
		} finally {
			// Clean up.
			Constants::clear_constants();
			$container->reset_replacement( WooPaymentsRestController::class );
			$container->reset_replacement( WooPaymentsService::class );
		}
	}

	/**
	 * Test get_details with country code handles service error gracefully.
	 */
	public function test_get_details_with_country_code_handles_service_error() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'              => true,
				'account_connected'    => true,
				'onboarding_started'   => true,
				'onboarding_completed' => false,
				'plugin_slug'          => 'woocommerce-payments',
				'plugin_file'          => 'woocommerce-payments/woocommerce-payments.php',
			),
		);

		// Arrange the WooPayments mode.
		$this->mock_woopayments_mode
			->method( 'is_test' )
			->willReturn( false );
		$this->mock_woopayments_mode
			->method( 'is_test_mode_onboarding' )
			->willReturn( false );
		$this->mock_woopayments_mode
			->method( 'is_dev' )
			->willReturn( false );

		// Arrange the WPCOM connection as fully working.
		$this->mock_wpcom_connection();

		// Arrange the version constant.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		// Mock the service to throw an exception.
		$mock_service = $this->createMock( WooPaymentsService::class );
		$mock_service
			->method( 'get_onboarding_details' )
			->willThrowException( new \Exception( 'Service error' ) );

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$container->replace( WooPaymentsRestController::class, $this->mock_rest_controller );
		$container->replace( WooPaymentsService::class, $mock_service );

		try {
			// Act - should not throw exception.
			$gateway_details = $this->sut->get_details( $fake_gateway, 0, 'US' );

			// Assert that details are still returned despite service error.
			$this->assertArrayHasKey( 'onboarding', $gateway_details );
			$this->assertArrayHasKey( 'state', $gateway_details['onboarding'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['supported'] );
		} finally {
			// Clean up.
			Constants::clear_constants();
			$container->reset_replacement( WooPaymentsRestController::class );
			$container->reset_replacement( WooPaymentsService::class );
		}
	}

	/**
	 * Test is_onboarding_supported returns true for supported countries when gateway doesn't provide method.
	 */
	public function test_is_onboarding_supported_with_supported_country() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Act.
		$is_supported = $this->sut->is_onboarding_supported( $fake_gateway, 'US' );

		// Assert.
		$this->assertTrue( $is_supported );

		// Test with lowercase country code.
		$is_supported = $this->sut->is_onboarding_supported( $fake_gateway, 'gb' );
		$this->assertTrue( $is_supported );
	}

	/**
	 * Test is_onboarding_supported returns false for unsupported countries.
	 */
	public function test_is_onboarding_supported_with_unsupported_country() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'onboarding_supported' => null, // Ensure gateway doesn't provide info.
			)
		);

		// Act - testing with a country definitely not in the supported list.
		$is_supported = $this->sut->is_onboarding_supported( $fake_gateway, 'XX' );

		// Assert.
		$this->assertFalse( $is_supported );
	}

	/**
	 * Test is_onboarding_supported returns true when no country code provided.
	 */
	public function test_is_onboarding_supported_without_country_code() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Act.
		$is_supported = $this->sut->is_onboarding_supported( $fake_gateway, '' );

		// Assert - should default to true to avoid blocking users.
		$this->assertTrue( $is_supported );
	}

	/**
	 * Test is_onboarding_supported defers to gateway method when available.
	 */
	public function test_is_onboarding_supported_defers_to_gateway_method() {
		// Arrange - Create a gateway that provides the is_onboarding_supported method.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'onboarding_supported' => false,
			)
		);

		// Act.
		$is_supported = $this->sut->is_onboarding_supported( $fake_gateway, 'XX' );

		// Assert - should return the gateway's value, not fall through to WooPayments logic.
		$this->assertFalse( $is_supported );
	}

	/**
	 * Test get_onboarding_not_supported_message returns WooPayments-specific message.
	 */
	public function test_get_onboarding_not_supported_message() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Act.
		$message = $this->sut->get_onboarding_not_supported_message( $fake_gateway, 'XX' );

		// Assert.
		$this->assertStringContainsString( 'WooPayments', $message );
		$this->assertStringContainsString( 'not supported', $message );
		$this->assertStringContainsString( 'business location', $message );
	}

	/**
	 * Test get_onboarding_not_supported_message defers to gateway method when available.
	 */
	public function test_get_onboarding_not_supported_message_defers_to_gateway_method() {
		// Arrange - Create a gateway with a custom message.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'onboarding_not_supported_message' => 'Custom unsupported message from gateway',
			)
		);

		// Act.
		$message = $this->sut->get_onboarding_not_supported_message( $fake_gateway, 'XX' );

		// Assert - should return the gateway's message.
		$this->assertEquals( 'Custom unsupported message from gateway', $message );
	}

	/**
	 * Test get_details includes supported state in onboarding section.
	 */
	public function test_get_details_includes_supported_state() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => true,
				'plugin_slug'       => 'woocommerce-payments',
				'plugin_file'       => 'woocommerce-payments/woocommerce-payments.php',
			),
		);

		// Arrange the WooPayments mode.
		$this->mock_woopayments_mode
			->method( 'is_test' )
			->willReturn( false );
		$this->mock_woopayments_mode
			->method( 'is_test_mode_onboarding' )
			->willReturn( false );
		$this->mock_woopayments_mode
			->method( 'is_dev' )
			->willReturn( false );

		// Arrange the version constant.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		try {
			// Act.
			$gateway_details = $this->sut->get_details( $fake_gateway, 0 );

			// Assert - when no country code is provided, supported state should be true by default.
			$this->assertArrayHasKey( 'onboarding', $gateway_details );
			$this->assertArrayHasKey( 'state', $gateway_details['onboarding'] );
			$this->assertArrayHasKey( 'supported', $gateway_details['onboarding']['state'] );
			$this->assertTrue( $gateway_details['onboarding']['state']['supported'] );
		} finally {
			// Clean up.
			Constants::clear_constants();
		}
	}

	/**
	 * Test is_onboarding_supported with supported country when gateway returns null (unknown).
	 *
	 * To lock in tri-state behavior: gateway returns null, WooPayments resolves via country list.
	 */
	public function test_is_onboarding_supported_with_supported_country_when_gateway_unknown() {
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array( 'onboarding_supported' => null )
		);

		$this->assertTrue( $this->sut->is_onboarding_supported( $fake_gateway, 'GB' ) );
	}

	/**
	 * Test get_details with unsupported country code.
	 */
	public function test_get_details_with_unsupported_country() {
		// Arrange - Create a gateway that doesn't support onboarding for a specific country.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'                          => true,
				'account_connected'                => true,
				'onboarding_supported'             => false,
				'onboarding_not_supported_message' => 'WooPayments is not available in your country.',
				'plugin_slug'                      => 'woocommerce-payments',
				'plugin_file'                      => 'woocommerce-payments/woocommerce-payments.php',
			)
		);

		// Arrange the WooPayments mode.
		$this->mock_woopayments_mode
			->method( 'is_test' )
			->willReturn( false );
		$this->mock_woopayments_mode
			->method( 'is_test_mode_onboarding' )
			->willReturn( false );
		$this->mock_woopayments_mode
			->method( 'is_dev' )
			->willReturn( false );

		// Arrange the version constant.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		try {
			// Act.
			$gateway_details = $this->sut->get_details( $fake_gateway, 0, 'XX' );

			// Assert - should include the unsupported state and message.
			$this->assertArrayHasKey( 'onboarding', $gateway_details );
			$this->assertArrayHasKey( 'state', $gateway_details['onboarding'] );
			$this->assertArrayHasKey( 'supported', $gateway_details['onboarding']['state'] );
			$this->assertFalse( $gateway_details['onboarding']['state']['supported'] );

			$this->assertArrayHasKey( 'messages', $gateway_details['onboarding'] );
			$this->assertArrayHasKey( 'not_supported', $gateway_details['onboarding']['messages'] );
			$this->assertEquals( 'WooPayments is not available in your country.', $gateway_details['onboarding']['messages']['not_supported'] );
		} finally {
			// Clean up.
			Constants::clear_constants();
		}
	}

	/**
	 * Test enhance_extension_suggestion with compatible installed version.
	 */
	public function test_enhance_extension_suggestion_with_compatible_installed_version() {
		// Arrange - Mock PluginsHelper to return compatible version (>= 9.3.0).
		$this->mockable_proxy->register_static_mocks(
			array(
				PluginsHelper::class => array(
					'get_plugin_data' => function ( $plugin_file ) {
						if ( 'woocommerce-payments/woocommerce-payments.php' === $plugin_file ) {
							return array(
								'Version' => '10.0.0', // Compatible version >= 9.3.0.
							);
						}
						return false;
					},
				),
			)
		);

		// Arrange - Extension suggestion for installed WooPayments with compatible version.
		$extension_suggestion = array(
			'id'         => 'woocommerce-payments',
			'plugin'     => array(
				'file'   => 'woocommerce-payments/woocommerce-payments',
				'status' => PaymentsProviders::EXTENSION_ACTIVE,
			),
			'onboarding' => array(),
		);

		// Arrange the WPCOM connection as fully working.
		$this->mock_wpcom_connection();

		// Act.
		$enhanced = $this->sut->enhance_extension_suggestion( $extension_suggestion );

		// Assert - Should set native in-context onboarding type.
		$this->assertArrayHasKey( 'onboarding', $enhanced );
		$this->assertArrayHasKey( 'type', $enhanced['onboarding'] );
		$this->assertSame( PaymentGateway::ONBOARDING_TYPE_NATIVE_IN_CONTEXT, $enhanced['onboarding']['type'] );

		// Assert - Should include WPCOM connection state.
		$this->assertArrayHasKey( 'state', $enhanced['onboarding'] );
		$this->assertArrayHasKey( 'wpcom_has_working_connection', $enhanced['onboarding']['state'] );
		$this->assertArrayHasKey( 'wpcom_is_store_connected', $enhanced['onboarding']['state'] );

		// Assert - Should not include preload link when WPCOM is connected.
		$this->assertArrayNotHasKey( 'preload', $enhanced['onboarding']['_links'] ?? array() );
	}

	/**
	 * Test enhance_extension_suggestion with incompatible installed version.
	 */
	public function test_enhance_extension_suggestion_with_incompatible_installed_version() {
		// Arrange - Mock PluginsHelper to return old version.
		$this->mockable_proxy->register_static_mocks(
			array(
				PluginsHelper::class => array(
					'get_plugin_data' => function ( $plugin_file ) {
						if ( 'woocommerce-payments/woocommerce-payments.php' === $plugin_file ) {
							return array(
								'Version' => '3.0.0', // Below minimum.
							);
						}
						return false;
					},
				),
			)
		);

		$extension_suggestion = array(
			'id'         => 'woocommerce-payments',
			'plugin'     => array(
				'file'   => 'woocommerce-payments/woocommerce-payments',
				'status' => PaymentsProviders::EXTENSION_ACTIVE,
			),
			'onboarding' => array(),
		);

		// Act.
		$enhanced = $this->sut->enhance_extension_suggestion( $extension_suggestion );

		// Assert - Should fall back to external onboarding type (from parent).
		$this->assertArrayHasKey( 'onboarding', $enhanced );
		$this->assertArrayHasKey( 'type', $enhanced['onboarding'] );
		$this->assertSame( PaymentGateway::ONBOARDING_TYPE_EXTERNAL, $enhanced['onboarding']['type'] );
	}

	/**
	 * Test enhance_extension_suggestion when extension not installed.
	 */
	public function test_enhance_extension_suggestion_when_not_installed() {
		// Arrange - Extension suggestion for not-yet-installed WooPayments.
		$extension_suggestion = array(
			'id'         => 'woocommerce-payments',
			'plugin'     => array(
				'file'   => '',
				'status' => PaymentsProviders::EXTENSION_NOT_INSTALLED,
			),
			'onboarding' => array(),
		);

		// Act.
		$enhanced = $this->sut->enhance_extension_suggestion( $extension_suggestion );

		// Assert - Should assume latest version and set native in-context.
		$this->assertArrayHasKey( 'onboarding', $enhanced );
		$this->assertArrayHasKey( 'type', $enhanced['onboarding'] );
		$this->assertSame( PaymentGateway::ONBOARDING_TYPE_NATIVE_IN_CONTEXT, $enhanced['onboarding']['type'] );
	}

	/**
	 * Test enhance_extension_suggestion includes preload link without WPCOM connection.
	 */
	public function test_enhance_extension_suggestion_includes_preload_link_without_wpcom() {
		// Arrange - Mock WPCOM connection as not working.
		$this->mock_wpcom_connection_manager
			->method( 'is_connected' )
			->willReturn( false );
		$this->mock_wpcom_connection_manager
			->method( 'has_connected_owner' )
			->willReturn( false );

		$extension_suggestion = array(
			'id'         => 'woocommerce-payments',
			'plugin'     => array(
				'file'   => '',
				'status' => PaymentsProviders::EXTENSION_NOT_INSTALLED,
			),
			'onboarding' => array(),
		);

		// Arrange the WPCOM connection as not working.
		$this->mock_wpcom_connection( false, false, false );

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$container->replace( WooPaymentsRestController::class, $this->mock_rest_controller );

		try {
			// Act.
			$enhanced = $this->sut->enhance_extension_suggestion( $extension_suggestion );

			// Assert - Should include preload link when WPCOM not connected.
			$this->assertArrayHasKey( 'onboarding', $enhanced );
			$this->assertArrayHasKey( '_links', $enhanced['onboarding'] );
			$this->assertArrayHasKey( 'preload', $enhanced['onboarding']['_links'] );
			$this->assertStringContainsString( '/onboarding/preload', $enhanced['onboarding']['_links']['preload']['href'] );

			// Assert - WPCOM connection state should show not connected.
			$this->assertArrayHasKey( 'state', $enhanced['onboarding'] );
			$this->assertFalse( $enhanced['onboarding']['state']['wpcom_has_working_connection'] );
		} finally {
			$container->reset_replacement( WooPaymentsRestController::class );
		}
	}

	/**
	 * Test needs_setup returns true when account is not connected.
	 */
	public function test_needs_setup_when_account_not_connected() {
		// Arrange - Gateway without account connection.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => false,
			)
		);

		// Act.
		$needs_setup = $this->sut->needs_setup( $fake_gateway );

		// Assert - Should need setup when no account connected.
		$this->assertTrue( $needs_setup );
	}

	/**
	 * Test needs_setup returns false when test-drive account exists.
	 */
	public function test_needs_setup_with_test_drive_account() {
		// Arrange - Gateway with connected account.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => true,
			)
		);

		// Arrange - Mock test-drive account status.
		$this->mock_woopayments_account_service
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'testDrive' => true,
					'isLive'    => false,
				)
			);

		// Act.
		$needs_setup = $this->sut->needs_setup( $fake_gateway );

		// Assert - Test-drive accounts don't need setup.
		$this->assertFalse( $needs_setup );
	}

	/**
	 * Test needs_setup delegates to parent when account connected and not test-drive.
	 */
	public function test_needs_setup_delegates_to_parent_when_normal_account() {
		// Arrange - Gateway with connected account that needs setup (per parent logic).
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => true,
				'needs_setup'       => true,
			)
		);

		// Arrange - Mock normal account (not test-drive).
		$this->mock_woopayments_account_service
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'testDrive' => false,
					'isLive'    => true,
				)
			);

		// Act.
		$needs_setup = $this->sut->needs_setup( $fake_gateway );

		// Assert - Should delegate to parent and return its value.
		$this->assertTrue( $needs_setup );
	}

	/**
	 * Test needs_setup delegates to parent and returns false when setup complete.
	 */
	public function test_needs_setup_returns_false_when_setup_complete() {
		// Arrange - Gateway fully configured.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => true,
				'needs_setup'       => false,
			)
		);

		// Arrange - Mock normal account.
		$this->mock_woopayments_account_service
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'testDrive' => false,
					'isLive'    => true,
				)
			);

		// Act.
		$needs_setup = $this->sut->needs_setup( $fake_gateway );

		// Assert - Should return false when setup complete.
		$this->assertFalse( $needs_setup );
	}

	/**
	 * Test is_in_test_mode returns true when WC_Payments mode reports test mode.
	 */
	public function test_is_in_test_mode_when_woopayments_reports_test() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Arrange - Mock WooPayments mode to return test mode.
		$this->mock_woopayments_mode
			->method( 'is_test' )
			->willReturn( true );

		// Act.
		$is_test_mode = $this->sut->is_in_test_mode( $fake_gateway );

		// Assert.
		$this->assertTrue( $is_test_mode );
	}

	/**
	 * Test is_in_test_mode returns false when WC_Payments mode reports live mode.
	 */
	public function test_is_in_test_mode_when_woopayments_reports_live() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Arrange - Mock WooPayments mode to return live mode.
		$this->mock_woopayments_mode
			->method( 'is_test' )
			->willReturn( false );

		// Act.
		$is_test_mode = $this->sut->is_in_test_mode( $fake_gateway );

		// Assert.
		$this->assertFalse( $is_test_mode );
	}

	/**
	 * Test is_in_test_mode delegates to parent when WC_Payments unavailable.
	 */
	public function test_is_in_test_mode_delegates_to_parent_when_woopayments_unavailable() {
		// Arrange - Gateway with test mode flag.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'test_mode' => true,
			)
		);

		// Arrange - Mock WC_Payments as not available.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_name ) {
					if ( 'WC_Payments' === $class_name ) {
						return false;
					}
					return false;
				},
			)
		);

		// Act.
		$is_test_mode = $this->sut->is_in_test_mode( $fake_gateway );

		// Assert - Should delegate to parent when WC_Payments unavailable.
		$this->assertTrue( $is_test_mode );
	}

	/**
	 * Test is_in_dev_mode returns true when WC_Payments mode reports dev mode.
	 */
	public function test_is_in_dev_mode_when_woopayments_reports_dev() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Arrange - Mock WooPayments mode to return dev mode.
		$this->mock_woopayments_mode
			->method( 'is_dev' )
			->willReturn( true );

		// Act.
		$is_dev_mode = $this->sut->is_in_dev_mode( $fake_gateway );

		// Assert.
		$this->assertTrue( $is_dev_mode );
	}

	/**
	 * Test is_in_dev_mode returns false when WC_Payments mode reports not dev mode.
	 */
	public function test_is_in_dev_mode_when_woopayments_reports_not_dev() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Arrange - Mock WooPayments mode to return not dev mode.
		$this->mock_woopayments_mode
			->method( 'is_dev' )
			->willReturn( false );

		// Act.
		$is_dev_mode = $this->sut->is_in_dev_mode( $fake_gateway );

		// Assert.
		$this->assertFalse( $is_dev_mode );
	}

	/**
	 * Test is_in_dev_mode delegates to parent when WC_Payments unavailable.
	 */
	public function test_is_in_dev_mode_delegates_to_parent_when_woopayments_unavailable() {
		// Arrange - Gateway with dev mode flag.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'dev_mode' => true,
			)
		);

		// Arrange - Mock WC_Payments as not available.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_name ) {
					if ( 'WC_Payments' === $class_name ) {
						return false;
					}
					return false;
				},
			)
		);

		// Act.
		$is_dev_mode = $this->sut->is_in_dev_mode( $fake_gateway );

		// Assert - Should delegate to parent when WC_Payments unavailable.
		$this->assertTrue( $is_dev_mode );
	}

	/**
	 * Test is_in_test_mode_onboarding returns true when WC_Payments mode reports test mode onboarding.
	 */
	public function test_is_in_test_mode_onboarding_when_woopayments_reports_test_mode() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Arrange - Mock WooPayments mode to return test mode onboarding.
		$this->mock_woopayments_mode
			->method( 'is_test_mode_onboarding' )
			->willReturn( true );

		// Act.
		$is_test_mode_onboarding = $this->sut->is_in_test_mode_onboarding( $fake_gateway );

		// Assert.
		$this->assertTrue( $is_test_mode_onboarding );
	}

	/**
	 * Test is_in_test_mode_onboarding returns false when WC_Payments mode reports not test mode onboarding.
	 */
	public function test_is_in_test_mode_onboarding_when_woopayments_reports_not_test_mode() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );

		// Arrange - Mock WooPayments mode to return not test mode onboarding.
		$this->mock_woopayments_mode
			->method( 'is_test_mode_onboarding' )
			->willReturn( false );

		// Act.
		$is_test_mode_onboarding = $this->sut->is_in_test_mode_onboarding( $fake_gateway );

		// Assert.
		$this->assertFalse( $is_test_mode_onboarding );
	}

	/**
	 * Test is_in_test_mode_onboarding delegates to parent when WC_Payments unavailable.
	 */
	public function test_is_in_test_mode_onboarding_delegates_to_parent_when_woopayments_unavailable() {
		// Arrange - Gateway with test mode onboarding flag.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'test_mode_onboarding' => true,
			)
		);

		// Arrange - Mock WC_Payments as not available.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_name ) {
					if ( 'WC_Payments' === $class_name ) {
						return false;
					}
					return false;
				},
			)
		);

		// Act.
		$is_test_mode_onboarding = $this->sut->is_in_test_mode_onboarding( $fake_gateway );

		// Assert - Should delegate to parent when WC_Payments unavailable.
		$this->assertTrue( $is_test_mode_onboarding );
	}

	/**
	 * Test get_onboarding_url with connected account returns URL with base params only.
	 */
	public function test_get_onboarding_url_with_connected_account() {
		// Arrange - Gateway with connected account.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => true,
			)
		);

		// Act.
		$onboarding_url = $this->sut->get_onboarding_url( $fake_gateway );

		// Assert - Should contain base URL and standard params but NO test_drive params.
		$this->assertStringContainsString( 'https://example.com/wp-admin/woopayments/connect-url', $onboarding_url );
		$this->assertStringContainsString( 'from=', $onboarding_url );
		$this->assertStringContainsString( 'source=', $onboarding_url );
		$this->assertStringContainsString( 'redirect_to_settings_page=true', $onboarding_url );
		// Should NOT include test_drive params for connected accounts.
		$this->assertStringNotContainsString( 'test_drive=true', $onboarding_url );
		$this->assertStringNotContainsString( 'auto_start_test_drive_onboarding=true', $onboarding_url );
	}

	/**
	 * Test get_onboarding_url for new store in coming soon mode with already selling profile.
	 */
	public function test_get_onboarding_url_coming_soon_mode_already_selling_online() {
		// Arrange - Gateway without connected account.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => false,
			)
		);

		// Arrange - Store in coming soon mode.
		update_option( 'woocommerce_coming_soon', 'yes' );

		// Arrange - Onboarding profile: already selling online.
		update_option(
			'woocommerce_onboarding_profile',
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => 'yes_im_selling_online',
			)
		);

		try {
			// Act.
			$onboarding_url = $this->sut->get_onboarding_url( $fake_gateway );

			// Assert - Should do LIVE onboarding (no test_drive params).
			$this->assertStringContainsString( 'https://example.com/wp-admin/woopayments/connect-url', $onboarding_url );
			$this->assertStringNotContainsString( 'test_drive=true', $onboarding_url );
			$this->assertStringNotContainsString( 'auto_start_test_drive_onboarding=true', $onboarding_url );
		} finally {
			// Clean up.
			delete_option( 'woocommerce_coming_soon' );
			delete_option( 'woocommerce_onboarding_profile' );
		}
	}

	/**
	 * Test get_onboarding_url for new store in coming soon mode with both online and offline.
	 */
	public function test_get_onboarding_url_coming_soon_mode_selling_both() {
		// Arrange - Gateway without connected account.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => false,
			)
		);

		// Arrange - Store in coming soon mode.
		update_option( 'woocommerce_coming_soon', 'yes' );

		// Arrange - Onboarding profile: selling both online and offline.
		update_option(
			'woocommerce_onboarding_profile',
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => 'im_selling_both_online_and_offline',
			)
		);

		try {
			// Act.
			$onboarding_url = $this->sut->get_onboarding_url( $fake_gateway );

			// Assert - Should do LIVE onboarding (no test_drive params).
			$this->assertStringContainsString( 'https://example.com/wp-admin/woopayments/connect-url', $onboarding_url );
			$this->assertStringNotContainsString( 'test_drive=true', $onboarding_url );
			$this->assertStringNotContainsString( 'auto_start_test_drive_onboarding=true', $onboarding_url );
		} finally {
			// Clean up.
			delete_option( 'woocommerce_coming_soon' );
			delete_option( 'woocommerce_onboarding_profile' );
		}
	}

	/**
	 * Test get_onboarding_url for new store in coming soon mode with other profile.
	 */
	public function test_get_onboarding_url_coming_soon_mode_other_profile() {
		// Arrange - Gateway without connected account.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'           => true,
				'account_connected' => false,
			)
		);

		// Arrange - Store in coming soon mode.
		update_option( 'woocommerce_coming_soon', 'yes' );

		// Arrange - Onboarding profile: not already selling.
		update_option(
			'woocommerce_onboarding_profile',
			array(
				'business_choice' => 'im_just_starting',
			)
		);

		try {
			// Act.
			$onboarding_url = $this->sut->get_onboarding_url( $fake_gateway );

			// Assert - Should do TEST-DRIVE onboarding (includes test_drive params).
			$this->assertStringContainsString( 'https://example.com/wp-admin/woopayments/connect-url', $onboarding_url );
			$this->assertStringContainsString( 'test_drive=true', $onboarding_url );
			$this->assertStringContainsString( 'auto_start_test_drive_onboarding=true', $onboarding_url );
		} finally {
			// Clean up.
			delete_option( 'woocommerce_coming_soon' );
			delete_option( 'woocommerce_onboarding_profile' );
		}
	}

	/**
	 * Setup WPCOM connection manager mock.
	 *
	 * Creates a mock of the WPCOM connection manager and configures it to
	 * simulate a connected Jetpack site with a connected owner.
	 */
	private function setup_wpcom_connection_mock(): void {
		$this->mock_wpcom_connection_manager = $this->getMockBuilder( WPCOM_Connection_Manager::class )
													->onlyMethods(
														array(
															'is_connected',
															'has_connected_owner',
															'is_connection_owner',
														)
													)
													->getMock();
	}

	/**
	 * Configure the WPCOM connection manager mock.
	 *
	 * @param bool $is_connected         Whether the site is connected to WPCOM.
	 * @param bool $has_connected_owner  Whether the connected owner exists.
	 * @param bool $is_connection_owner  Whether the current user is the connection owner.
	 */
	private function mock_wpcom_connection( bool $is_connected = true, bool $has_connected_owner = true, bool $is_connection_owner = true ): void {
		$this->mock_wpcom_connection_manager
			->method( 'is_connected' )
			->willReturn( $is_connected );
		$this->mock_wpcom_connection_manager
			->method( 'has_connected_owner' )
			->willReturn( $has_connected_owner );
		$this->mock_wpcom_connection_manager
			->method( 'is_connection_owner' )
			->willReturn( $is_connection_owner );
	}

	/**
	 * Setup WooPayments DI container mock.
	 *
	 * Creates a mock WooPayments DI container with standard methods for testing.
	 */
	private function setup_woopayments_container_mock(): void {
		$this->mock_woopayments_container = $this->getMockBuilder( \stdClass::class )
												->addMethods( array( 'get' ) )
												->getMock();
	}

	/**
	 * Setup WooPayments WooPaymentsRestController mock.
	 */
	private function setup_woopayments_reset_controller_mock(): void {
		// Mock the WooPaymentsRestController to provide REST URL paths.
		$this->mock_rest_controller = $this->createMock( WooPaymentsRestController::class );
		$this->mock_rest_controller
			->method( 'get_rest_url_path' )
			->willReturnCallback(
				function ( $relative_path = '' ) {
					$path = '/some/rest/for/woopayments';
					if ( ! empty( $relative_path ) ) {
						$path .= '/' . ltrim( $relative_path, '/' );
					}
					return $path;
				}
			);
	}

	/**
	 * Setup WooPayments account service mock.
	 *
	 * Creates a mock account service with standard methods for testing.
	 */
	private function setup_woopayments_account_service_mock(): void {
		$this->mock_woopayments_account_service = $this->getMockBuilder( \stdClass::class )
														->addMethods( array( 'is_stripe_account_valid', 'get_account_status_data' ) )
														->getMock();
	}

	/**
	 * Setup WooPayments mode mock.
	 *
	 * Creates a mock WooPayments mode with standard methods for testing.
	 */
	private function setup_woopayments_mode_mock(): void {
		$this->mock_woopayments_mode = $this->getMockBuilder( \stdClass::class )
											->addMethods( array( 'is_test', 'is_test_mode_onboarding', 'is_dev' ) )
											->getMock();
	}

	/**
	 * Setup legacy proxy mocks.
	 *
	 * Configures the mockable legacy proxy with class, static, and function mocks
	 * needed for testing the payments functionality.
	 */
	private function setup_legacy_proxy_mocks(): void {
		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();

		$this->mockable_proxy = $container->get( LegacyProxy::class );
		$this->mockable_proxy->register_class_mocks(
			array(
				WPCOM_Connection_Manager::class  => $this->mock_wpcom_connection_manager,
				WooPaymentsRestController::class => $this->mock_rest_controller,
			)
		);

		// We have no way of knowing if the container has already resolved the mocked classes,
		// so we need to reset all resolved instances.
		$container->reset_all_resolved();

		$this->mockable_proxy->register_static_mocks(
			array(
				'WC_Payments_Utils'   => array(
					'supported_countries' => function () {
						return $this->get_woopayments_supported_countries();
					},
				),
				'WC_Payments'         => array(
					'get_account_service' => function () {
						return $this->mock_woopayments_account_service;
					},
					'mode'                => function () {
						return $this->mock_woopayments_mode;
					},
				),
				'WC_Payments_Account' => array(
					'get_connect_url' => function () {
						return 'https://example.com/wp-admin/woopayments/connect-url?existing=param';
					},
				),
				PluginsHelper::class  => array(
					'get_plugin_data' => function ( $plugin_file ) {
						if ( 'woocommerce-payments/woocommerce-payments.php' === $plugin_file ) {
							return array(
								'Version' => '4.0.0',
							);
						}

						return false;
					},
				),
			)
		);

		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists'        => function ( $class_name ) {
					if ( in_array( $class_name, array( 'WC_Payments', 'WC_Payments_Account', 'WC_Payments_Utils' ), true ) ) {
						return true;
					}

					// For other classes, delegate to PHP's native class_exists()
					// so tests mirror runtime reality.
					return \class_exists( $class_name );
				},
				'method_exists'       => function ( $object_or_class, $method_name ) {
					if ( is_object( $object_or_class ) && $object_or_class === $this->mock_woopayments_mode ) {
						if ( in_array( $method_name, array( 'is_test', 'is_test_mode_onboarding', 'is_dev' ), true ) ) {
							return true;
						}
					}

					if ( is_object( $object_or_class ) && $object_or_class === $this->mock_woopayments_account_service ) {
						if ( in_array( $method_name, array( 'get_account_status_data' ), true ) ) {
							return true;
						}
					}
					if ( is_string( $object_or_class ) ) {
						if ( 'WC_Payments' === $object_or_class ) {
							if ( in_array( $method_name, array( 'mode' ), true ) ) {
								return true;
							}
						}

						if ( 'WC_Payments_Account' === $object_or_class ) {
							if ( in_array( $method_name, array( 'get_connect_url' ), true ) ) {
								return true;
							}
						}

						if ( 'WC_Payments_Utils' === $object_or_class ) {
							if ( in_array( $method_name, array( 'supported_countries' ), true ) ) {
								return true;
							}
						}
					}
					// For other methods, delegate to PHP's native method_exists()
					// so tests mirror runtime reality.
					return \method_exists( $object_or_class, $method_name );
				},
				'is_callable'         => function () {
					if ( func_num_args() > 0 ) {
						$callable = func_get_arg( 0 );
						if ( is_array( $callable ) && count( $callable ) === 2 ) {
							$object_or_class = $callable[0];
							$method_name     = $callable[1];
							if ( $object_or_class === $this->mock_woopayments_mode &&
								in_array( $method_name, array( 'is_test', 'is_test_mode_onboarding', 'is_dev' ), true ) ) {
								return true;
							}

							if ( $object_or_class === $this->mock_woopayments_account_service &&
								in_array( $method_name, array( 'get_account_status_data' ), true ) ) {
								return true;
							}
						} elseif ( is_string( $callable ) ) {
							if ( in_array(
								$callable,
								array( 'WC_Payments::mode', 'WC_Payments_Account::get_connect_url', 'WC_Payments_Utils::supported_countries' ),
								true
							)
							) {

								return true;
							}
						}

						// For other callables, delegate to PHP's native is_callable()
						// so tests mirror runtime reality.
						return \is_callable( $callable );
					}

					// No arguments provided.
					return false;
				},
				'wcpay_get_container' => function () {
					return $this->mock_woopayments_container;
				},
			),
		);
	}

	/**
	 * Get the list of supported countries for WooPayments.
	 *
	 * @return array Array of country codes and names.
	 */
	private function get_woopayments_supported_countries(): array {
		// This is just a subset of countries that WooPayments supports.
		// But it should cover our testing needs.
		return array(
			'us' => 'United States',
			'gb' => 'United Kingdom',
			'de' => 'Germany',
		);
	}
}
