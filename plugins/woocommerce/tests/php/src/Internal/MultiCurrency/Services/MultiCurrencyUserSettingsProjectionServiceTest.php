<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyUserSettingsProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyUserSettingsProjectionService class.
 */
class MultiCurrencyUserSettingsProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project account details hook manifest when active.
	 */
	public function test_projects_account_details_hook_manifest_when_active(): void {
		$manifest = MultiCurrencyUserSettingsProjectionService::get_hook_manifest( 3 );

		$this->assertTrue( MultiCurrencyUserSettingsProjectionService::should_activate( 3 ) );
		$this->assertSame( array(), $manifest['blockers'] );
		$this->assertSame(
			array(
				array(
					'hook'     => 'woocommerce_edit_account_form',
					'callback' => 'add_presentment_currency_switch',
					'priority' => 10,
				),
				array(
					'hook'     => 'woocommerce_save_account_details',
					'callback' => 'save_presentment_currency',
					'priority' => 10,
				),
			),
			$manifest['actions']
		);
	}

	/**
	 * @testdox Should project single currency activation blocker.
	 */
	public function test_projects_single_currency_activation_blocker(): void {
		$this->assertFalse( MultiCurrencyUserSettingsProjectionService::should_activate( 1 ) );
		$this->assertSame(
			array( 'single_currency' ),
			MultiCurrencyUserSettingsProjectionService::get_activation_blockers( 1 )
		);
		$this->assertSame(
			array(
				'actions'  => array(),
				'blockers' => array( 'single_currency' ),
			),
			MultiCurrencyUserSettingsProjectionService::get_hook_manifest( 1 )
		);
	}

	/**
	 * @testdox Should project currency options with selected state.
	 */
	public function test_projects_currency_options_with_selected_state(): void {
		$options = MultiCurrencyUserSettingsProjectionService::get_currency_options(
			$this->get_enabled_currencies(),
			'EUR'
		);

		$this->assertSame(
			array(
				array(
					'code'     => 'USD',
					'symbol'   => get_woocommerce_currency_symbol( 'USD' ),
					'label'    => get_woocommerce_currency_symbol( 'USD' ) . ' USD',
					'selected' => false,
				),
				array(
					'code'     => 'GBP',
					'symbol'   => get_woocommerce_currency_symbol( 'GBP' ),
					'label'    => get_woocommerce_currency_symbol( 'GBP' ) . ' GBP',
					'selected' => false,
				),
				array(
					'code'     => 'EUR',
					'symbol'   => get_woocommerce_currency_symbol( 'EUR' ),
					'label'    => get_woocommerce_currency_symbol( 'EUR' ) . ' EUR',
					'selected' => true,
				),
			),
			$options
		);
	}

	/**
	 * @testdox Should project presentment currency field markup.
	 */
	public function test_projects_presentment_currency_field_markup(): void {
		$markup = MultiCurrencyUserSettingsProjectionService::get_presentment_currency_field_markup(
			$this->get_enabled_currencies(),
			'EUR'
		);

		$this->assertStringContainsString(
			'<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">',
			$markup
		);
		$this->assertStringContainsString(
			'<label for="wcpay_selected_currency">Default currency</label>',
			$markup
		);
		$this->assertStringContainsString(
			'<select name="wcpay_selected_currency" id="wcpay_selected_currency">',
			$markup
		);
		$this->assertStringContainsString(
			'<option value="USD">' . wp_kses_post( get_woocommerce_currency_symbol( 'USD' ) . ' USD' ) . '</option>',
			$markup
		);
		$this->assertStringContainsString(
			'<option value="EUR" selected>' . get_woocommerce_currency_symbol( 'EUR' ) . ' EUR</option>',
			$markup
		);
		$this->assertStringContainsString(
			'<span><em>Select your preferred currency for shopping and payments.</em></span>',
			$markup
		);
		$this->assertStringContainsString( '<div class="clear"></div>', $markup );
	}

	/**
	 * @testdox Should project sanitized save intent for posted currency.
	 */
	public function test_projects_sanitized_save_intent_for_posted_currency(): void {
		$this->assertSame(
			array(
				'should_update' => true,
				'currency_code' => 'GBP',
			),
			MultiCurrencyUserSettingsProjectionService::get_save_presentment_currency_intent(
				array( 'wcpay_selected_currency' => ' GBP ' )
			)
		);
	}

	/**
	 * @testdox Should project no-op save intent when posted currency is absent or invalid.
	 */
	public function test_projects_noop_save_intent_when_posted_currency_is_absent_or_invalid(): void {
		$expected = array(
			'should_update' => false,
			'currency_code' => null,
		);

		$this->assertSame( $expected, MultiCurrencyUserSettingsProjectionService::get_save_presentment_currency_intent( array() ) );
		$this->assertSame(
			$expected,
			MultiCurrencyUserSettingsProjectionService::get_save_presentment_currency_intent(
				array( 'wcpay_selected_currency' => '' )
			)
		);
		$this->assertSame(
			$expected,
			MultiCurrencyUserSettingsProjectionService::get_save_presentment_currency_intent(
				array( 'wcpay_selected_currency' => array( 'GBP' ) )
			)
		);
		$this->assertSame(
			$expected,
			MultiCurrencyUserSettingsProjectionService::get_save_presentment_currency_intent(
				array( 'wcpay_selected_currency' => '<script>GBP</script>' )
			)
		);
	}

	/**
	 * Get enabled currency fixtures.
	 *
	 * @return array<int,array{code:string,symbol:string}>
	 */
	private function get_enabled_currencies(): array {
		return array(
			array(
				'code'   => 'USD',
				'symbol' => get_woocommerce_currency_symbol( 'USD' ),
			),
			array(
				'code'   => 'GBP',
				'symbol' => get_woocommerce_currency_symbol( 'GBP' ),
			),
			array(
				'code'   => 'EUR',
				'symbol' => get_woocommerce_currency_symbol( 'EUR' ),
			),
		);
	}
}
