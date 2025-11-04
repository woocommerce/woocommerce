<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsRestController;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use WC_Unit_Test_Case;

/**
 * WooPayments payment gateway provider service test.
 *
 * @class WooPayments
 */
class WooPaymentsTest extends WC_Unit_Test_Case {

	/**
	 * @var WooPaymentsRestController
	 */
	protected $mock_rest_controller;

	/**
	 * @var WooPayments
	 */
	protected $sut;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new WooPayments();
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

		// Arrange the version constant to meet the minimum requirements for the native in-context onboarding.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		// Mock the WooPaymentsRestController to provide REST URL paths.
		$mock_rest_controller = $this->createMock( WooPaymentsRestController::class );
		$mock_rest_controller
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

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$container->replace( WooPaymentsRestController::class, $mock_rest_controller );

		try {
			// Act.
			$gateway_details = $this->sut->get_details( $fake_gateway, 999 );

			// Assert that we have all the details.
			$this->assertEquals(
				array(
					'id'          => 'woocommerce_payments',
					'_order'      => 999,
					'title'       => 'WooPayments has a very long title that should be truncated after some length',
					'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim…',
					'icon'        => 'https://example.com/icon.png',
					'supports'    => array( 'products', 'something', 'bogus' ),
					'links'       => array(),
					'state'       => array(
						'enabled'           => true,
						'account_connected' => true,
						'needs_setup'       => true,
						'test_mode'         => true,
						'dev_mode'          => true,
					),
					'management'  => array(
						'_links' => array(
							'settings' => array(
								'href' => 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bogus_settings&from=' . Payments::FROM_PAYMENTS_SETTINGS,
							),
						),
					),
					'plugin'      => array(
						'_type'  => PaymentsProviders::EXTENSION_TYPE_WPORG,
						'slug'   => 'woocommerce-payments',
						'file'   => 'woocommerce-payments/woocommerce-payments',
						'status' => PaymentsProviders::EXTENSION_ACTIVE,
					),
					'onboarding'  => array(
						'type'                        => PaymentGateway::ONBOARDING_TYPE_NATIVE_IN_CONTEXT,
						'state'                       => array(
							'supported'                    => true,
							'started'                      => true,
							'completed'                    => true,
							'test_mode'                    => true,
							'test_drive_account'           => false,
							'wpcom_has_working_connection' => false,
							'wpcom_is_store_connected'     => false,
							'wpcom_has_connected_owner'    => false,
							'wpcom_is_connection_owner'    => false,
						),
						'messages'                    => array(
							'not_supported' => null,
						),
						'_links'                      => array(
							'onboard' => array(
								'href' => Utils::wc_payments_settings_url( '/woopayments/onboarding', array( 'from' => Payments::FROM_PAYMENTS_SETTINGS ) ),
							),
							'reset'   => array(
								'href' => rest_url( '/some/rest/for/woopayments/onboarding/reset' ),
							),
						),
						'recommended_payment_methods' => array(
							array(
								'id'          => 'woopay',
								'_order'      => 0,
								'enabled'     => false,
								'required'    => false,
								'title'       => 'WooPay',
								'description' => 'WooPay express checkout',
								'icon'        => '', // The icon with an invalid URL is ignored.
								'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY,
							),
							array(
								'id'          => 'card',
								'_order'      => 1,
								'enabled'     => true,
								'required'    => true,
								'title'       => 'Credit/debit card (required)',
								'description' => '<strong>Accepts</strong> <b>all major</b><em>credit</em> and <a href="#" target="_blank">debit cards</a>.',
								'icon'        => 'https://example.com/card-icon.png',
								'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY,
							),
							array(
								'id'          => 'basic2',
								'_order'      => 2,
								'enabled'     => false,
								'required'    => false,
								'title'       => 'Title',
								'description' => '',
								'icon'        => '',
								'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY,
							),
							array(
								'id'          => 'basic',
								'_order'      => 3,
								'enabled'     => true,
								'required'    => false,
								'title'       => 'Title',
								'description' => '',
								'icon'        => '',
								'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_SECONDARY,
							),
						),
					),
				),
				$gateway_details
			);
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

		// Arrange the version constant to meet the minimum requirements.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		// Mock the WooPaymentsRestController to provide REST URL paths.
		$mock_rest_controller = $this->createMock( WooPaymentsRestController::class );
		$mock_rest_controller
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
		$container->replace( WooPaymentsRestController::class, $mock_rest_controller );
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

		// Arrange the version constant.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		// Mock the WooPaymentsRestController to provide REST URL paths.
		$mock_rest_controller = $this->createMock( WooPaymentsRestController::class );
		$mock_rest_controller
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
		$container->replace( WooPaymentsRestController::class, $mock_rest_controller );
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

		// Load the mock WC_Payments_Utils if the real class doesn't exist.
		if ( ! class_exists( '\WC_Payments_Utils' ) ) {
			require_once __DIR__ . '/../Mocks/WCPaymentsUtils.php';
		}

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

		// Load the mock WC_Payments_Utils if the real class doesn't exist.
		if ( ! class_exists( '\WC_Payments_Utils' ) ) {
			require_once __DIR__ . '/../Mocks/WCPaymentsUtils.php';
		}

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
		if ( ! class_exists( '\WC_Payments_Utils' ) ) {
			require_once __DIR__ . '/../Mocks/WCPaymentsUtils.php';
		}
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
}
