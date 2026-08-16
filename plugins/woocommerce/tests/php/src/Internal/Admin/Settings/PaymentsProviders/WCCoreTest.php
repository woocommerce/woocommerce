<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WCCore;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Gateway_Paypal;

/**
 * WC core payment gateway provider service test.
 *
 * @class WCCore
 */
class WCCoreTest extends WC_Unit_Test_Case {

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * @var WCCore
	 */
	protected $sut;

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

		$this->sut = new WCCore( $this->mockable_proxy );
	}

	/**
	 * Data provider for core gateway IDs.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function provider_core_gateway_ids(): array {
		return array(
			'BACS'   => array( 'gateway_id' => WC_Gateway_BACS::ID ),
			'Cheque' => array( 'gateway_id' => WC_Gateway_Cheque::ID ),
			'COD'    => array( 'gateway_id' => WC_Gateway_COD::ID ),
			'PayPal' => array( 'gateway_id' => WC_Gateway_Paypal::ID ),
		);
	}

	/**
	 * Test get_plugin_details returns empty file path to prevent deactivation.
	 *
	 * @dataProvider provider_core_gateway_ids
	 *
	 * @param string $gateway_id The gateway ID to test.
	 */
	public function test_get_plugin_details_prevents_deactivation( string $gateway_id ) {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			$gateway_id,
			array(
				'enabled'            => true,
				'plugin_slug'        => 'woocommerce',
				'plugin_file'        => 'woocommerce/woocommerce.php',
				'method_title'       => 'Test Gateway',
				'method_description' => 'Test gateway description.',
			),
		);

		// Act.
		$plugin_details = $this->sut->get_plugin_details( $fake_gateway );

		// Assert - Core gateways should have empty file path to prevent deactivation.
		$this->assertIsArray( $plugin_details );
		$this->assertArrayHasKey( 'file', $plugin_details );
		$this->assertSame( '', $plugin_details['file'], "Gateway $gateway_id should have empty file path for the plugin details." );

		// Assert - Other expected keys should still be present.
		$this->assertArrayHasKey( '_type', $plugin_details );
		$this->assertArrayHasKey( 'slug', $plugin_details );
		$this->assertArrayHasKey( 'status', $plugin_details );
	}

	/**
	 * Test get_icon.
	 */
	public function test_get_icon() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			WC_Gateway_BACS::ID,
			array(
				'enabled'            => true,
				'plugin_slug'        => 'woocommerce',
				'plugin_file'        => 'woocommerce/woocommerce.php',
				'method_title'       => 'BACS',
				'method_description' => 'Bacs is good.',
				'supports'           => array( 'products', 'something', 'bogus' ),
				'icon'               => 'https://example.com/icon.png',
			),
		);

		// Act.
		$gateway_details = $this->sut->get_icon( $fake_gateway );

		// Assert.
		$this->assertEquals( plugins_url( 'assets/images/payment_methods/bacs.svg', WC_PLUGIN_FILE ), $gateway_details );

		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			WC_Gateway_Cheque::ID,
			array(
				'enabled'            => true,
				'plugin_slug'        => 'woocommerce',
				'plugin_file'        => 'woocommerce/woocommerce.php',
				'method_title'       => 'Cheque',
				'method_description' => 'Cheque is good.',
				'supports'           => array( 'products', 'something', 'bogus' ),
				'icon'               => 'https://example.com/icon.png',
			),
		);

		// Act.
		$gateway_details = $this->sut->get_icon( $fake_gateway );

		// Assert.
		$this->assertEquals( plugins_url( 'assets/images/payment_methods/cheque.svg', WC_PLUGIN_FILE ), $gateway_details );

		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			WC_Gateway_COD::ID,
			array(
				'enabled'            => true,
				'plugin_slug'        => 'woocommerce',
				'plugin_file'        => 'woocommerce/woocommerce.php',
				'method_title'       => 'COD',
				'method_description' => 'COD is good.',
				'supports'           => array( 'products', 'something', 'bogus' ),
				'icon'               => 'https://example.com/icon.png',
			),
		);

		// Act.
		$gateway_details = $this->sut->get_icon( $fake_gateway );

		// Assert.
		$this->assertEquals( plugins_url( 'assets/images/payment_methods/cod.svg', WC_PLUGIN_FILE ), $gateway_details );

		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			WC_Gateway_Paypal::ID,
			array(
				'enabled'            => true,
				'plugin_slug'        => 'woocommerce',
				'plugin_file'        => 'woocommerce/woocommerce.php',
				'method_title'       => 'Paypal',
				'method_description' => 'Paypal is good.',
				'supports'           => array( 'products', 'something', 'bogus' ),
				'icon'               => 'https://example.com/icon.png',
			),
		);

		// Act.
		$gateway_details = $this->sut->get_icon( $fake_gateway );

		// Assert.
		$this->assertEquals( plugins_url( 'assets/images/payment_methods/72x72/paypal.png', WC_PLUGIN_FILE ), $gateway_details );
	}
}
