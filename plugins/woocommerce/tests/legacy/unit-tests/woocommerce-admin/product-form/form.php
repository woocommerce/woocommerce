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
	 * Test add_field with missing keys.
	 */
	public function test_add_field_with_missing_argument() {
		$field = Form::add_field( 'id', 'woocommerce', array() );

		$this->assertInstanceOf( 'WP_Error', $field );
		$this->assertContains( 'You are missing required arguments of WooCommerce ProductForm Field: name, type, label, section', $field->get_error_message() );
	}

	/**
	 * Test add_field duplicate field id.
	 */
	public function test_add_field_duplicate_field_id() {
		Form::add_field(
			'id',
			'woocommerce',
			array(
				'label'   => 'label',
				'name'    => 'name',
				'type'    => 'text',
				'section' => 'product_details',
			)
		);

		$field_duplicate = Form::add_field(
			'id',
			'woocommerce',
			array(
				'label'   => 'label',
				'name'    => 'name',
				'type'    => 'text',
				'section' => 'product_details',
			)
		);
		$this->assertInstanceOf( 'WP_Error', $field_duplicate );
		$this->assertContains( 'You have attempted to register a duplicate form field with WooCommerce Form: `id`', $field_duplicate->get_error_message() );
	}

	/**
	 * Test that get_fields.
	 */
	public function test_get_fields() {
		Form::add_field(
			'id',
			'woocommerce',
			array(
				'label'   => 'label',
				'name'    => 'name',
				'type'    => 'text',
				'section' => 'product_details',
			)
		);

		Form::add_field(
			'id2',
			'woocommerce',
			array(
				'label'   => 'label',
				'name'    => 'name',
				'type'    => 'textarea',
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
				'label'   => 'label',
				'name'    => 'id',
				'type'    => 'text',
				'section' => 'product_details',
			)
		);

		Form::add_field(
			'id2',
			'woocommerce',
			array(
				'label'   => 'label',
				'name'    => 'id2',
				'type'    => 'textarea',
				'section' => 'product_details',
			)
		);

		Form::add_field(
			'first',
			'woocommerce',
			array(
				'label'   => 'label',
				'order'   => 1,
				'name'    => 'first',
				'type'    => 'textarea',
				'section' => 'product_details',
			)
		);

		$fields = Form::get_fields();
		$this->assertEquals( 3, count( $fields ) );
		$this->assertEquals( 'first', $fields[0]->get_id() );
		$this->assertEquals( 'id', $fields[1]->get_id() );
		$this->assertEquals( 'id2', $fields[2]->get_id() );
	}

	/**
	 * Test that get_cards.
	 */
	public function test_get_cards_sort_default() {
		Form::add_card(
			'id',
			'woocommerce'
		);

		Form::add_card(
			'id2',
			'woocommerce'
		);

		Form::add_card(
			'first',
			'woocommerce',
			array(
				'order' => 1,
			)
		);

		$cards = Form::get_cards();
		$this->assertEquals( 3, count( $cards ) );
		$this->assertEquals( 'first', $cards[0]->get_id() );
		$this->assertEquals( 'id', $cards[1]->get_id() );
		$this->assertEquals( 'id2', $cards[2]->get_id() );
	}

	/**
	 * Test that get_sections.
	 */
	public function test_get_sections_sort_default() {
		Form::add_section(
			'id',
			'woocommerce',
			array()
		);

		Form::add_section(
			'id2',
			'woocommerce',
			array(
				'title' => 'title',
			)
		);

		Form::add_section(
			'first',
			'woocommerce',
			array(
				'order' => 1,
				'title' => 'title',
			)
		);

		$sections = Form::get_sections();
		$this->assertEquals( 2, count( $sections ) );
		$this->assertEquals( 'first', $sections[0]->get_id() );
		$this->assertEquals( 'id2', $sections[1]->get_id() );
	}
}

