<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Addons.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Addons_Test extends WC_Unit_Test_Case {

	/**
	 * Location passed to the last intercepted redirect.
	 *
	 * @var string|null
	 */
	private $redirect_location = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_transient( 'wc_addons_sections' );
		add_filter( 'pre_http_request', fn() => new WP_Error( 'http_request_failed', 'cURL error 35' ) );
		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET['section'] );
		parent::tearDown();
	}

	/**
	 * Intercepts redirects so the tested handler's trailing exit does not run.
	 *
	 * @param string $location Redirect target.
	 * @return void
	 * @throws RuntimeException Always.
	 */
	public function intercept_redirect( string $location ): void {
		$this->redirect_location = $location;
		throw new RuntimeException( 'Redirect intercepted.' );
	}

	/**
	 * Runs the legacy redirect handler for a section and returns the redirect target.
	 *
	 * @param string $section Requested legacy section.
	 * @return string
	 */
	private function get_redirect_for_section( string $section ): string {
		$_GET['section']         = $section; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->redirect_location = null;
		try {
			WC_Admin_Addons::handle_legacy_marketplace_redirects();
		} catch ( RuntimeException $e ) {
			// PHPUnit's converted warnings are RuntimeExceptions too; only swallow the redirect.
			if ( null === $this->redirect_location ) {
				throw $e;
			}
			return $this->redirect_location;
		}
		$this->fail( 'Expected the handler to redirect.' );
	}

	/**
	 * @testdox Legacy redirect falls back to the extensions page when sections can't be loaded.
	 */
	public function test_redirect_falls_back_to_featured_when_sections_fail_to_load(): void {
		$this->assertSame(
			admin_url( 'admin.php?page=wc-admin&path=%2Fextensions' ),
			$this->get_redirect_for_section( 'marketing' ),
			'A failed sections request should send users to the default extensions page.'
		);
	}

	/**
	 * @testdox Legacy redirect ignores malformed filtered sections and keeps valid ones.
	 */
	public function test_redirect_ignores_malformed_sections(): void {
		add_filter(
			'woocommerce_addons_sections',
			fn() => array(
				'not-an-object',
				null,
				(object) array( 'label' => 'No slug' ),
				(object) array( 'slug' => array( 'marketing' ) ),
				(object) array( 'slug' => 'marketing' ),
			)
		);

		$this->assertSame(
			admin_url( 'admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&category=marketing' ),
			$this->get_redirect_for_section( 'marketing' ),
			'The valid section should still resolve to its category.'
		);
	}

	/**
	 * @testdox Legacy redirect keeps known sections and drops unknown ones.
	 */
	public function test_redirect_keeps_known_sections_only(): void {
		add_filter( 'woocommerce_addons_sections', fn() => array( (object) array( 'slug' => 'payment-gateways' ) ) );

		$this->assertSame(
			admin_url( 'admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&category=payment-gateways' ),
			$this->get_redirect_for_section( 'payment-gateways' ),
			'A known section should be kept.'
		);
		$this->assertSame(
			admin_url( 'admin.php?page=wc-admin&path=%2Fextensions' ),
			$this->get_redirect_for_section( 'unknown' ),
			'An unknown section should fall back to the default extensions page.'
		);
	}
}
