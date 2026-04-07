<?php
declare( strict_types = 1 );

/**
 * Tests for functions in includes/wc-template-functions.php.
 */
class WC_Template_Functions_Test extends WC_Unit_Test_Case {

	/**
	 * Backup of $_GET superglobal.
	 *
	 * @var array
	 */
	private $original_get;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Restore $_GET after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$_GET = $this->original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		remove_all_filters( 'woocommerce_noindex_filtered_pages' );
	}

	// -------------------------------------------------------------------------
	// wc_has_product_filter_params()
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return false when no filter params are present.
	 */
	public function test_has_product_filter_params_returns_false_with_no_params(): void {
		$_GET = array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->assertFalse( wc_has_product_filter_params() );
	}

	/**
	 * @testdox Should return true when a filter_ prefixed param is present.
	 */
	public function test_has_product_filter_params_returns_true_for_filter_prefix(): void {
		$_GET = array( 'filter_color' => 'blue' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->assertTrue( wc_has_product_filter_params() );
	}

	/**
	 * @testdox Should return true when rating_filter param is present.
	 */
	public function test_has_product_filter_params_returns_true_for_rating_filter(): void {
		$_GET = array( 'rating_filter' => '4' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->assertTrue( wc_has_product_filter_params() );
	}

	/**
	 * @testdox Should return true when min_price param is present.
	 */
	public function test_has_product_filter_params_returns_true_for_min_price(): void {
		$_GET = array( 'min_price' => '10' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->assertTrue( wc_has_product_filter_params() );
	}

	/**
	 * @testdox Should return true when max_price param is present.
	 */
	public function test_has_product_filter_params_returns_true_for_max_price(): void {
		$_GET = array( 'max_price' => '100' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->assertTrue( wc_has_product_filter_params() );
	}

	/**
	 * @testdox Should return false when unrelated query params are present.
	 */
	public function test_has_product_filter_params_returns_false_for_unrelated_params(): void {
		$_GET = array( 'orderby' => 'price', 'paged' => '2', 's' => 'shirt' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->assertFalse( wc_has_product_filter_params() );
	}

	// -------------------------------------------------------------------------
	// wc_page_no_robots() — filtered pages branch
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should add noindex when filter params are present and feature is enabled.
	 */
	public function test_page_no_robots_adds_noindex_for_filtered_pages(): void {
		$_GET = array( 'filter_color' => 'blue' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = wc_page_no_robots( array() );

		$this->assertArrayHasKey( 'noindex', $result, 'noindex directive should be added for filtered pages' );
		$this->assertTrue( $result['noindex'], 'noindex directive should be true for filtered pages' );
	}

	/**
	 * @testdox Should not add noindex when filter params are present but feature is disabled.
	 */
	public function test_page_no_robots_respects_disabled_filter(): void {
		$_GET = array( 'filter_color' => 'blue' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		add_filter( 'woocommerce_noindex_filtered_pages', '__return_false' );

		$result = wc_page_no_robots( array() );

		$this->assertArrayNotHasKey( 'noindex', $result, 'noindex should not be added when feature is disabled' );
	}

	/**
	 * @testdox Should not add noindex when no filter params are present.
	 */
	public function test_page_no_robots_does_not_add_noindex_without_filter_params(): void {
		$_GET = array( 'orderby' => 'price' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = wc_page_no_robots( array() );

		$this->assertArrayNotHasKey( 'noindex', $result, 'noindex should not be added when no filter params are present' );
	}

	/**
	 * @testdox Should add noindex when min_price param is present.
	 */
	public function test_page_no_robots_adds_noindex_for_min_price(): void {
		$_GET = array( 'min_price' => '10' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = wc_page_no_robots( array() );

		$this->assertArrayHasKey( 'noindex', $result );
	}

	/**
	 * @testdox Should add noindex when max_price param is present.
	 */
	public function test_page_no_robots_adds_noindex_for_max_price(): void {
		$_GET = array( 'max_price' => '100' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = wc_page_no_robots( array() );

		$this->assertArrayHasKey( 'noindex', $result );
	}

	/**
	 * @testdox Should add noindex when rating_filter param is present.
	 */
	public function test_page_no_robots_adds_noindex_for_rating_filter(): void {
		$_GET = array( 'rating_filter' => '4' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = wc_page_no_robots( array() );

		$this->assertArrayHasKey( 'noindex', $result );
	}
}
