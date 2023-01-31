<?php
/**
 * Class for testing the Field class.
 *
 * @package WooCommerce\Internal\Admin\ProductForm
 */

use Automattic\WooCommerce\Internal\Admin\ProductForm\Field;

/**
 * class WC_Admin_Tests_ProductForm_Field
 */
class WC_Admin_Tests_ProductForm_Field extends WC_Unit_Test_Case {

	/**
	 * Test that instantiating a Field without the required arguments throws an exception.
	 */
	public function test_no_required_arguments() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'You are missing required arguments of WooCommerce ProductForm Field: type, section, properties.name, properties.label' );
		new Field(
			'id',
			'woocommerce',
			array(),
		);
	}
}

