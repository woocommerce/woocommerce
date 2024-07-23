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
	 * Test get group query args
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
}
