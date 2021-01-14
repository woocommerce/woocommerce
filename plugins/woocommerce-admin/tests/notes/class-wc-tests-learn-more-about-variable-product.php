<?php
/**
 * LearnMoreAboutVariableProducts note tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Admin\Notes\LearnMoreAboutVariableProducts;
use \Automattic\WooCommerce\Admin\Notes\Notes;

/**
 * Class WC_Tests_Marketing_Notes
 */
class WC_Tests_Learn_More_About_Variable_Product extends WC_Unit_Test_Case {

	/**
	 * Reset the notes table for each test.
	 */
	public function setUp() {
		parent::setUp();
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}wc_admin_notes" );
	}

	/**
	 * Tests LearnMoreAboutVariableProducts gets created when a products gets published
	 */
	public function test_adding_note_when_product_gets_published() {
		// Given a new product.
		$product = array(
			'post_title'   => 'a product',
			'post_type'    => 'product',
			'post_status'  => 'publish',
			'post_content' => '',
		);

		// When it is published.
		wp_insert_post( $product );

		// Then we should have LearnMoreAboutVariableProducts note.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( LearnMoreAboutVariableProducts::NOTE_NAME );
		$this->assertNotEmpty( $note_ids );
		$this->assertCount( 1, $note_ids );

		$note = Notes::get_note( $note_ids[0] );
		$this->assertEquals( $note->get_name(), LearnMoreAboutVariableProducts::NOTE_NAME );

		// Adding a second product does not create an additional note.
		wp_insert_post( $product );
		$note_ids = $data_store->get_notes_with_name( LearnMoreAboutVariableProducts::NOTE_NAME );
		$this->assertNotEmpty( $note_ids );
		$this->assertCount( 1, $note_ids );
	}

	/**
	 * Test a variable product does not create LearnMoreAboutVariableProducts note
	 */
	public function test_adding_variable_product_does_not_add_note() {
		// Given a variable product.
		$product = $this->create_variable_product();

		// When it gets published.
		wp_publish_post( $product->get_id() );

		// Then a note should not be added.
		$note_ids   = $this->get_note_ids();
		$note_count = count( $note_ids );
		$this->assertEmpty( $note_ids, "{$note_count} notes found." );
	}


	/**
	 * @dataProvider postProvider
	 *
	 * @param string $product product from provider.
	 */
	public function test_adding_draft_product_and_non_product_post_does_not_add_note( $product ) {
		wp_insert_post( $product );

		$note_ids   = $this->get_note_ids();
		$note_count = count( $note_ids );
		$this->assertEmpty( $note_ids, "{$note_count} notes found." );
	}

	/**
	 * Post provider for draft product and non-product post type
	 *
	 * @return array a set of posts.
	 */
	public function postProvider() {
		return array(
			array(
				'post_title'   => 'a product',
				'post_type'    => 'not a product',
				'post_status'  => 'publish',
				'post_content' => '',
			),
			array(
				'post_title'   => 'a product',
				'post_type'    => 'product',
				'post_status'  => 'draft',
				'post_content' => '',
			),
		);
	}

	/**
	 * Return note ids
	 * @return array
	 */
	protected function get_note_ids() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		return $data_store->get_notes_with_name( LearnMoreAboutVariableProducts::NOTE_NAME );
	}

	/**
	 * Create a variable product for testing
	 *
	 * @return WC_Product_Variable
	 */
	protected function create_variable_product() {
		$name      = 'test';
		$product   = new \WC_Product_Variable();
		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( wp_rand( 1, 100 ) );
		$attribute->set_options( array_filter( array( 1, 2 ) ) );
		$attributes[] = $attribute;

		$product->set_props(
			array(
				'name'       => $name,
				'featured'   => wp_rand( 1, 10 ),
				'attributes' => $attributes,
				'status'     => 'draft',
			)
		);
		// Need to save to get an ID for variations.
		$product->save();

		// Create variations, one for each attribute value combination.
		$variation_attributes = wc_list_pluck(
			array_filter(
				$product->get_attributes(),
				'wc_attributes_array_filter_variation'
			),
			'get_slugs'
		);
		$possible_attributes  = array_reverse( wc_array_cartesian( $variation_attributes ) );
		foreach ( $possible_attributes as $possible_attribute ) {
			$variation = new \WC_Product_Variation();
			$variation->set_props(
				array(
					'parent_id'     => $product->get_id(),
					'attributes'    => $possible_attribute,
					'regular_price' => 10,
					'stock_status'  => 'instock',
				)
			);
			$variation->save();
		}
		$data_store = $product->get_data_store();
		$data_store->sort_all_product_variations( $product->get_id() );

		return $product;
	}
}
