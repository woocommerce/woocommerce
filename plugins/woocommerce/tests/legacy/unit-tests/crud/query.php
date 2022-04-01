<?php

/**
 * WC_Object_Query tests
 * @package WooCommerce\Tests\CRUD
 * @since 3.1.0
 */
class WC_Tests_WC_Object_Query extends WC_Unit_Test_Case {

	/**
	 * Test the default query var values.
	 *
	 * @since 3.1.0
	 */
	function test_default_query() {
		$query = new WC_Mock_WC_Object_Query();

		$this->assertNotEmpty( $query->get_query_vars() );
		$this->assertEquals( '', $query->get( 'parent' ) );
		$this->assertEquals( 'date', $query->get( 'orderby' ) );
	}

	/**
	 * Test setting/getting query vars.
	 *
	 * @since 3.1.0
	 */
	function test_query_with_args() {
		$args  = array(
			'limit' => 15,
		);
		$query = new WC_Mock_WC_Object_Query( $args );

		$this->assertEquals( 15, $query->get( 'limit' ) );
		$query->set( 'limit', 20 );
		$this->assertEquals( 20, $query->get( 'limit' ) );
	}
}
