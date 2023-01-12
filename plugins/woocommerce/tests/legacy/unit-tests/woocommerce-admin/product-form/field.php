<?php
/**
 * Class for testing the Field class.
 *
 * @package WooCommerce\Internal\Admin\ProductForm
 */

use Automattic\WooCommerce\Internal\Admin\ProductForm\Field;

/**
 * class WC_Admin_Tests_ProductFrom_Field
 */
class WC_Admin_Tests_ProductForm_Field extends WC_Unit_Test_Case {

	/**
	 * Test that get_missing_arguments returns the correct keys.
	 */
	public function test_get_missing_arguments() {
		$missing_args = Field::get_missing_arguments(
			array(
				'section' => 'product_details',
			)
		);

		$this->assertEquals( array( 'type', 'properties.name', 'properties.label' ), $missing_args );
	}
}

