<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\MiniCartFooterBlock as MiniCartFooterBlockType;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * Tests for the MiniCartFooterBlock block type.
 *
 * @since 10.4.0
 */
class MiniCartFooterBlock extends \WP_UnitTestCase {

	/**
	 * Instance of the block being tested.
	 *
	 * @var MiniCartFooterBlockType
	 */
	protected $block;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/mini-cart-footer-block' ) ) {
			$registry->unregister( 'woocommerce/mini-cart-footer-block' );
		}

		$this->block = new MiniCartFooterBlockType(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		);
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/mini-cart-footer-block' ) ) {
			$registry->unregister( 'woocommerce/mini-cart-footer-block' );
		}
		unset( $this->block );
		parent::tearDown();
	}

	/**
	 * Data provider for testing get_total_items_description().
	 *
	 * @return array<string, array{bool, bool, bool, string}>
	 */
	public function provider_total_items_description_scenarios(): array {
		return array(
			'all three enabled'    => array( true, true, true, 'Shipping, taxes, and discounts calculated at checkout.' ),
			'shipping and taxes'   => array( true, true, false, 'Shipping and taxes calculated at checkout.' ),
			'shipping and coupons' => array( true, false, true, 'Shipping and discounts calculated at checkout.' ),
			'taxes and coupons'    => array( false, true, true, 'Taxes and discounts calculated at checkout.' ),
			'only shipping'        => array( true, false, false, 'Shipping calculated at checkout.' ),
			'only taxes'           => array( false, true, false, 'Taxes calculated at checkout.' ),
			'only coupons'         => array( false, false, true, 'Discounts calculated at checkout.' ),
			'none enabled'         => array( false, false, false, '' ),
		);
	}

	/**
	 * Test get_total_items_description returns correct message based on settings.
	 *
	 * @dataProvider provider_total_items_description_scenarios
	 *
	 * @param bool   $shipping_enabled Whether shipping is enabled.
	 * @param bool   $taxes_enabled    Whether taxes are enabled.
	 * @param bool   $coupons_enabled  Whether coupons are enabled.
	 * @param string $expected         Expected description text.
	 */
	public function test_get_total_items_description( bool $shipping_enabled, bool $taxes_enabled, bool $coupons_enabled, string $expected ): void {
		// Arrange - Mock WooCommerce settings.
		add_filter(
			'wc_shipping_enabled',
			function () use ( $shipping_enabled ) {
				return $shipping_enabled;
			}
		);
		add_filter(
			'wc_tax_enabled',
			function () use ( $taxes_enabled ) {
				return $taxes_enabled;
			}
		);
		add_filter(
			'pre_option_woocommerce_enable_coupons',
			function () use ( $coupons_enabled ) {
				return $coupons_enabled ? 'yes' : 'no';
			}
		);

		// Act - Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->block );
		$method     = $reflection->getMethod( 'get_totals_item_description' );
		$method->setAccessible( true );
		$result = $method->invoke( $this->block );

		// Assert.
		$this->assertSame( $expected, $result );
	}
}
