<?php
/**
 * Class for testing the Form class.
 *
 * @package WooCommerce\Internal\Admin\ProductForm
 */

use Automattic\WooCommerce\Internal\Admin\ProductForm\FormFactory as Form;

/**
 * class WC_Admin_Tests_ProductFrom_Field
 */
class WC_Admin_Tests_ProductForm_Form_Factory extends WC_Unit_Test_Case {
	/**
	 * Test add_field with missing keys.
	 */
	public function test_add_field_with_missing_argument() {
		$field = Form::add_field( 'id', 'woocommerce', array() );

		$this->assertInstanceOf( 'WP_Error', $field );
		$this->assertContains( 'You are missing required arguments of WooCommerce ProductForm Field: type, section, properties.name, properties.label', $field->get_error_message() );
	}

	/**
	 * Test add_field duplicate field id.
	 */
	public function test_add_field_duplicate_field_id() {
		Form::add_field(
			'id',
			'woocommerce',
			array(
				'type'       => 'text',
				'section'    => 'product_details',
				'properties' => array(
					'label' => 'label',
					'name'  => 'name',
				),
			)
		);

		$field_duplicate = Form::add_field(
			'id',
			'woocommerce',
			array(
				'type'       => 'text',
				'section'    => 'product_details',
				'properties' => array(
					'label' => 'label',
					'name'  => 'name',
				),
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
				'type'       => 'text',
				'section'    => 'product_details',
				'properties' => array(
					'label' => 'label',
					'name'  => 'name',
				),
			)
		);

		Form::add_field(
			'id2',
			'woocommerce',
			array(
				'type'       => 'textarea',
				'section'    => 'product_details',
				'properties' => array(
					'label' => 'label',
					'name'  => 'name',
				),
			)
		);

		$fields = Form::get_fields();
		$this->assertEquals( 2, count( $fields ) );
		$this->assertEquals( 'text', $fields[0]->get_additional_args()['type'] );
		$this->assertEquals( 'textarea', $fields[1]->get_additional_args()['type'] );
	}

	/**
	 * Test that get_fields.
	 */
	public function test_get_fields_sort_default() {
		Form::add_field(
			'id',
			'woocommerce',
			array(
				'type'       => 'text',
				'section'    => 'product_details',
				'properties' => array(
					'label' => 'label',
					'name'  => 'name',
				),
			)
		);

		Form::add_field(
			'id2',
			'woocommerce',
			array(
				'type'       => 'textarea',
				'section'    => 'product_details',
				'properties' => array(
					'label' => 'label',
					'name'  => 'name',
				),
			)
		);

		Form::add_field(
			'first',
			'woocommerce',
			array(
				'order'      => 1,
				'type'       => 'textarea',
				'section'    => 'product_details',
				'properties' => array(
					'label' => 'label',
					'name'  => 'name',
				),
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
		Form::add_subsection(
			'id',
			'woocommerce'
		);

		Form::add_subsection(
			'id2',
			'woocommerce'
		);

		Form::add_subsection(
			'first',
			'woocommerce',
			array(
				'order' => 1,
			)
		);

		$subsections = Form::get_subsections();
		$this->assertEquals( 3, count( $subsections ) );
		$this->assertEquals( 'first', $subsections[0]->get_id() );
		$this->assertEquals( 'id', $subsections[1]->get_id() );
		$this->assertEquals( 'id2', $subsections[2]->get_id() );
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
	/**
	 * Test that get_tabs.
	 */
	public function test_get_tabs_sort_default() {
		Form::add_tab(
			'id',
			'woocommerce',
			array(
				'name'  => 'tab_name',
				'title' => 'Tab Title',
			)
		);

		Form::add_tab(
			'id2',
			'woocommerce',
			array(
				'name'  => 'tab_name2',
				'title' => 'Tab Title 2',
			)
		);

		$sections = Form::get_tabs();
		$this->assertEquals( 2, count( $sections ) );
	}
}

