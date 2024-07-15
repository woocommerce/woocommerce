<?php
/**
 * Test the class that evaluates payment gateway suggestion visibility.
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
	 * The mock logger.
	 *
	 * @var WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_logger;

	/**
	 * Run setup code for unit tests.
	 */
	public function setUp(): void {
		parent::setUp();

		// Have a mock logger used by the rule evaluator.
		$this->mock_logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
		add_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		delete_option( self::MOCK_OPTION );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );

		parent::tearDown();
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

	/**
	 * Test that suggestion evaluation logs debug logs when logging is enabled.
	 */
	public function test_evaluation_logs() {
		add_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );

		$suggestion = array(
			'id'         => 'mock-gateway',
			'is_visible' => array(
				(object) array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
				// Only the top-level rules are logged, not the operands.
				(object) array(
					'type'     => 'or',
					'operands' => array(
						(object) array(
							'type' => 'fail',
						),
						(object) array(
							'type' => 'pass',
						),
					),
				),
				(object) array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'b', // This will fail the rule.
					'default'     => null,
					'operation'   => '=',
				),
			),
		);
		update_option( self::MOCK_OPTION, 'a' );

		$this->mock_logger_debug_calls(
			array(
				array(
					'[mock-gateway] option: passed',
					array( 'source' => 'unit-tests' ),
				),
				array(
					'[mock-gateway] or: passed',
					array( 'source' => 'unit-tests' ),
				),
				array(
					'[mock-gateway] option: failed',
					array( 'source' => 'unit-tests' ),
				),
			)
		);

		EvaluateSuggestion::evaluate( (object) $suggestion, array( 'source' => 'unit-tests' ) );

		remove_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );
	}

	/**
	 * Test that suggestion evaluation doesn't log debug logs when logging is disabled.
	 */
	public function test_evaluation_doesnt_log() {
		add_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_false' );

		$suggestion = array(
			'id'         => 'mock-gateway',
			'is_visible' => array(
				(object) array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
				(object) array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'b', // This will fail the rule.
					'default'     => null,
					'operation'   => '=',
				),
			),
		);
		update_option( self::MOCK_OPTION, 'a' );

		// No debug logs.
		$this->mock_logger_debug_calls();

		EvaluateSuggestion::evaluate( (object) $suggestion, array( 'source' => 'unit-tests' ) );

		remove_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_false' );
	}

	/**
	 * Test that suggestion evaluation logs when rule is not an object.
	 */
	public function test_evaluation_logs_when_rule_not_object() {
		add_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );

		$suggestion = array(
			'id'         => 'mock-gateway',
			'is_visible' => array(
				(object) array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
				array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
			),
		);
		update_option( self::MOCK_OPTION, 'a' );

		$this->mock_logger_debug_calls(
			array(
				array(
					'[mock-gateway] option: passed',
					array( 'source' => 'unit-tests' ),
				),
				array(
					'[mock-gateway] rule not an object: failed',
					array( 'source' => 'unit-tests' ),
				),
			)
		);

		EvaluateSuggestion::evaluate( (object) $suggestion, array( 'source' => 'unit-tests' ) );

		remove_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );
	}

	/**
	 * Test that suggestion evaluation logs an anonymous spec.
	 */
	public function test_evaluation_logs_anonymous_spec() {
		add_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );

		$suggestion = array(
			'id'         => '', // empty ID and no 'title' field.
			'is_visible' => array(
				(object) array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
				array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
			),
		);
		update_option( self::MOCK_OPTION, 'a' );

		$this->mock_logger_debug_calls(
			array(
				array(
					'[anonymous-suggestion] option: passed',
					array( 'source' => 'unit-tests' ),
				),
				array(
					'[anonymous-suggestion] rule not an object: failed',
					array( 'source' => 'unit-tests' ),
				),
			)
		);

		EvaluateSuggestion::evaluate( (object) $suggestion, array( 'source' => 'unit-tests' ) );

		remove_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );
	}

	/**
	 * Test that suggestion evaluation logs to the default source.
	 */
	public function test_evaluation_logs_to_default_source() {
		add_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );

		$suggestion = array(
			'id'         => 'mock-gateway',
			'is_visible' => array(
				(object) array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
				array(
					'type'        => 'option',
					'option_name' => self::MOCK_OPTION,
					'value'       => 'a',
					'default'     => null,
					'operation'   => '=',
				),
			),
		);
		update_option( self::MOCK_OPTION, 'a' );

		$this->mock_logger_debug_calls(
			array(
				array(
					'[mock-gateway] option: passed',
					array( 'source' => 'wc-payment-gateway-suggestions' ),
				),
				array(
					'[mock-gateway] rule not an object: failed',
					array( 'source' => 'wc-payment-gateway-suggestions' ),
				),
			)
		);

		EvaluateSuggestion::evaluate( (object) $suggestion );

		remove_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}

	/**
	 * Set expectations for the logger debug calls with each consecutive call args.
	 *
	 * @param array $calls_args List of expected arguments for each call.
	 *
	 * @return void
	 */
	private function mock_logger_debug_calls( array $calls_args = array() ) {
		if ( empty( $calls_args ) ) {
			$this->mock_logger
				->expects( $this->never() )
				->method( 'debug' );

			return;
		}

		$this->mock_logger
			->expects( $this->exactly( count( $calls_args ) ) )
			->method( 'debug' )
			->willReturnCallback(
				function ( ...$args ) use ( &$calls_args ) {
					$expected_args = array_shift( $calls_args );
					$this->assertSame( $expected_args, $args );
				}
			);
	}
}
