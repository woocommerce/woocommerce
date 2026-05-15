<?php
declare( strict_types=1 );

/**
 * Tests for the WC_Product_Cat_Dropdown_Walker class.
 */
class WC_Product_Cat_Dropdown_Walker_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Product_Cat_Dropdown_Walker
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// The walker is lazy-loaded by core; require it explicitly for the unit test.
		if ( ! class_exists( 'WC_Product_Cat_Dropdown_Walker' ) ) {
			require_once WC()->plugin_path() . '/includes/walkers/class-wc-product-cat-dropdown-walker.php';
		}
		$this->sut = new WC_Product_Cat_Dropdown_Walker();
	}

	/**
	 * @testdox Should emit the canonical boolean `selected` attribute when the category is selected.
	 */
	public function test_start_el_emits_boolean_selected_attribute_when_selected(): void {
		$category    = (object) array(
			'term_id' => 42,
			'slug'    => 'apparel',
			'name'    => 'Apparel',
			'count'   => 3,
		);
		$output      = '';
		$args        = array(
			'selected'     => 'apparel',
			'value'        => 'slug',
			'hierarchical' => false,
			'show_count'   => false,
		);

		$this->sut->start_el( $output, $category, 0, $args, 0 );

		$this->assertStringNotContainsString(
			'selected="selected"',
			$output,
			'Walker must not emit the non-boolean `selected="selected"` form.'
		);
		$this->assertStringContainsString(
			' selected>',
			$output,
			'Walker should emit the boolean `selected` attribute form.'
		);
	}

	/**
	 * @testdox Should not emit any selected attribute when the category is not selected.
	 */
	public function test_start_el_does_not_emit_selected_when_not_selected(): void {
		$category = (object) array(
			'term_id' => 43,
			'slug'    => 'books',
			'name'    => 'Books',
			'count'   => 0,
		);
		$output   = '';
		$args     = array(
			'selected'     => 'apparel',
			'value'        => 'slug',
			'hierarchical' => false,
			'show_count'   => false,
		);

		$this->sut->start_el( $output, $category, 0, $args, 0 );

		$this->assertStringNotContainsString(
			'selected',
			$output,
			'Unselected category options must not include any selected attribute.'
		);
	}
}
