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
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use WC_Unit_Test_Case;

/**
 * WooPayments payment gateway provider service test.
 *
 * @class WCCore
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

		// Replace the controller in the container so that it can be used during tests.
		$this->mock_rest_controller = $this->createMock( WooPaymentsRestController::class );
		$this->mock_rest_controller
			->method( 'get_rest_url_path' )
			->willReturn( '/some/rest/path' );
		wc_get_container()->replace( WooPaymentsRestController::class, $this->mock_rest_controller );
	}

	/**
	 * Test get_details.
	 */
	public function test_get_details() {
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
						'started'                      => true,
						'completed'                    => true,
						'test_mode'                    => true,
						'test_drive_account'           => false,
						'wpcom_has_working_connection' => false,
						'wpcom_is_store_connected'     => false,
						'wpcom_has_connected_owner'    => false,
						'wpcom_is_connection_owner'    => false,
					),
					'_links'                      => array(
						'onboard' => array(
							'href' => Utils::wc_payments_settings_url( '/woopayments/onboarding', array( 'from' => Payments::FROM_PAYMENTS_SETTINGS ) ),
						),
						'reset'   => array(
							'href' => rest_url( '/some/rest/path' ),
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

		// Clean up.
		Constants::clear_constants();
	}
}
