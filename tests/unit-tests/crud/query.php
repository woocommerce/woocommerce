<?php

/**
 * WC_Object_Query tests
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_WC_Object_Query extends WC_Unit_Test_Case {

	function test_default_query() {
		$query = new WC_Mock_WC_Object_Query();

		$this->assertNotEmpty( $query->query_vars );
		$this->assertEquals( '', $query->get( 'parent' ) );
		$this->assertEquals( 'date', $query->get( 'orderby' ) );
	}

	function test_query_with_args() {
		$args = array(
			'per_page' => 15,
			'year' => 2017
		);
		$query = new WC_Mock_WC_Object_Query( $args );

		$this->assertEquals( 15, $query->get( 'per_page' ) );
		$this->assertEquals( 2017, $query->get( 'year' ) );

		$query->set( 'year', 2016 );
		$this->assertEquals( 2016, $query->get( 'year' ) );
	}
}
