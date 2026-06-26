<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\TaxSettingsRecommendations;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the TaxSettingsRecommendations class.
 */
class TaxSettingsRecommendationsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TaxSettingsRecommendations
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TaxSettingsRecommendations();
		$this->sut->init();

		// Routes must register on rest_api_init; firing it runs init()'s callback.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( TaxSettingsRecommendations::DISMISSED_OPTION_NAME );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Dismissing persists the option and returns a 200 response.
	 */
	public function test_dismiss_persists_option_and_returns_200(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$response = rest_do_request( new WP_REST_Request( 'POST', '/wc-admin/tax/recommendations/dismiss' ) );

		$this->assertSame( 200, $response->get_status(), 'A valid dismissal should return HTTP 200.' );
		$this->assertSame(
			'yes',
			get_option( TaxSettingsRecommendations::DISMISSED_OPTION_NAME ),
			'The dismissal option should be persisted as "yes".'
		);
	}

	/**
	 * @testdox Dismissing twice still returns a 200 response.
	 */
	public function test_dismiss_twice_returns_200(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		update_option( TaxSettingsRecommendations::DISMISSED_OPTION_NAME, 'yes' );

		$response = rest_do_request( new WP_REST_Request( 'POST', '/wc-admin/tax/recommendations/dismiss' ) );

		$this->assertSame(
			200,
			$response->get_status(),
			'Re-dismissing an already-dismissed card should succeed, not error.'
		);
	}

	/**
	 * @testdox Users without manage_woocommerce cannot dismiss.
	 */
	public function test_dismiss_denied_without_capability(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		$response = rest_do_request( new WP_REST_Request( 'POST', '/wc-admin/tax/recommendations/dismiss' ) );

		$this->assertSame(
			rest_authorization_required_code(),
			$response->get_status(),
			'A user lacking manage_woocommerce should be denied.'
		);
		$this->assertNotSame(
			'yes',
			get_option( TaxSettingsRecommendations::DISMISSED_OPTION_NAME ),
			'A denied request must not persist the dismissal.'
		);
	}

	/**
	 * @testdox Preload maps the stored option to the taxRecommendationsHidden boolean.
	 */
	public function test_preload_settings_maps_option_to_boolean(): void {
		$this->assertFalse(
			$this->sut->preload_settings( array() )['taxRecommendationsHidden'],
			'An unset option should preload as false.'
		);

		update_option( TaxSettingsRecommendations::DISMISSED_OPTION_NAME, 'yes' );

		$this->assertTrue(
			$this->sut->preload_settings( array() )['taxRecommendationsHidden'],
			'A dismissed card should preload as true.'
		);
	}

	/**
	 * @testdox Preload leaves non-array input untouched.
	 */
	public function test_preload_settings_ignores_non_array(): void {
		$this->assertSame(
			'unchanged',
			$this->sut->preload_settings( 'unchanged' ),
			'Non-array settings should be returned as-is.'
		);
	}
}
