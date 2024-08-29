<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\McStats;

/**
 * Test Mc Stats class
 */
class McStatsTest extends \WC_Unit_Test_Case {

	/**
	 * Set up. Runs before each test.
	 */
	public function setUp(): void {
		$this->sut = wc_get_container()->get( McStats::class );
	}

	/**
	 * @testdox Test get group query args
	 */
	public function test_get_group_query_args() {

		$this->sut->add( 'group', 'test' );
		$this->sut->add( 'group', 'test2' );

		$this->assertEmpty( $this->sut->get_group_query_args( 'group2' ) );

		$check = $this->sut->get_group_query_args( 'group' );

		$this->assertCount( 1, $check );
		$this->assertArrayHasKey( 'x_woocommerce-group', $check );
		$this->assertEquals( 'test,test2', $check['x_woocommerce-group'] );
	}
	/**
	 * @testdox Test that do_stats() doesn't output anything when tracking is disabled.
	 */
	public function test_do_stats_tracking_disabled() {
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'no';
			}
		);

		ob_start();
		$this->sut->do_stats();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * @testdox Test that do_stats() correctly outputs tracking pixels and clears stats when tracking is enabled.
	 */
	public function test_do_stats_tracking_enabled() {
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);

		$mock = $this->getMockBuilder( McStats::class )
				->setMethods( array( 'get_stats_urls' ) )
				->getMock();

		$mock->expects( $this->once() )
			->method( 'get_stats_urls' )
			->willReturn( array( 'http://example.com/stat1', 'http://example.com/stat2' ) );

		ob_start();
		$mock->do_stats();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<img src="http://example.com/stat1" width="1" height="1" style="display:none;" />', $output );
		$this->assertStringContainsString( '<img src="http://example.com/stat2" width="1" height="1" style="display:none;" />', $output );
	}


	/**
	 * @testdox Test do server side stat tracking disabled
	 */
	public function test_do_server_side_stat_tracking_disabled() {
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'no';
			}
		);

		$result = $this->sut->do_server_side_stat( 'http://example.com' );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox Test do server side stat tracking enabled
	 */
	public function test_do_server_side_stat_tracking_enabled() {
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);

		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) {
				$this->assertEquals( 'http://example.com', $url );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => 'success',
				);
			},
			10,
			3
		);

		$result = $this->sut->do_server_side_stat( 'http://example.com' );
		$this->assertTrue( $result );

		remove_all_filters( 'pre_http_request' );
	}
}
