<?php
/**
 * Test the class that evalutes payment gateway suggestion visibility.
 *
 * @package WooCommerce\Admin\Tests\PaymentGatewaySuggestions
 */

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;

/**
 * class WC_Admin_Tests_PaymentGatewaySuggestions_EvaluateSuggestion
 */
class WC_Admin_Tests_PaymentGatewaySuggestions_EvaluateSuggestion extends WC_Unit_Test_Case {
	/**
	 * Mock gateway option.
	 */
	const MOCK_OPTION = 'woocommerce_admin_mock_gateway_option';

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( self::MOCK_OPTION );
	}

	/**
	 * Test that the gateway is returned as is when no rules are provided.
	 */
	public function test_no_rules() {
		$suggestion = array(
			'id' => 'mock-gateway',
		);
		$evaluated  = EvaluateSuggestion::evaluate( (object) $suggestion );
		$this->assertEquals( (object) $suggestion, $evaluated );
	}

	/**
	 * Test that the gateway is not visible when rules do not pass.
	 */
	public function test_is_not_visible() {
		$suggestion = array(
			'id'         => 'mock-gateway',
			'is_visible' => (object) array(
				'type'        => 'option',
				'option_name' => self::MOCK_OPTION,
				'value'       => 'a',
				'default'     => null,
				'operation'   => '=',
			),
		);
		$evaluated  = EvaluateSuggestion::evaluate( (object) $suggestion );
		$this->assertFalse( $evaluated->is_visible );
	}

	/**
	 * Test that the gateway is returned when visibility rules pass.
	 */
	public function test_is_visible() {
		$suggestion = array(
			'id'         => 'mock-gateway',
			'is_visible' => (object) array(
				'type'        => 'option',
				'option_name' => self::MOCK_OPTION,
				'value'       => 'a',
				'default'     => null,
				'operation'   => '=',
			),
		);
		update_option( self::MOCK_OPTION, 'a' );
		$evaluated = EvaluateSuggestion::evaluate( (object) $suggestion );
		$this->assertTrue( $evaluated->is_visible );
	}
}
