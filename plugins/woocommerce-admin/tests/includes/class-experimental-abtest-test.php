<?php
/**
 * Experimental_Abtest Tests
 *
 * @package WooCommerce\Admin
 */

use WooCommerce\Admin\Experimental_Abtest;


/**
 * Experimental_Abtest Tests
 *
 * @package WooCommerce\Admin
 */
class Experimental_Abtest_Test extends WC_Unit_Test_Case {

	/**
	 * Tests woocommerce_explat_request_args filter is used to construct
	 * the request URL.
	 */
	public function test_it_applies_filters_to_construct_request_args() {
		delete_transient( 'abtest_variation_control' );
		add_filter(
			'pre_http_request',
			function( $arg1, $arg2, $url ) {
				$this->assertTrue( false !== strpos( $url, 'test=test' ) );
				return array(
					'response'    => 200,
					'status_code' => 200,
					'success'     => 1,
					'body'        => '{}',
				);
			},
			10,
			3
		);

		add_filter(
			'woocommerce_explat_request_args',
			function( $args ) {
				$args['test'] = 'test';
				return $args;
			},
			10,
			1
		);

		$exp = new Experimental_Abtest( 'anon', 'platform', true );
		$exp->get_variation( 'control' );
	}

	/**
	 * Tests retrieve the test variation when consent is false
	 */
	public function test_get_variation_return_control_when_no_consent() {
		$exp = new Experimental_Abtest( 'anon', 'platform', false );
		$this->assertEquals(
			$exp->get_variation( 'test_experiment_name' ),
			'control'
		);
	}

		/**
		 * Tests retrieve the test variation when consent is false
		 */
	public function test_get_variation() {
		delete_transient( 'abtest_variation_control' );
		add_filter(
			'pre_http_request',
			function( $preempt, $parsed_args, $url ) {
				return array(
					'response'    => 200,
					'status_code' => 200,
					'success'     => 1,
					'body'        => '{
						"variations": {
							"test_experiment_name": "treatment"
						}
					}',
				);
			},
			10,
			3
		);

		$exp = new Experimental_Abtest( 'anon', 'platform', true );
		$this->assertEquals(
			$exp->get_variation( 'test_experiment_name' ),
			'treatment'
		);
	}
}
