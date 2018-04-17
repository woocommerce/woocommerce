<?php
/**
 * Tests for the admin/wc-meta-box-functions.php.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Test wc_selected().
 *
 * @since 3.4.0
 */
class WC_Meta_Box_Functions_Test extends WC_Unit_Test_Case {
	/**
	 * Load the necessary files, as they're not automatically loaded by WooCommerce.
	 */
	public static function setUpBeforeClass() {
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/wc-meta-box-functions.php';
	}

	/**
	 * Test: wc_selected
	 */
	public function test_wc_selected() {
		$test_cases = array(
			// both value and options int.
			array( 0, 0, true ),
			array( 0, 1, false ),
			array( 1, 0, false ),

			// value string, options int.
			array( '0', 0, true ),
			array( '0', 1, false ),
			array( '1', 0, false ),

			// value int, options string.
			array( 0, '0', true ),
			array( 0, '1', false ),
			array( 1, '0', false ),

			// both value and options str.
			array( '0', '0', true ),
			array( '0', '1', false ),
			array( '1', '0', false ),

			// both value and options int.
			array( 0, array( 0, 1, 2 ), true ),
			array( 0, array( 1, 1, 1 ), false ),

			// value string, options int.
			array( '0', array( 0, 1, 2 ), true ),
			array( '0', array( 1, 1, 1 ), false ),

			// value int, options string.
			array( 0, array( '0', '1', '2' ), true ),
			array( 0, array( '1', '1', '1' ), false ),

			// both value and options str.
			array( '0', array( '0', '1', '2' ), true ),
			array( '0', array( '1', '1', '1' ), false ),
		);

		foreach ( $test_cases as $test_case ) {
			list( $value, $options, $result ) = $test_case;
			$actual_result = $result ? " selected='selected'" : '';
			$this->assertEquals( wc_selected( $value, $options ), $actual_result );
		}
	}
}
