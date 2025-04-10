<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\PaymentGateway;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use WC_Unit_Test_Case;

/**
 * Payment gateway provider service test.
 *
 * @class PaymentGateway
 */
class PaymentGatewayTest extends WC_Unit_Test_Case {

	/**
	 * @var PaymentGateway
	 */
	protected $sut;

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

		$this->sut = new PaymentGateway();
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
						'id'      => 'basic',
						// No order, should be last.
						'enabled' => true,
						'title'   => 'Title',
					),
					// Basic PM with priority instead of order.
					array(
						'id'       => 'basic2',
						'priority' => 30,
						'enabled'  => false,
						'title'    => 'Title',
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
					),
					array(
						'id'          => 'woopay',
						'order'       => 10,
						'enabled'     => false,
						'title'       => 'WooPay',
						'description' => 'WooPay express checkout',
						// Not a good URL.
						'icon'        => 'not_good_url/icon.svg',
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
							'href' => 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bogus_settings',
						),
					),
				),
				'plugin'      => array(
					'_type'  => PaymentProviders::EXTENSION_TYPE_WPORG,
					'slug'   => 'woocommerce-payments',
					'file'   => 'woocommerce-payments/woocommerce-payments',
					'status' => PaymentProviders::EXTENSION_ACTIVE,
				),
				'onboarding'  => array(
					'state'                       => array(
						'started'   => true,
						'completed' => true,
						'test_mode' => true,
					),
					'_links'                      => array(
						'onboard' => array(
							'href' => 'https://example.com/connection-url',
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
						),
						array(
							'id'          => 'card',
							'_order'      => 1,
							'enabled'     => true,
							'required'    => true,
							'title'       => 'Credit/debit card (required)',
							'description' => '<strong>Accepts</strong> <b>all major</b><em>credit</em> and <a href="#" target="_blank">debit cards</a>.',
							'icon'        => 'https://example.com/card-icon.png',
						),
						array(
							'id'          => 'basic2',
							'_order'      => 2,
							'enabled'     => false,
							'required'    => false,
							'title'       => 'Title',
							'description' => '',
							'icon'        => '',
						),
						array(
							'id'          => 'basic',
							'_order'      => 3,
							'enabled'     => true,
							'required'    => false,
							'title'       => 'Title',
							'description' => '',
							'icon'        => '',
						),
					),
				),
			),
			$gateway_details
		);
	}

	/**
	 * Test get_title.
	 */
	public function test_get_title() {
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_title' => 'WooPayments' ) );
		$this->assertEquals( 'WooPayments', $this->sut->get_title( $fake_gateway ) );
	}

	/**
	 * Test get_description.
	 */
	public function test_get_description() {
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_description' => 'Accept payments with WooPayments.' ) );
		$this->assertEquals( 'Accept payments with WooPayments.', $this->sut->get_description( $fake_gateway ) );
	}

	/**
	 * Test get_icon.
	 */
	public function test_get_icon() {
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'icon' => 'https://example.com/icon.png' ) );
		$this->assertEquals( 'https://example.com/icon.png', $this->sut->get_icon( $fake_gateway ) );

		// Test invalid URL falls back to default icon.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'icon' => 'not_good_url/icon.svg' ) );
		$this->assertStringContainsString( 'wp-content/plugins/woocommerce/assets/images/icons/default-payments.svg', $this->sut->get_icon( $fake_gateway ) );

		// Test empty icon falls back to default icon.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'icon' => '' ) );
		$this->assertStringContainsString( 'wp-content/plugins/woocommerce/assets/images/icons/default-payments.svg', $this->sut->get_icon( $fake_gateway ) );

		// Test missing icon falls back to default icon.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array() );
		$this->assertStringContainsString( 'wp-content/plugins/woocommerce/assets/images/icons/default-payments.svg', $this->sut->get_icon( $fake_gateway ) );

		// Test icon with img tag falls back to default icon.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'icon' => '<img src="https://example.com/icon.png" />' ) );
		$this->assertStringContainsString( 'wp-content/plugins/woocommerce/assets/images/icons/default-payments.svg', $this->sut->get_icon( $fake_gateway ) );

		// Test icon with list of images falls back to default icon.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'icon' => '<img src="https://example.com/icon.png" /><img src="https://example.com/icon2.png" />' ) );
		$this->assertStringContainsString( 'wp-content/plugins/woocommerce/assets/images/icons/default-payments.svg', $this->sut->get_icon( $fake_gateway ) );
	}

	/**
	 * Test get_supports.
	 */
	public function test_get_supports() {
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'supports' => array(
					'key'   => 'products',
					2       => 'something',
					3       => 'bogus', // Only one `bogus` entry should be returned.
					'bogus',
					// Sanitization.
					'list'  => array( 'products', 'something', 'bogus' ), // This should be ignored.
					'item'  => 1,                                         // This should be ignored.
					'item2' => true,                                      // This should be ignored.
					':"|<>bogus_-1%@#%^&*',
				),
			)
		);
		$this->assertEquals(
			array(
				'products',
				'something',
				'bogus',
				'bogus_-1',
			),
			$this->sut->get_supports_list( $fake_gateway )
		);
	}

	/**
	 * Test is_enabled.
	 */
	public function test_is_enabled() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'enabled' => true ) );
		$this->assertTrue( $this->sut->is_enabled( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'enabled' => false ) );
		$this->assertFalse( $this->sut->is_enabled( $fake_gateway ) );
	}

	/**
	 * Test needs_setup.
	 */
	public function test_needs_setup() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'needs_setup' => true ) );
		$this->assertTrue( $this->sut->needs_setup( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'needs_setup' => false ) );
		$this->assertFalse( $this->sut->needs_setup( $fake_gateway ) );
	}

	/**
	 * Test is_in_test_mode.
	 */
	public function test_is_in_test_mode() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'test_mode' => true ) );
		$this->assertTrue( $this->sut->is_in_test_mode( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'test_mode' => false ) );
		$this->assertFalse( $this->sut->is_in_test_mode( $fake_gateway ) );
	}

	/**
	 * Test is_in_dev_mode.
	 */
	public function test_is_in_dev_mode() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'dev_mode' => true ) );
		$this->assertTrue( $this->sut->is_in_dev_mode( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'dev_mode' => false ) );
		$this->assertFalse( $this->sut->is_in_dev_mode( $fake_gateway ) );
	}

	/**
	 * Test get_settings_url.
	 */
	public function test_get_settings_url() {
		$fake_gateway = new FakePaymentGateway( 'gateway1' );
		$this->assertEquals( 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bogus_settings', $this->sut->get_settings_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => 'https://example.com/settings-url' ) );
		$this->assertEquals( 'https://example.com/settings-url', $this->sut->get_settings_url( $fake_gateway ) );
	}

	/**
	 * Test get_onboarding_url.
	 */
	public function test_get_onboarding_url() {
		$fake_gateway = new FakePaymentGateway( 'gateway1' );
		$this->assertEquals( 'https://example.com/connection-url', $this->sut->get_onboarding_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'connection_url' => 'https://example.com/onboarding-url' ) );
		$this->assertEquals( 'https://example.com/onboarding-url', $this->sut->get_onboarding_url( $fake_gateway ) );
	}

	/**
	 * Test get_plugin_slug.
	 */
	public function test_get_plugin_slug() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'plugin_slug' => 'woocommerce-payments' ) );
		$this->assertEquals( 'woocommerce-payments', $this->sut->get_plugin_slug( $fake_gateway ) );
	}

	/**
	 * Test get_plugin_file.
	 */
	public function test_get_plugin_file() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'plugin_file' => 'woocommerce-payments/woocommerce-payments.php' ) );
		$this->assertEquals( 'woocommerce-payments/woocommerce-payments', $this->sut->get_plugin_file( $fake_gateway ) );
	}

	/**
	 * Test get_recommended_payment_methods.
	 */
	public function test_get_recommended_payment_methods() {
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'recommended_payment_methods' => array(
					array(
						'id'          => 'woopay',
						'_order'      => 0,
						'enabled'     => false,
						'title'       => 'WooPay',
						'description' => 'WooPay express checkout',
						'icon'        => 'https://example.com/icon.png',
					),
					array(
						'id'          => 'card',
						'_order'      => 1,
						'enabled'     => true,
						'required'    => true,
						'title'       => 'Credit/debit card (required)',
						'description' => 'Accepts all major credit and debit cards.',
						'icon'        => 'https://example.com/card-icon.png',
					),
				),
			)
		);
		$this->assertEquals(
			array(
				array(
					'id'          => 'woopay',
					'_order'      => 0,
					'enabled'     => false,
					'required'    => false,
					'title'       => 'WooPay',
					'description' => 'WooPay express checkout',
					'icon'        => 'https://example.com/icon.png',
				),
				array(
					'id'          => 'card',
					'_order'      => 1,
					'enabled'     => true,
					'required'    => true,
					'title'       => 'Credit/debit card (required)',
					'description' => 'Accepts all major credit and debit cards.',
					'icon'        => 'https://example.com/card-icon.png',
				),
			),
			$this->sut->get_recommended_payment_methods( $fake_gateway )
		);
	}
}
