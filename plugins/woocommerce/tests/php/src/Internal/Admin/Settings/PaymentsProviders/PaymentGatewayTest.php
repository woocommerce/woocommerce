<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use stdClass;
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
					'type'                        => PaymentGateway::ONBOARDING_TYPE_EXTERNAL,
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
	}

	/**
	 * Test enhance_extension_suggestion.
	 */
	public function test_enhance_extension_suggestion() {
		// Arrange.
		$extension_suggestion = array(
			'id'          => 'woopayments',
			'title'       => 'WooPayments',
			'description' => 'Accept payments with WooPayments.',
			'icon'        => 'https://example.com/icon.png',
			'image'       => 'https://example.com/image.png',
			'category'    => PaymentsProviders::CATEGORY_PSP,
			'links'       => array(
				'about' => array(
					'_type' => 'about',
					'url'   => 'https://example.com/about',
				),
			),
			'plugin'      => array(
				'_type'  => PaymentsProviders::EXTENSION_TYPE_WPORG,
				'slug'   => 'woocommerce-payments',
				'file'   => 'woocommerce-payments/woocommerce-payments',
				'status' => PaymentsProviders::EXTENSION_NOT_INSTALLED,
			),
			'tags'        => array(
				'made_in_woo',
			),
			'_priority'   => 1,
			'_type'       => PaymentsExtensionSuggestions::TYPE_PSP,
		);

		// Act.
		$enhanced_suggestion = $this->sut->enhance_extension_suggestion( $extension_suggestion );

		// Assert.
		// The onboarding entry should be added.
		$this->assertArrayHasKey( 'onboarding', $enhanced_suggestion );
		$this->assertEquals(
			array(
				'type' => PaymentGateway::ONBOARDING_TYPE_EXTERNAL,
			),
			$enhanced_suggestion['onboarding']
		);
	}

	/**
	 * Test get_title.
	 */
	public function test_get_title() {
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_title' => 'WooPayments' ) );
		$this->assertEquals( 'WooPayments', $this->sut->get_title( $fake_gateway ) );

		// Test title with HTML tags.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_title' => '<h1><a href="#">WooPayments</a></h1> <a href="#">Some link</a> ' ) );
		$this->assertEquals( 'WooPayments Some link', $this->sut->get_title( $fake_gateway ) );

		// Test title with encoded HTML entities.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_title' => htmlentities( '<h1><a href="#">WooPayments</a></h1> <a href="#">Some link</a> ' ) ) );
		$this->assertEquals( 'WooPayments Some link', $this->sut->get_title( $fake_gateway ) );

		// Test title with wrong type.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_title' => true,
				'title'        => 'Public title',
			)
		);
		$this->assertEquals( 'Public title', $this->sut->get_title( $fake_gateway ) );

		// Test title empty falls back on public-facing title.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_title' => '',
				'title'        => 'Public title',
			)
		);
		$this->assertEquals( 'Public title', $this->sut->get_title( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_title' => false,
				'title'        => 'Public title',
			)
		);
		$this->assertEquals( 'Public title', $this->sut->get_title( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_title' => array( 'Something' ),
				'title'        => 'Public title',
			)
		);
		$this->assertEquals( 'Public title', $this->sut->get_title( $fake_gateway ) );

		// Test title empty falls back on Unknown.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_title' => '',
				'title'        => '',
			)
		);
		$this->assertEquals( 'Unknown', $this->sut->get_title( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_title' => false,
				'title'        => '',
			)
		);
		$this->assertEquals( 'Unknown', $this->sut->get_title( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_title' => array( 'Something' ),
				'title'        => '',
			)
		);
		$this->assertEquals( 'Unknown', $this->sut->get_title( $fake_gateway ) );
	}

	/**
	 * Test get_description.
	 */
	public function test_get_description() {
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_description' => 'Accept payments with WooPayments.' ) );
		$this->assertEquals( 'Accept payments with WooPayments.', $this->sut->get_description( $fake_gateway ) );

		// Test description with HTML tags.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_description' => '<a href="#">Accept</a> <b>payments</b> <strong><span>with</span> WooPayments. </strong><h1></h1> ' ) );
		$this->assertEquals( 'Accept payments with WooPayments.', $this->sut->get_description( $fake_gateway ) );

		// Test description with encoded HTML entities.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'method_description' => htmlentities( '<a href="#">Accept</a> <b>payments</b> <strong><span>with</span> WooPayments. </strong><h1></h1> ' ) ) );
		$this->assertEquals( 'Accept payments with WooPayments.', $this->sut->get_description( $fake_gateway ) );

		// Test description with wrong type.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_description' => true,
				'description'        => 'Public description',
			)
		);
		$this->assertEquals( 'Public description', $this->sut->get_description( $fake_gateway ) );

		// Test description empty falls back on public-facing description.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_description' => '',
				'description'        => 'Public description',
			)
		);
		$this->assertEquals( 'Public description', $this->sut->get_description( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_description' => false,
				'description'        => 'Public description',
			)
		);
		$this->assertEquals( 'Public description', $this->sut->get_description( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_description' => array( 'Something' ),
				'description'        => 'Public description',
			)
		);
		$this->assertEquals( 'Public description', $this->sut->get_description( $fake_gateway ) );

		// Test description empty falls back on empty string.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_description' => '',
				'description'        => '',
			)
		);
		$this->assertEquals( '', $this->sut->get_description( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_description' => false,
				'description'        => '',
			)
		);
		$this->assertEquals( '', $this->sut->get_description( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'method_description' => array( 'Something' ),
				'description'        => '',
			)
		);
		$this->assertEquals( '', $this->sut->get_description( $fake_gateway ) );
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

		// Test wrong type icon falls back to default icon.
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'icon' => true ) );
		$this->assertStringContainsString( 'wp-content/plugins/woocommerce/assets/images/icons/default-payments.svg', $this->sut->get_icon( $fake_gateway ) );
		$fake_gateway = new FakePaymentGateway( 'woocommerce_payments', array( 'icon' => array( 'some-icon' ) ) );
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

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'supports' => 'products' ) );
		$this->assertEquals( array(), $this->sut->get_supports_list( $fake_gateway ) );

		// Test undefined.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array() );
		$this->assertEquals( array( 'products' ), $this->sut->get_supports_list( $fake_gateway ) );
	}

	/**
	 * Test is_enabled.
	 */
	public function test_is_enabled() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'enabled' => true ) );
		$this->assertTrue( $this->sut->is_enabled( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'enabled' => false ) );
		$this->assertFalse( $this->sut->is_enabled( $fake_gateway ) );

		// Test with string value.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'enabled' => 'yes' ) );
		$this->assertTrue( $this->sut->is_enabled( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'enabled' => 'no' ) );
		$this->assertFalse( $this->sut->is_enabled( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'enabled' => array() ) );
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

		// Test with string value.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'needs_setup' => 'yes' ) );
		$this->assertTrue( $this->sut->needs_setup( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'needs_setup' => 'no' ) );
		$this->assertFalse( $this->sut->needs_setup( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'needs_setup' => array() ) );
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

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'test_mode' => array() ) );
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

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'dev_mode' => array() ) );
		$this->assertFalse( $this->sut->is_in_dev_mode( $fake_gateway ) );
	}

	/**
	 * Test is_account_connected.
	 */
	public function test_is_account_connected() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'account_connected' => true ) );
		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'account_connected' => false ) );
		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'account_connected' => array() ) );
		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );

		// Test undefined.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array() );
		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * Test is_onboarding_started.
	 */
	public function test_is_onboarding_started() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'onboarding_started' => true ) );
		$this->assertTrue( $this->sut->is_onboarding_started( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'onboarding_started' => false ) );
		$this->assertFalse( $this->sut->is_onboarding_started( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'onboarding_started' => array() ) );
		$this->assertTrue( $this->sut->is_onboarding_started( $fake_gateway ) );

		// Test undefined.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array() );
		$this->assertTrue( $this->sut->is_onboarding_started( $fake_gateway ) );
	}

	/**
	 * Test is_onboarding_completed.
	 */
	public function test_is_onboarding_completed() {
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_started'   => true,
				'onboarding_completed' => true,
			)
		);
		$this->assertTrue( $this->sut->is_onboarding_completed( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_started'   => true,
				'onboarding_completed' => false,
			)
		);
		$this->assertFalse( $this->sut->is_onboarding_completed( $fake_gateway ) );

		// Test without onboarding started.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_started'   => false,
				'onboarding_completed' => true,
			)
		);
		$this->assertFalse( $this->sut->is_onboarding_completed( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_started'   => true,
				'onboarding_completed' => array(),
			)
		);
		$this->assertTrue( $this->sut->is_onboarding_completed( $fake_gateway ) );

		// Test undefined.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array() );
		$this->assertTrue( $this->sut->is_onboarding_completed( $fake_gateway ) );
	}

	/**
	 * Test is_in_test_mode_onboarding.
	 */
	public function test_is_in_test_mode_onboarding() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'test_mode_onboarding' => true ) );
		$this->assertTrue( $this->sut->is_in_test_mode_onboarding( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'test_mode_onboarding' => false ) );
		$this->assertFalse( $this->sut->is_in_test_mode_onboarding( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'test_mode_onboarding' => array() ) );
		$this->assertFalse( $this->sut->is_in_test_mode_onboarding( $fake_gateway ) );
	}

	/**
	 * Test get_settings_url.
	 */
	public function test_get_settings_url() {
		$test_site_wp_admin_url = get_site_url( null, 'wp-admin/', 'admin' );

		// Test valid, full URLs.
		$fake_gateway = new FakePaymentGateway( 'gateway1' );
		$this->assertEquals( 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bogus_settings&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => 'https://example.com/settings-url' ) );
		$this->assertEquals( 'https://example.com/settings-url?from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		// Test invalid URLs.
		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => 'not_good_url/settings-url' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=gateway2&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => '//not_good_url/settings-url?param=value' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=gateway2&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		// Test valid relative WP admin URLs.
		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bogus_settings' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=bogus_settings&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => 'wp-admin/admin.php?page=wc-settings&tab=checkout&section=bogus_settings' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=bogus_settings&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => '/admin.php?page=wc-settings&tab=checkout&section=bogus_settings' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=bogus_settings&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => 'admin.php?page=wc-settings&tab=checkout&section=bogus_settings' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=bogus_settings&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		// Test invalid relative URLs.
		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => 'not_good_url/admin.php?page=wc-settings&tab=checkout&section=bogus_settings' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=gateway2&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => 'page=wc-settings&tab=checkout&section=bogus_settings' ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=gateway2&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );

		// Test with wrong type uses the default settings URL.
		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'settings_url' => false ) );
		$this->assertEquals( $test_site_wp_admin_url . 'admin.php?page=wc-settings&tab=checkout&section=gateway2&from=' . Payments::FROM_PAYMENTS_SETTINGS, $this->sut->get_settings_url( $fake_gateway ) );
	}

	/**
	 * Test get_onboarding_url.
	 */
	public function test_get_onboarding_url() {
		// Test with no onboarding URL.
		$fake_gateway = new FakePaymentGateway( 'gateway1' );
		$this->assertEquals( 'https://example.com/connection-url', $this->sut->get_onboarding_url( $fake_gateway ) );

		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'connection_url' => 'https://example.com/onboarding-url' ) );
		$this->assertEquals( 'https://example.com/onboarding-url', $this->sut->get_onboarding_url( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway2', array( 'connection_url' => false ) );
		$this->assertEquals( '', $this->sut->get_onboarding_url( $fake_gateway ) );
	}

	/**
	 * Test get_plugin_details.
	 */
	public function test_get_plugin_details() {
		// Test in regular plugin.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				// This should be determined from the class filename.
				'plugin_slug'    => null,
				'plugin_file'    => 'woocommerce-payments/woocommerce-payments.php',
				'class_filename' => trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce-payments/some-dir/gateways/class-fake-gateway.php',
			)
		);
		$this->assertEquals(
			array(
				'_type'  => PaymentsProviders::EXTENSION_TYPE_WPORG,
				'slug'   => 'woocommerce-payments',
				'file'   => 'woocommerce-payments/woocommerce-payments',
				'status' => PaymentsProviders::EXTENSION_ACTIVE,
			),
			$this->sut->get_plugin_details( $fake_gateway )
		);

		// Test in must-use plugin.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				// This should be determined from the class filename.
				'plugin_slug'    => null,
				'plugin_file'    => 'woocommerce-payments/woocommerce-payments.php',
				'class_filename' => trailingslashit( WPMU_PLUGIN_DIR ) . 'woocommerce-payments/some-dir/gateways/class-fake-gateway.php',
			)
		);
		$this->assertEquals(
			array(
				'_type'  => PaymentsProviders::EXTENSION_TYPE_MU_PLUGIN,
				'slug'   => 'woocommerce-payments',
				// No plugin file for must-use plugins.
				'file'   => '',
				'status' => PaymentsProviders::EXTENSION_ACTIVE,
			),
			$this->sut->get_plugin_details( $fake_gateway )
		);

		// Test in must-use root plugin.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				// This should be determined from the class filename.
				'plugin_slug'    => null,
				'plugin_file'    => null,
				'class_filename' => trailingslashit( WPMU_PLUGIN_DIR ) . 'class-fake-gateway.php',
			)
		);
		$this->assertEquals(
			array(
				'_type'  => PaymentsProviders::EXTENSION_TYPE_MU_PLUGIN,
				// The file name is the slug.
				'slug'   => 'class-fake-gateway',
				// No plugin file for must-use plugins.
				'file'   => '',
				'status' => PaymentsProviders::EXTENSION_ACTIVE,
			),
			$this->sut->get_plugin_details( $fake_gateway )
		);

		// Test in theme.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				// This should be determined from the class filename.
				'plugin_slug'    => null,
				'plugin_file'    => null,
				'class_filename' => trailingslashit( get_theme_root() ) . 'some-theme/some-dir/class-fake-gateway.php',
			)
		);
		$this->assertEquals(
			array(
				'_type'  => PaymentsProviders::EXTENSION_TYPE_THEME,
				// The theme slug is the slug.
				'slug'   => 'some-theme',
				// No plugin file for themes.
				'file'   => '',
				'status' => PaymentsProviders::EXTENSION_ACTIVE,
			),
			$this->sut->get_plugin_details( $fake_gateway )
		);

		// Test in other location.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				// This should be determined from the class filename.
				'plugin_slug'    => null,
				'plugin_file'    => null,
				'class_filename' => '/var/some-dir/class-fake-gateway.php',
			)
		);
		$this->assertEquals(
			array(
				'_type'  => PaymentsProviders::EXTENSION_TYPE_UNKNOWN,
				// No slug for unknown location.
				'slug'   => '',
				// No plugin file for unknown location.
				'file'   => '',
				'status' => PaymentsProviders::EXTENSION_ACTIVE,
			),
			$this->sut->get_plugin_details( $fake_gateway )
		);
	}

	/**
	 * Test get_plugin_slug.
	 */
	public function test_get_plugin_slug() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'plugin_slug' => 'woocommerce-payments' ) );
		$this->assertEquals( 'woocommerce-payments', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'plugin_slug' => false ) );
		$this->assertEquals( '', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in the plugins directory.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce-payments/some-dir/gateways/class-fake-gateway.php',
			)
		);
		$this->assertEquals( 'woocommerce-payments', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in the plugins directory, only one level deep.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce-payments/class-fake-gateway.php',
			)
		);
		$this->assertEquals( 'woocommerce-payments', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in the root of the plugins directory.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => trailingslashit( WP_PLUGIN_DIR ) . 'fake-gateway.php',
			)
		);
		$this->assertEquals( 'fake-gateway', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in the mu-plugins directory.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => trailingslashit( WPMU_PLUGIN_DIR ) . 'woocommerce-payments/some-dir/gateways/class-fake-gateway.php',
			)
		);
		$this->assertEquals( 'woocommerce-payments', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in the mu-plugins directory, only one level deep.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => trailingslashit( WPMU_PLUGIN_DIR ) . 'woocommerce-payments/class-fake-gateway.php',
			)
		);
		$this->assertEquals( 'woocommerce-payments', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in the root of the mu-plugins directory.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => trailingslashit( WPMU_PLUGIN_DIR ) . 'fake-gateway.php',
			)
		);
		$this->assertEquals( 'fake-gateway', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in the themes directory.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => trailingslashit( get_theme_root() ) . 'some-theme/some-dir/gateways/class-fake-gateway.php',
			)
		);
		$this->assertEquals( 'some-theme', $this->sut->get_plugin_slug( $fake_gateway ) );

		// Test with class filename in a random directory.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => null,
				'class_filename' => '/var/www/something/woocommerce-payments/some-dir/gateways/class-fake-gateway.php',
			)
		);
		$this->assertEquals( '', $this->sut->get_plugin_slug( $fake_gateway ) );
	}

	/**
	 * Test get_plugin_file.
	 */
	public function test_get_plugin_file() {
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'plugin_file' => 'woocommerce-payments/woocommerce-payments.php' ) );
		$this->assertEquals( 'woocommerce-payments/woocommerce-payments', $this->sut->get_plugin_file( $fake_gateway ) );

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array( 'plugin_file' => false ) );
		$this->assertEquals( '', $this->sut->get_plugin_file( $fake_gateway ) );
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
						'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_SECONDARY, // This should be kept.
					),
					array(
						'id'          => 'card',
						'_order'      => 1,
						'enabled'     => true,
						'required'    => true,
						'title'       => 'Credit/debit card (required)',
						'description' => 'Accepts all major credit and debit cards.',
						'icon'        => 'https://example.com/card-icon.png',
						// No category means it should be primary.
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
					'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_SECONDARY,
				),
				array(
					'id'          => 'card',
					'_order'      => 1,
					'enabled'     => true,
					'required'    => true,
					'title'       => 'Credit/debit card (required)',
					'description' => 'Accepts all major credit and debit cards.',
					'icon'        => 'https://example.com/card-icon.png',
					'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY,
				),
			),
			$this->sut->get_recommended_payment_methods( $fake_gateway )
		);

		// Test validation.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'recommended_payment_methods' => array(
					false,
					'something',
					123,
					new stdClass(),
					array(
						// No id.
						'_order'  => 0,
						'enabled' => false,
						'title'   => 'WooPay',
					),
					array(
						'id'          => 'woopay',
						// No title.
						'_order'      => 0,
						'enabled'     => false,
						'description' => 'WooPay express checkout',
					),
					array(
						// Should validate.
						'id'          => 'good_id',
						'_order'      => 1,
						'enabled'     => true,
						'required'    => true,
						'title'       => 'WooPay',
						'description' => '<a href="#"><h1>WooPay</h1></a> <b>express</b> <em>checkout</em>',
					),
				),
			)
		);
		$this->assertEquals(
			array(
				array(
					'id'          => 'good_id',
					// Changed to 0.
					'_order'      => 0,
					'enabled'     => true,
					'required'    => true,
					'title'       => 'WooPay',
					// The h1 tag should be stripped.
					'description' => '<a href="#">WooPay</a> <b>express</b> <em>checkout</em>',
					// Default category.
					'category'    => PaymentGateway::PAYMENT_METHOD_CATEGORY_PRIMARY,
					// No icon.
					'icon'        => '',
				),
			),
			$this->sut->get_recommended_payment_methods( $fake_gateway )
		);

		// Test with wrong type.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'recommended_payment_methods' => 'woopay',
			)
		);
		$this->assertEquals(
			array(),
			$this->sut->get_recommended_payment_methods( $fake_gateway )
		);
	}
}
