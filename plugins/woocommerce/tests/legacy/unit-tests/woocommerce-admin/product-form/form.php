<?php
/**
 * Class for testing the Form class.
 *
 * @package WooCommerce\Internal\Admin\ProductForm
 */

use Automattic\WooCommerce\Internal\Admin\ProductForm\Form;

/**
 * class WC_Admin_Tests_ProductFrom_Field
 */
class WC_Admin_Tests_ProductForm_Form extends WC_Unit_Test_Case {
	/**
	 * @var resource
	 */
	public static $error_log_capture;
	/**
	 * @var string location of error_log output.
	 */
	public static $error_log_config;

	/**
	 * Overwrite the error_log output.
	 */
	public static function setUpBeforeClass(): void {
		self::$error_log_capture = tmpfile();
		// Capture error logs for testing.
		// phpcs:ignore WordPress.PHP.IniSet.Risky
		self::$error_log_config = ini_set( 'error_log', stream_get_meta_data( self::$error_log_capture )['uri'] );
	}

	/**
	 * Set error_log output back to default.
	 */
	public static function tearDownAfterClass(): void {
		// Set error log back to default.
		// phpcs:ignore WordPress.PHP.IniSet.Risky
		ini_set( 'error_log', self::$error_log_config );
	}

	/**
	 * Test add_field with missing keys.
	 */
	public function test_add_field_with_missing_argument() {
		Form::add_field( 'id', 'woocommerce', array() );

		$output = stream_get_contents( self::$error_log_capture );
		$this->assertContains( 'You are missing required arguments of WooCommerce ProductForm Field: name, type, section', $output );
	}

	/**
	 * Test add_field duplicate field id.
	 */
	public function test_add_field_duplicate_field_id() {
		Form::add_field(
			'id',
			'woocommerce',
			array(
				'name'     => 'name',
				'type'     => 'text',
				'section' => 'product_details',
			)
		);

		Form::add_field(
			'id',
			'woocommerce',
			array(
				'name'     => 'name',
				'type'     => 'text',
				'section' => 'product_details',
			)
		);
		$output = stream_get_contents( self::$error_log_capture );
		$this->assertContains( 'You have attempted to register a duplicate form field with WooCommerce Form: `id`', $output );
	}

	/**
	 * Test that get_fields.
	 */
	public function test_get_fields() {
		Form::add_field(
			'id',
			'woocommerce',
			array(
				'name'     => 'name',
				'type'     => 'text',
				'section' => 'product_details',
			)
		);

		Form::add_field(
			'id2',
			'woocommerce',
			array(
				'name'     => 'name',
				'type'     => 'textarea',
				'section' => 'product_details',
			)
		);

		$fields = Form::get_fields();
		$this->assertEquals( 2, count( $fields ) );
		$this->assertEquals( 'text', $fields[0]->type );
		$this->assertEquals( 'textarea', $fields[1]->type );
	}

	/**
	 * Test that get_fields.
	 */
	public function test_get_fields_sort_default() {
		Form::add_field(
			'id',
			'woocommerce',
			array(
				'name'     => 'id',
				'type'     => 'text',
				'section' => 'product_details',
			)
		);

		Form::add_field(
			'id2',
			'woocommerce',
			array(
				'name'     => 'id2',
				'type'     => 'textarea',
				'section' => 'product_details',
			)
		);

		Form::add_field(
			'first',
			'woocommerce',
			array(
				'order'    => 1,
				'name'     => 'first',
				'type'     => 'textarea',
				'section' => 'product_details',
			)
		);

		$fields = Form::get_fields();
		$this->assertEquals( 3, count( $fields ) );
		$this->assertEquals( 'first', $fields[0]->get_id() );
		$this->assertEquals( 'id', $fields[1]->get_id() );
		$this->assertEquals( 'id2', $fields[2]->get_id() );
	}
}

