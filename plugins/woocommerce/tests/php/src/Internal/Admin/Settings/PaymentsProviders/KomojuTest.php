<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Komoju;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * KOMOJU payment gateway provider service test.
 *
 * @class Komoju
 */
class KomojuTest extends WC_Unit_Test_Case {

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * @var Komoju
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

		$this->sut = new Komoju( $this->mockable_proxy );
	}

	/**
	 * Test that the settings URL points to KOMOJU's own dedicated settings tab,
	 * not the generic per-gateway settings section.
	 */
	public function test_get_settings_url_points_to_komoju_settings_tab(): void {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'komoju',
			array(
				'enabled'            => true,
				'plugin_slug'        => 'komoju-japanese-payments',
				'plugin_file'        => 'komoju-japanese-payments/index',
				'method_title'       => 'KOMOJU',
				'method_description' => 'Deprecated legacy combined gateway.',
			),
		);

		// Act.
		$settings_url = $this->sut->get_settings_url( $fake_gateway );

		// Assert.
		$this->assertSame(
			admin_url( 'admin.php?page=wc-settings&tab=komoju_settings' ),
			$settings_url
		);
	}
}
