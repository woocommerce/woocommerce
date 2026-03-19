<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use WC_Unit_Test_Case;

/**
 * Payment gateway provider service test.
 *
 * @class PaymentGateway
 */
class PaymentGatewayTest extends WC_Unit_Test_Case {

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

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

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();

		$this->mockable_proxy = $container->get( LegacyProxy::class );

		$this->sut = new PaymentGateway( $this->mockable_proxy );
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
					'type'                        => PaymentGateway::ONBOARDING_TYPE_EXTERNAL,
					'state'                       => array(
						'supported' => true,
						'started'   => true,
						'completed' => true,
						'test_mode' => true,
					),
					'messages'                    => array(
						'not_supported' => null,
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
	 * Test needs_setup fallback logic when method returns false but account is not connected.
	 */
	public function test_needs_setup_fallback_when_method_returns_false_and_not_connected() {
		// Arrange - Create a mock gateway with needs_setup method returning false.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'needs_setup' ) )
			->addMethods( array( 'is_account_connected' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect needs_setup method to return false.
		$gateway->expects( $this->once() )
			->method( 'needs_setup' )
			->willReturn( false );

		// Expect is_account_connected to be called and return false.
		$gateway->expects( $this->once() )
			->method( 'is_account_connected' )
			->willReturn( false );

		// Act.
		$result = $this->sut->needs_setup( $gateway );

		// Assert - Should return true because account is not connected.
		$this->assertTrue( $result );
	}

	/**
	 * Test needs_setup fallback logic when method returns false and account is connected.
	 */
	public function test_needs_setup_fallback_when_method_returns_false_and_is_connected() {
		// Arrange - Create a mock gateway with needs_setup method returning false.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'needs_setup' ) )
			->addMethods( array( 'is_account_connected' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect needs_setup method to return false.
		$gateway->expects( $this->once() )
			->method( 'needs_setup' )
			->willReturn( false );

		// Expect is_account_connected to be called and return true.
		$gateway->expects( $this->once() )
			->method( 'is_account_connected' )
			->willReturn( true );

		// Act.
		$result = $this->sut->needs_setup( $gateway );

		// Assert - Should return false because account is connected.
		$this->assertFalse( $result );
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
	 * Test is_in_test_mode with testmode property.
	 * Use a mock gateway without is_test_mode() method to test property fallback.
	 */
	public function test_is_in_test_mode_with_testmode_property() {
		// Arrange - Create a mock gateway without is_test_mode() or is_in_test_mode() methods.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->getMock();

		$gateway->id = 'test_gateway';

		// Test with testmode property set to true.
		$gateway->testmode = true;
		$this->assertTrue( $this->sut->is_in_test_mode( $gateway ) );

		// Test with testmode property set to false.
		$gateway->testmode = false;
		$this->assertFalse( $this->sut->is_in_test_mode( $gateway ) );

		// Test with string values.
		$gateway->testmode = 'yes';
		$this->assertTrue( $this->sut->is_in_test_mode( $gateway ) );

		$gateway->testmode = 'no';
		$this->assertFalse( $this->sut->is_in_test_mode( $gateway ) );
	}

	/**
	 * Test is_in_test_mode with mode option fallback.
	 * Use a mock gateway without methods or properties to test get_option fallback.
	 */
	public function test_is_in_test_mode_with_mode_option() {
		// Test mode option with 'test' value.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_option' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect get_option to be called for 'test_mode', 'testmode', and 'mode'.
		$gateway->expects( $this->exactly( 3 ) )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $key, $default_value ) {
					unset( $default_value ); // Avoid parameter not used PHPCS errors.
					if ( 'mode' === $key ) {
						return 'test';
					}
					return 'not_found';
				}
			);

		$this->assertTrue( $this->sut->is_in_test_mode( $gateway ) );

		// Test with 'sandbox' value.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_option' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		$gateway->expects( $this->exactly( 3 ) )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $key, $default_value ) {
					unset( $default_value ); // Avoid parameter not used PHPCS errors.
					if ( 'mode' === $key ) {
						return 'sandbox';
					}
					return 'not_found';
				}
			);

		$this->assertTrue( $this->sut->is_in_test_mode( $gateway ) );

		// Test with 'live' value.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_option' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		$gateway->expects( $this->exactly( 3 ) )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $key, $default_value ) {
					unset( $default_value ); // Avoid parameter not used PHPCS errors.
					if ( 'mode' === $key ) {
						return 'live';
					}
					return 'not_found';
				}
			);

		$this->assertFalse( $this->sut->is_in_test_mode( $gateway ) );

		// Test with 'production' value.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_option' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		$gateway->expects( $this->exactly( 3 ) )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $key, $default_value ) {
					unset( $default_value ); // Avoid parameter not used PHPCS errors.
					if ( 'mode' === $key ) {
						return 'production';
					}
					return 'not_found';
				}
			);

		$this->assertFalse( $this->sut->is_in_test_mode( $gateway ) );
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
	 * Test get_provider_links with valid links.
	 */
	public function test_get_provider_links_with_valid_links() {
		// Arrange - Test all accepted link types.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
						'url'   => 'https://example.com/about',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_DOCS,
						'url'   => 'https://example.com/docs',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
						'url'   => 'https://example.com/support',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_PRICING,
						'url'   => 'https://example.com/pricing',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_TERMS,
						'url'   => 'https://example.com/terms',
					),
				),
			)
		);

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway, 'US' );

		// Assert.
		$this->assertCount( 5, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_ABOUT, $links[0]['_type'] );
		$this->assertEquals( 'https://example.com/about', $links[0]['url'] );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_DOCS, $links[1]['_type'] );
		$this->assertEquals( 'https://example.com/docs', $links[1]['url'] );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_SUPPORT, $links[2]['_type'] );
		$this->assertEquals( 'https://example.com/support', $links[2]['url'] );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_PRICING, $links[3]['_type'] );
		$this->assertEquals( 'https://example.com/pricing', $links[3]['url'] );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_TERMS, $links[4]['_type'] );
		$this->assertEquals( 'https://example.com/terms', $links[4]['url'] );
	}

	/**
	 * Test get_provider_links without provider_links set.
	 */
	public function test_get_provider_links_without_provider_links() {
		// Arrange - Create a gateway without provider_links set.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array() );

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway );

		// Assert.
		$this->assertEquals( array(), $links );
	}

	/**
	 * Test get_provider_links when the method is not defined on the gateway.
	 */
	public function test_get_provider_links_when_method_not_defined() {
		// Arrange - Create a mock gateway without the get_provider_links method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->getMock();

		// Set the id property that might be used in error logging.
		$gateway->id = 'test_gateway';

		// Act.
		$links = $this->sut->get_provider_links( $gateway );

		// Assert - Should return empty array when method doesn't exist.
		$this->assertEquals( array(), $links );
	}

	/**
	 * Test that when a gateway implements get_provider_links, the method gets called and returned links are used.
	 */
	public function test_get_provider_links_method_is_called_and_links_are_used() {
		// Arrange - Create a mock gateway with the get_provider_links method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'get_provider_links' ) )
			->getMock();

		// Set the id property that might be used in error logging.
		$gateway->id = 'test_gateway';

		// Expected links to be returned by the mock.
		$expected_links = array(
			array(
				'_type' => PaymentsProviders::LINK_TYPE_DOCS,
				'url'   => 'https://example.com/documentation',
			),
			array(
				'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
				'url'   => 'https://example.com/support-page',
			),
		);

		// Expect the get_provider_links method to be called once with the country code 'US'.
		$gateway->expects( $this->once() )
			->method( 'get_provider_links' )
			->with( 'US' )
			->willReturn( $expected_links );

		// Act.
		$links = $this->sut->get_provider_links( $gateway, 'US' );

		// Assert - Verify the links returned match what the gateway method provided.
		$this->assertCount( 2, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_DOCS, $links[0]['_type'] );
		$this->assertEquals( 'https://example.com/documentation', $links[0]['url'] );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_SUPPORT, $links[1]['_type'] );
		$this->assertEquals( 'https://example.com/support-page', $links[1]['url'] );
	}

	/**
	 * Test get_provider_links with invalid link types.
	 */
	public function test_get_provider_links_with_invalid_types() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => 'invalid_type',
						'url'   => 'https://example.com/invalid',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
						'url'   => 'https://example.com/support',
					),
					array(
						// Missing _type.
						'url' => 'https://example.com/no-type',
					),
					array(
						'_type' => '', // Empty type.
						'url'   => 'https://example.com/empty-type',
					),
				),
			)
		);

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway );

		// Assert - Only the valid one should remain.
		$this->assertCount( 1, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_SUPPORT, $links[0]['_type'] );
		$this->assertEquals( 'https://example.com/support', $links[0]['url'] );
	}

	/**
	 * Test get_provider_links with invalid URLs.
	 */
	public function test_get_provider_links_with_invalid_urls() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
						'url'   => 'not_a_valid_url',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_DOCS,
						'url'   => 'https://example.com/docs',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
						// Missing url.
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_PRICING,
						'url'   => '', // Empty URL.
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_TERMS,
						'url'   => 123, // Wrong type.
					),
				),
			)
		);

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway );

		// Assert - Only the valid one should remain.
		$this->assertCount( 1, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_DOCS, $links[0]['_type'] );
		$this->assertEquals( 'https://example.com/docs', $links[0]['url'] );
	}

	/**
	 * Test get_provider_links with non-array items.
	 */
	public function test_get_provider_links_with_non_array_items() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					'not_an_array',
					123,
					true,
					array(
						'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
						'url'   => 'https://example.com/support',
					),
					null,
				),
			)
		);

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway );

		// Assert - Only the valid one should remain.
		$this->assertCount( 1, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_SUPPORT, $links[0]['_type'] );
		$this->assertEquals( 'https://example.com/support', $links[0]['url'] );
	}

	/**
	 * Test get_provider_links with non-array return value.
	 */
	public function test_get_provider_links_with_non_array_return() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => 'not_an_array',
			)
		);

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway );

		// Assert.
		$this->assertEquals( array(), $links );
	}

	/**
	 * Test get_provider_links with empty array.
	 */
	public function test_get_provider_links_with_empty_array() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(),
			)
		);

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway );

		// Assert.
		$this->assertEquals( array(), $links );
	}

	/**
	 * Test get_provider_links URL sanitization.
	 */
	public function test_get_provider_links_url_sanitization() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
						'url'   => 'https://example.com/support?param=value&special=<script>alert("xss")</script>',
					),
				),
			)
		);

		// Act.
		$links = $this->sut->get_provider_links( $fake_gateway );

		// Assert - URL should be escaped.
		$this->assertCount( 1, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_SUPPORT, $links[0]['_type'] );
		$this->assertStringContainsString( 'https://example.com/support', $links[0]['url'] );
		$this->assertStringNotContainsString( '<script>', $links[0]['url'] );
	}

	/**
	 * Test get_provider_links includes in get_details.
	 */
	public function test_get_provider_links_in_get_details() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'plugin_slug'    => 'test-gateway',
				'plugin_file'    => 'test-gateway/test-gateway.php',
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_DOCS,
						'url'   => 'https://example.com/docs',
					),
					array(
						'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
						'url'   => 'https://example.com/support',
					),
				),
			)
		);

		// Act.
		$details = $this->sut->get_details( $fake_gateway );

		// Assert - Links should be included.
		$this->assertArrayHasKey( 'links', $details );
		$this->assertCount( 2, $details['links'] );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_DOCS, $details['links'][0]['_type'] );
		$this->assertEquals( 'https://example.com/docs', $details['links'][0]['url'] );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_SUPPORT, $details['links'][1]['_type'] );
		$this->assertEquals( 'https://example.com/support', $details['links'][1]['url'] );
	}

	/**
	 * Test that when a gateway implements needs_setup(), the method gets called and returned value is used.
	 */
	public function test_needs_setup_method_is_called() {
		// Arrange - Create a mock gateway with the needs_setup method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'needs_setup' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the needs_setup method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'needs_setup' )
			->willReturn( true );

		// Act.
		$result = $this->sut->needs_setup( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements is_test_mode(), the method gets called and returned value is used.
	 */
	public function test_is_test_mode_method_is_called() {
		// Arrange - Create a mock gateway with the is_test_mode method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_test_mode' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_test_mode method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'is_test_mode' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_in_test_mode( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements is_in_test_mode(), the method gets called and returned value is used.
	 */
	public function test_is_in_test_mode_method_is_called() {
		// Arrange - Create a mock gateway with the is_in_test_mode method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_in_test_mode' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_in_test_mode method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'is_in_test_mode' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_in_test_mode( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements is_dev_mode(), the method gets called and returned value is used.
	 */
	public function test_is_dev_mode_method_is_called() {
		// Arrange - Create a mock gateway with the is_dev_mode method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_dev_mode' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_dev_mode method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'is_dev_mode' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_in_dev_mode( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements is_in_dev_mode(), the method gets called and returned value is used.
	 */
	public function test_is_in_dev_mode_method_is_called() {
		// Arrange - Create a mock gateway with the is_in_dev_mode method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_in_dev_mode' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_in_dev_mode method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'is_in_dev_mode' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_in_dev_mode( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements is_account_connected(), the method gets called and returned value is used.
	 */
	public function test_is_account_connected_method_is_called() {
		// Arrange - Create a mock gateway with the is_account_connected method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_account_connected' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_account_connected method to be called once and return false.
		$gateway->expects( $this->once() )
			->method( 'is_account_connected' )
			->willReturn( false );

		// Act.
		$result = $this->sut->is_account_connected( $gateway );

		// Assert.
		$this->assertFalse( $result );
	}

	/**
	 * Test that when a gateway implements is_connected(), the method gets called and returned value is used.
	 */
	public function test_is_connected_method_is_called() {
		// Arrange - Create a mock gateway with the is_connected method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_connected' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_connected method to be called once and return false.
		$gateway->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		// Act.
		$result = $this->sut->is_account_connected( $gateway );

		// Assert.
		$this->assertFalse( $result );
	}

	/**
	 * Test that when a gateway implements is_onboarding_started(), the method gets called and returned value is used.
	 */
	public function test_is_onboarding_started_method_is_called() {
		// Arrange - Create a mock gateway with the is_onboarding_started method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_onboarding_started' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_onboarding_started method to be called once and return false.
		$gateway->expects( $this->once() )
			->method( 'is_onboarding_started' )
			->willReturn( false );

		// Act.
		$result = $this->sut->is_onboarding_started( $gateway );

		// Assert.
		$this->assertFalse( $result );
	}

	/**
	 * Test that when a gateway implements is_onboarding_completed(), the method gets called and returned value is used.
	 */
	public function test_is_onboarding_completed_method_is_called() {
		// Arrange - Create a mock gateway with both methods for onboarding.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_onboarding_started', 'is_onboarding_completed' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect is_onboarding_started to return true (prerequisite).
		$gateway->expects( $this->once() )
			->method( 'is_onboarding_started' )
			->willReturn( true );

		// Expect the is_onboarding_completed method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'is_onboarding_completed' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_onboarding_completed( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements is_account_partially_onboarded(), the method gets called and returned value is used.
	 */
	public function test_is_account_partially_onboarded_method_is_called() {
		// Arrange - Create a mock gateway with the is_account_partially_onboarded method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_onboarding_started', 'is_account_partially_onboarded' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect is_onboarding_started to return true (prerequisite).
		$gateway->expects( $this->once() )
			->method( 'is_onboarding_started' )
			->willReturn( true );

		// Expect the is_account_partially_onboarded method to be called once and return true.
		// When partially onboarded, is_onboarding_completed should return false (inverted).
		$gateway->expects( $this->once() )
			->method( 'is_account_partially_onboarded' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_onboarding_completed( $gateway );

		// Assert - Should be false because partially onboarded.
		$this->assertFalse( $result );
	}

	/**
	 * Test that when a gateway implements is_test_mode_onboarding(), the method gets called and returned value is used.
	 */
	public function test_is_test_mode_onboarding_method_is_called() {
		// Arrange - Create a mock gateway with the is_test_mode_onboarding method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_test_mode_onboarding' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_test_mode_onboarding method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'is_test_mode_onboarding' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_in_test_mode_onboarding( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements is_in_test_mode_onboarding(), the method gets called and returned value is used.
	 */
	public function test_is_in_test_mode_onboarding_method_is_called() {
		// Arrange - Create a mock gateway with the is_in_test_mode_onboarding method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'is_in_test_mode_onboarding' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the is_in_test_mode_onboarding method to be called once and return true.
		$gateway->expects( $this->once() )
			->method( 'is_in_test_mode_onboarding' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_in_test_mode_onboarding( $gateway );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test that when a gateway implements get_settings_url(), the method gets called and returned URL is used.
	 */
	public function test_get_settings_url_method_is_called() {
		// Arrange - Create a mock gateway with the get_settings_url method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'get_settings_url' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the get_settings_url method to be called once and return a valid URL.
		$gateway->expects( $this->once() )
			->method( 'get_settings_url' )
			->willReturn( 'https://example.com/gateway-settings' );

		// Act.
		$result = $this->sut->get_settings_url( $gateway );

		// Assert - Should return the URL with the 'from' parameter added.
		$this->assertStringContainsString( 'https://example.com/gateway-settings', $result );
		$this->assertStringContainsString( 'from=' . Payments::FROM_PAYMENTS_SETTINGS, $result );
	}

	/**
	 * Test that when a gateway implements get_connection_url(), the method gets called and returned URL is used.
	 */
	public function test_get_connection_url_method_is_called() {
		// Arrange - Create a mock gateway with the get_connection_url method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'get_connection_url' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expect the get_connection_url method to be called once with a return URL parameter.
		$gateway->expects( $this->once() )
			->method( 'get_connection_url' )
			->with( $this->stringContains( 'admin.php?page=wc-settings' ) )
			->willReturn( 'https://example.com/onboard' );

		// Act.
		$result = $this->sut->get_onboarding_url( $gateway );

		// Assert.
		$this->assertEquals( 'https://example.com/onboard', $result );
	}

	/**
	 * Test that when a gateway implements get_recommended_payment_methods(), the method gets called and returned data is used.
	 */
	public function test_get_recommended_payment_methods_method_is_called() {
		// Arrange - Create a mock gateway with the get_recommended_payment_methods method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'get_recommended_payment_methods' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expected payment methods to be returned by the mock.
		$expected_methods = array(
			array(
				'id'    => 'card',
				'title' => 'Credit Card',
			),
			array(
				'id'    => 'bank_transfer',
				'title' => 'Bank Transfer',
			),
		);

		// Expect the get_recommended_payment_methods method to be called once with country code 'GB'.
		$gateway->expects( $this->once() )
			->method( 'get_recommended_payment_methods' )
			->with( 'GB' )
			->willReturn( $expected_methods );

		// Act.
		$result = $this->sut->get_recommended_payment_methods( $gateway, 'GB' );

		// Assert - Verify the methods returned are properly processed.
		$this->assertCount( 2, $result );
		$this->assertEquals( 'card', $result[0]['id'] );
		$this->assertEquals( 'Credit Card', $result[0]['title'] );
		$this->assertEquals( 'bank_transfer', $result[1]['id'] );
		$this->assertEquals( 'Bank Transfer', $result[1]['title'] );
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

	/**
	 * Test is_onboarding_supported returns null when gateway doesn't provide the method.
	 */
	public function test_is_onboarding_supported_returns_null_when_method_not_provided() {
		// Arrange - Create a simple gateway without the is_onboarding_supported method.
		$basic_gateway = new class() extends \WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'basic_gateway';
			}
		};

		// Act.
		$result = $this->sut->is_onboarding_supported( $basic_gateway, 'US' );

		// Assert - should return null when the gateway doesn't provide the method.
		$this->assertNull( $result );
	}

	/**
	 * Test is_onboarding_supported returns value from gateway method when provided.
	 */
	public function test_is_onboarding_supported_returns_gateway_value_when_provided() {
		// Arrange - Create a mock gateway with is_onboarding_supported method.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_supported' => true,
			)
		);

		// Act.
		$result = $this->sut->is_onboarding_supported( $fake_gateway, 'US' );

		// Assert - should return true when gateway provides true.
		$this->assertTrue( $result );

		// Test with false value.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_supported' => false,
			)
		);

		// Act.
		$result = $this->sut->is_onboarding_supported( $fake_gateway, 'XX' );

		// Assert - should return false when gateway provides false.
		$this->assertFalse( $result );
	}

	/**
	 * Test is_onboarding_supported handles string values correctly.
	 */
	public function test_is_onboarding_supported_handles_string_values() {
		// Arrange - Test with 'yes' string value.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_supported' => 'yes',
			)
		);

		// Act.
		$result = $this->sut->is_onboarding_supported( $fake_gateway, 'US' );

		// Assert - should convert 'yes' to true.
		$this->assertTrue( $result );

		// Test with 'no' string value.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_supported' => 'no',
			)
		);

		// Act.
		$result = $this->sut->is_onboarding_supported( $fake_gateway, 'US' );

		// Assert - should convert 'no' to false.
		$this->assertFalse( $result );
	}

	/**
	 * Test get_onboarding_not_supported_message returns null when gateway doesn't provide the method.
	 */
	public function test_get_onboarding_not_supported_message_returns_null_when_method_not_provided() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway( 'gateway1', array() );

		// Act.
		$result = $this->sut->get_onboarding_not_supported_message( $fake_gateway, 'XX' );

		// Assert - should return null when the gateway doesn't provide the method.
		$this->assertNull( $result );
	}

	/**
	 * Test get_onboarding_not_supported_message returns value from gateway method when provided.
	 */
	public function test_get_onboarding_not_supported_message_returns_gateway_value_when_provided() {
		// Arrange - Create a mock gateway with get_onboarding_not_supported_message method.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_not_supported_message' => 'This gateway is not available in your country.',
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_not_supported_message( $fake_gateway, 'XX' );

		// Assert - should return the message from the gateway.
		$this->assertEquals( 'This gateway is not available in your country.', $result );
	}

	/**
	 * Test get_onboarding_not_supported_message sanitizes the message.
	 */
	public function test_get_onboarding_not_supported_message_sanitizes_message() {
		// Arrange - Create a gateway with a message containing HTML/special characters.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_not_supported_message' => '  <script>alert("test")</script>This gateway is not available.  ',
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_not_supported_message( $fake_gateway, 'XX' );

		// Assert - should sanitize and trim the message.
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringContainsString( 'This gateway is not available', $result );
	}

	/**
	 * Test get_onboarding_not_supported_message returns null for empty or invalid values.
	 */
	public function test_get_onboarding_not_supported_message_returns_null_for_invalid_values() {
		// Test with empty string.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_not_supported_message' => '',
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_not_supported_message( $fake_gateway, 'XX' );

		// Assert - should return null for empty string.
		$this->assertNull( $result );

		// Test with non-string value.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_not_supported_message' => array( 'message' ),
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_not_supported_message( $fake_gateway, 'XX' );

		// Assert - should return null for non-string value.
		$this->assertNull( $result );
	}

	/**
	 * Test get_details with unsupported onboarding country.
	 */
	public function test_get_details_with_unsupported_onboarding() {
		// Arrange - Create a gateway that doesn't support onboarding for a specific country.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'onboarding_supported'             => false,
				'onboarding_not_supported_message' => 'This gateway is not supported in your country.',
			)
		);

		// Act.
		$gateway_details = $this->sut->get_details( $fake_gateway, 0, 'XX' );

		// Assert - should include the unsupported state and message.
		$this->assertArrayHasKey( 'onboarding', $gateway_details );
		$this->assertArrayHasKey( 'state', $gateway_details['onboarding'] );
		$this->assertArrayHasKey( 'supported', $gateway_details['onboarding']['state'] );
		$this->assertFalse( $gateway_details['onboarding']['state']['supported'] );

		$this->assertArrayHasKey( 'messages', $gateway_details['onboarding'] );
		$this->assertArrayHasKey( 'not_supported', $gateway_details['onboarding']['messages'] );
		$this->assertEquals( 'This gateway is not supported in your country.', $gateway_details['onboarding']['messages']['not_supported'] );
	}

	/**
	 * Test get_provider_links with empty country code does not cause issues.
	 *
	 * Empty country code is a valid input (parameter is optional) and should not trigger
	 * any debug logging or errors.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/62380
	 */
	public function test_get_provider_links_with_empty_country_code() {
		// Arrange - Create a gateway with provider links.
		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_DOCS,
						'url'   => 'https://example.com/docs',
					),
				),
			)
		);

		// Act - Call with empty country code (the default when not provided).
		$links = $this->sut->get_provider_links( $fake_gateway, '' );

		// Assert - Should return links successfully without any issues.
		$this->assertCount( 1, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_DOCS, $links[0]['_type'] );
		$this->assertEquals( 'https://example.com/docs', $links[0]['url'] );
	}

	/**
	 * Test get_provider_links with various country code formats.
	 *
	 * This tests that valid ISO 3166-1 alpha-2 country codes work correctly,
	 * while invalid (non-empty) country codes are handled gracefully.
	 *
	 * @dataProvider data_provider_country_codes
	 *
	 * @param string $country_code The country code to test.
	 * @param bool   $is_valid     Whether the country code is valid.
	 */
	public function test_get_provider_links_with_various_country_codes( string $country_code, bool $is_valid ) {
		// Arrange - Create a mock gateway with the get_provider_links method.
		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
			->disableOriginalConstructor()
			->addMethods( array( 'get_provider_links' ) )
			->getMock();

		$gateway->id = 'test_gateway';

		// Expected links to be returned by the mock.
		$expected_links = array(
			array(
				'_type' => PaymentsProviders::LINK_TYPE_DOCS,
				'url'   => 'https://example.com/docs',
			),
		);

		// The get_provider_links method should be called with the sanitized country code.
		// For valid codes, it should receive the uppercase version.
		// For invalid codes, it should receive an empty string.
		$expected_code = $is_valid ? strtoupper( $country_code ) : '';
		$gateway->expects( $this->once() )
			->method( 'get_provider_links' )
			->with( $expected_code )
			->willReturn( $expected_links );

		// Act.
		$links = $this->sut->get_provider_links( $gateway, $country_code );

		// Assert - Links should be returned regardless of country code validity.
		$this->assertCount( 1, $links );
		$this->assertEquals( PaymentsProviders::LINK_TYPE_DOCS, $links[0]['_type'] );
	}

	/**
	 * Data provider for country code tests.
	 *
	 * @return array Test cases with country code and validity flag.
	 */
	public function data_provider_country_codes(): array {
		return array(
			'empty string (valid - optional parameter)'    => array( '', true ),
			'valid US code'                                => array( 'US', true ),
			'valid lowercase us code (auto-uppercased)'    => array( 'us', true ),
			'valid GB code'                                => array( 'GB', true ),
			'single character (invalid)'                   => array( 'U', false ),
			'three characters (invalid)'                   => array( 'USA', false ),
			'numeric country code (invalid for alpha-2)'   => array( '12', false ),
			'mixed alphanumeric (invalid)'                 => array( 'U1', false ),
			'whitespace only (invalid after sanitization)' => array( '  ', false ),
			'special characters (invalid after sanitization)' => array( '@@', false ),
		);
	}

	/**
	 * Create a fake logger that tracks all log calls.
	 *
	 * Implements WC_Logger_Interface so it can be injected via the
	 * woocommerce_logging_class filter.
	 *
	 * @return object A fake logger with tracking capabilities.
	 */
	private function create_fake_logger(): object {
		// phpcs:disable Squiz.Commenting, Squiz.Classes.ClassFileName.NoMatch
		return new class() implements \WC_Logger_Interface {
			public array $debug_calls   = array();
			public array $info_calls    = array();
			public array $warning_calls = array();
			public array $error_calls   = array();

			public function add( $handle, $message, $level = \WC_Log_Levels::NOTICE ) {
				unset( $handle, $message, $level ); // Avoid parameter not used PHPCS errors.
				return true;
			}

			public function log( $level, $message, $context = array() ) {
				unset( $level, $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function emergency( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function alert( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function critical( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function notice( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function debug( $message, $context = array() ) {
				$this->debug_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}

			public function info( $message, $context = array() ) {
				$this->info_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}

			public function warning( $message, $context = array() ) {
				$this->warning_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}

			public function error( $message, $context = array() ) {
				$this->error_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}

			public function reset() {
				$this->debug_calls   = array();
				$this->info_calls    = array();
				$this->warning_calls = array();
				$this->error_calls   = array();
			}

			public function has_any_logs(): bool {
				return ! empty( $this->debug_calls )
					|| ! empty( $this->info_calls )
					|| ! empty( $this->warning_calls )
					|| ! empty( $this->error_calls );
			}
		};
		// phpcs:enable Squiz.Commenting, Squiz.Classes.ClassFileName.NoMatch
	}

	/**
	 * Test that NO logging is triggered for empty country code.
	 *
	 * Empty country code is a valid input (parameter is optional), so it should
	 * not cause any logging at any level.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/62380
	 */
	public function test_get_provider_links_no_logging_for_empty_country_code() {
		// Arrange.
		$fake_logger = $this->create_fake_logger();

		// Use the woocommerce_logging_class filter to inject the fake logger.
		// Passing an object bypasses the cache check and uses the object directly.
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_DOCS,
						'url'   => 'https://example.com/docs',
					),
				),
			)
		);

		// Act - Call with empty country code.
		$this->sut->get_provider_links( $fake_gateway, '' );

		// Assert - No logging should have occurred at any level.
		$this->assertEmpty( $fake_logger->debug_calls, 'Debug logging should NOT be triggered for empty country code' );
		$this->assertEmpty( $fake_logger->info_calls, 'Info logging should NOT be triggered for empty country code' );
		$this->assertEmpty( $fake_logger->warning_calls, 'Warning logging should NOT be triggered for empty country code' );
		$this->assertEmpty( $fake_logger->error_calls, 'Error logging should NOT be triggered for empty country code' );
		$this->assertFalse( $fake_logger->has_any_logs(), 'No logging should occur for empty country code' );

		// Clean up - Remove filter.
		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * Test that NO logging is triggered for valid country codes.
	 *
	 * Valid ISO 3166-1 alpha-2 country codes should not cause any logging.
	 *
	 * @dataProvider data_provider_valid_country_codes_for_logging
	 *
	 * @param string $country_code The country code to test.
	 */
	public function test_get_provider_links_no_logging_for_valid_country_codes( string $country_code ) {
		// Arrange.
		$fake_logger = $this->create_fake_logger();

		// Use the woocommerce_logging_class filter to inject the fake logger.
		// Passing an object bypasses the cache check and uses the object directly.
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_DOCS,
						'url'   => 'https://example.com/docs',
					),
				),
			)
		);

		// Act.
		$this->sut->get_provider_links( $fake_gateway, $country_code );

		// Assert - No logging should have occurred at any level.
		$this->assertEmpty( $fake_logger->debug_calls, "Debug logging should NOT be triggered for valid country code: {$country_code}" );
		$this->assertEmpty( $fake_logger->info_calls, "Info logging should NOT be triggered for valid country code: {$country_code}" );
		$this->assertEmpty( $fake_logger->warning_calls, "Warning logging should NOT be triggered for valid country code: {$country_code}" );
		$this->assertEmpty( $fake_logger->error_calls, "Error logging should NOT be triggered for valid country code: {$country_code}" );
		$this->assertFalse( $fake_logger->has_any_logs(), "No logging should occur for valid country code: {$country_code}" );

		// Clean up - Remove filter.
		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * Data provider for valid country codes (no logging expected).
	 *
	 * @return array Test cases with valid country codes.
	 */
	public function data_provider_valid_country_codes_for_logging(): array {
		return array(
			'US'        => array( 'US' ),
			'GB'        => array( 'GB' ),
			'DE'        => array( 'DE' ),
			'lowercase' => array( 'us' ), // Will be uppercased to valid "US".
		);
	}

	/**
	 * Test that debug logging IS triggered for invalid non-empty country codes.
	 *
	 * Invalid country codes (non-empty, not ISO 3166-1 alpha-2) should trigger
	 * debug logging so developers can investigate.
	 *
	 * @dataProvider data_provider_invalid_country_codes_for_logging
	 *
	 * @param string $country_code The invalid country code to test.
	 */
	public function test_get_provider_links_logging_for_invalid_country_codes( string $country_code ) {
		// Arrange.
		$fake_logger = $this->create_fake_logger();

		// Use the woocommerce_logging_class filter to inject the fake logger.
		// Passing an object bypasses the cache check and uses the object directly.
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$fake_gateway = new FakePaymentGateway(
			'gateway1',
			array(
				'provider_links' => array(
					array(
						'_type' => PaymentsProviders::LINK_TYPE_DOCS,
						'url'   => 'https://example.com/docs',
					),
				),
			)
		);

		// Act.
		$this->sut->get_provider_links( $fake_gateway, $country_code );

		// Assert - Debug logging SHOULD have been triggered for invalid country code.
		$this->assertCount( 1, $fake_logger->debug_calls, "Debug logging SHOULD be triggered for invalid country code: {$country_code}" );
		$this->assertEmpty( $fake_logger->info_calls, "Info logging should NOT be triggered for invalid country code: {$country_code}" );
		$this->assertEmpty( $fake_logger->warning_calls, "Warning logging should NOT be triggered for invalid country code: {$country_code}" );
		$this->assertEmpty( $fake_logger->error_calls, "Error logging should NOT be triggered for invalid country code: {$country_code}" );

		// Verify the debug call has the expected context.
		$debug_call = $fake_logger->debug_calls[0];
		$this->assertStringContainsString( 'invalid country code', $debug_call['message'] );
		$this->assertArrayHasKey( 'source', $debug_call['context'] );
		$this->assertEquals( 'settings-payments', $debug_call['context']['source'] );
		$this->assertArrayHasKey( 'gateway', $debug_call['context'] );
		$this->assertEquals( 'gateway1', $debug_call['context']['gateway'] );
		$this->assertArrayHasKey( 'country', $debug_call['context'] );

		// Clean up - Remove filter.
		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * Data provider for invalid country codes (logging expected).
	 *
	 * @return array Test cases with invalid country codes.
	 */
	public function data_provider_invalid_country_codes_for_logging(): array {
		return array(
			'three characters'   => array( 'USA' ),
			'single character'   => array( 'U' ),
			'numeric'            => array( '12' ),
			'mixed alphanumeric' => array( 'U1' ),
		);
	}
}
