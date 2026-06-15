<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStorefrontProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyStorefrontProjectionService class.
 */
class MultiCurrencyStorefrontProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project Storefront theme compatibility.
	 */
	public function test_projects_storefront_theme_compatibility(): void {
		$this->assertTrue( MultiCurrencyStorefrontProjectionService::is_storefront_theme( 'storefront', 'storefront' ) );
		$this->assertTrue( MultiCurrencyStorefrontProjectionService::is_storefront_theme( 'child-theme', 'storefront' ) );
		$this->assertFalse( MultiCurrencyStorefrontProjectionService::is_storefront_theme( 'twentytwentyfive', 'twentytwentyfive' ) );
	}

	/**
	 * @testdox Should project Storefront hook manifest when active.
	 */
	public function test_projects_storefront_hook_manifest_when_active(): void {
		$manifest = MultiCurrencyStorefrontProjectionService::get_hook_manifest( 2, true );

		$this->assertTrue( MultiCurrencyStorefrontProjectionService::should_activate( 2, true ) );
		$this->assertSame( array(), $manifest['blockers'] );
		$this->assertSame(
			array(
				array(
					'hook'     => 'woocommerce_breadcrumb_defaults',
					'callback' => 'inject_switcher_into_breadcrumb',
					'priority' => 9999,
				),
			),
			$manifest['filters']
		);
		$this->assertSame(
			array(
				array(
					'hook'     => 'wp_enqueue_scripts',
					'callback' => 'add_inline_css',
					'priority' => 50,
				),
			),
			$manifest['actions']
		);
	}

	/**
	 * @testdox Should project Storefront activation blockers and simulation overrides.
	 */
	public function test_projects_storefront_activation_blockers_and_simulation_overrides(): void {
		$this->assertSame(
			array( 'single_currency' ),
			MultiCurrencyStorefrontProjectionService::get_activation_blockers( 1, true )
		);
		$this->assertSame(
			array( 'storefront_switcher_disabled' ),
			MultiCurrencyStorefrontProjectionService::get_activation_blockers( 2, false )
		);
		$this->assertSame(
			array( 'simulation_hides_switcher' ),
			MultiCurrencyStorefrontProjectionService::get_activation_blockers(
				2,
				true,
				array( 'enable_storefront_switcher' => false )
			)
		);
		$this->assertSame(
			array(),
			MultiCurrencyStorefrontProjectionService::get_activation_blockers(
				2,
				false,
				array( 'enable_storefront_switcher' => true )
			)
		);
		$this->assertSame(
			array(
				'filters'  => array(),
				'actions'  => array(),
				'blockers' => array( 'storefront_switcher_disabled' ),
			),
			MultiCurrencyStorefrontProjectionService::get_hook_manifest( 2, false )
		);
	}

	/**
	 * @testdox Should project Storefront widget CSS and filter metadata.
	 */
	public function test_projects_storefront_widget_css_and_filter_metadata(): void {
		$this->assertSame(
			array(
				'before_widget' => '<div id="woocommerce-payments-multi-currency-storefront-widget" class="woocommerce-breadcrumb">',
				'after_widget'  => '</div>',
			),
			MultiCurrencyStorefrontProjectionService::get_default_widget_args()
		);

		$style_manifest = MultiCurrencyStorefrontProjectionService::get_inline_style_manifest();

		$this->assertSame( 'storefront-style', $style_manifest['handle'] );
		$this->assertSame( 'wcpay_multi_currency_storefront_widget_css', $style_manifest['filter'] );
		$this->assertStringContainsString( '#woocommerce-payments-multi-currency-storefront-widget', $style_manifest['css'] );
		$this->assertStringContainsString( 'float: right;', $style_manifest['css'] );
		$this->assertStringContainsString( 'margin: 0;', $style_manifest['css'] );
		$this->assertSame(
			array(
				array(
					'filter'  => 'wcpay_multi_currency_storefront_widget_instance',
					'default' => array(),
				),
				array(
					'filter'  => 'wcpay_multi_currency_storefront_widget_args',
					'default' => MultiCurrencyStorefrontProjectionService::get_default_widget_args(),
				),
			),
			MultiCurrencyStorefrontProjectionService::get_widget_filter_manifest()
		);
	}

	/**
	 * @testdox Should inject switcher markup before breadcrumb nav.
	 */
	public function test_injects_switcher_markup_before_breadcrumb_nav(): void {
		$defaults = array(
			'wrap_before' => '<div class="storefront-breadcrumb"><nav class="woocommerce-breadcrumb">',
			'wrap_after'  => '</nav></div>',
		);

		$result = MultiCurrencyStorefrontProjectionService::inject_switcher_into_breadcrumb(
			$defaults,
			'<div id="woocommerce-payments-multi-currency-storefront-widget"></div>'
		);

		$this->assertSame(
			'<div class="storefront-breadcrumb"><div id="woocommerce-payments-multi-currency-storefront-widget"></div><nav class="woocommerce-breadcrumb">',
			$result['wrap_before']
		);
		$this->assertSame( '</nav></div>', $result['wrap_after'] );
		$this->assertSame(
			array( 'wrap_before' => '<span class="trail">Home</span>' ),
			MultiCurrencyStorefrontProjectionService::inject_switcher_into_breadcrumb(
				array( 'wrap_before' => '<span class="trail">Home</span>' ),
				'<div>Switcher</div>'
			)
		);
	}
}
