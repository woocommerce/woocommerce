<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Admin_Post_Types class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * WC_Admin_Post_Types_Test
 */
class WC_Admin_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Post_Types
	 */
	private WC_Admin_Post_Types $sut;

	/**
	 * Original WordPress hooks.
	 *
	 * @var array<string, WP_Hook>
	 */
	private array $wp_filter_backup;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_filter;

		parent::setUp();

		$this->wp_filter_backup = $wp_filter;

		remove_all_actions( 'post_submitbox_misc_actions' );

		$this->sut = new WC_Admin_Post_Types();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_filter;

		$wp_filter = $this->wp_filter_backup;

		parent::tearDown();
	}

	/**
	 * @testdox Catalog visibility renders before extension rows in the publish box.
	 */
	public function test_product_data_visibility_uses_early_publish_box_priority(): void {
		$this->assertSame(
			5,
			has_action( 'post_submitbox_misc_actions', array( $this->sut, 'product_data_visibility' ) ),
			'Catalog visibility should render before extension rows using the default priority.'
		);
	}
}
