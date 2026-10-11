<?php
/**
 * Tests for WC_Admin_Taxonomies.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

require_once WC_ABSPATH . 'includes/admin/class-wc-admin-taxonomies.php';

/**
 * WC_Admin_Taxonomies tests.
 */
class WC_Admin_Taxonomies_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Taxonomies
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = WC_Admin_Taxonomies::get_instance();
		$_POST     = array();
	}

	/**
	 * Restore request and user state.
	 */
	public function tearDown(): void {
		$_POST = array();

		parent::tearDown();
	}

	/**
	 * @testdox Should normalize product category display types before storage.
	 * @dataProvider display_type_provider
	 *
	 * @param mixed  $request_value Request display type.
	 * @param string $expected      Expected stored display type.
	 */
	public function test_save_category_fields_normalizes_display_type( $request_value, string $expected ): void {
		$term_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Display type test',
			)
		);

		$_POST['display_type'] = $request_value;

		$this->sut->save_category_fields( $term_id, '', 'product_cat' );

		$this->assertSame( $expected, get_term_meta( $term_id, 'display_type', true ), 'Display type should be normalized before storage.' );
	}

	/**
	 * Data provider for display type normalization.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function display_type_provider(): array {
		return array(
			'core value'       => array( 'products', 'products' ),
			'slashed value'    => array( 'sub\\categories', 'subcategories' ),
			'extension value'  => array( 'custom-layout', 'custom-layout' ),
			'non-string value' => array( array( 'both' ), '' ),
		);
	}
}
