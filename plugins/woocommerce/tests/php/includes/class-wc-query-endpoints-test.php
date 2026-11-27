<?php
declare( strict_types = 1 );

/**
 * Tests for endpoint request validation through WordPress routing.
 */
class WC_Query_Endpoints_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Query
	 */
	private $sut;

	/**
	 * Rewrite configuration to restore after each test.
	 *
	 * @var WP_Rewrite
	 */
	private $original_rewrite;

	/**
	 * Endpoint validation callback.
	 *
	 * @var Closure
	 */
	private $validator;

	/**
	 * Last HTTP response status.
	 *
	 * @var int
	 */
	private $status_code = 0;

	/**
	 * Register a test endpoint and create its host page.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_rewrite = clone $GLOBALS['wp_rewrite'];
		$this->sut              = WC()->query;
		$account_page_id        = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'my-account',
			)
		);

		update_option( 'woocommerce_myaccount_page_id', $account_page_id );
		add_filter(
			'woocommerce_get_query_vars',
			static function ( $vars ) {
				$vars['test-endpoint'] = 'test-route';
				return $vars;
			}
		);
		$this->validator = static function ( $is_valid, $value ) {
			return true === $is_valid && 'valid' === $value;
		};
		add_filter( 'woocommerce_endpoint_test-endpoint_is_valid_request', $this->validator, 10, 2 );
		$this->refresh_endpoint_rewrites();

		add_filter(
			'status_header',
			function ( $header, $code ) {
				$this->status_code = $code;
				return $header;
			},
			10,
			2
		);
	}

	/**
	 * Restore the in-memory rewrite configuration, which database rollback does not reset.
	 */
	public function tearDown(): void {
		$GLOBALS['wp_rewrite'] = $this->original_rewrite; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the rewrite object changed by these tests.
		parent::tearDown();
	}

	/**
	 * @testdox Endpoint validators accept valid requests and reject invalid paths despite query-string overrides.
	 *
	 * @testWith ["/my-account/test-route/valid/", false]
	 *           ["/my-account/test-route/invalid/", true]
	 *           ["/my-account/test-route/invalid/?test-route=valid&orders=1", true]
	 *           ["/my-account/", false]
	 *
	 * @param string $path      Request path and optional query string.
	 * @param bool   $not_found Whether the URL must return 404.
	 */
	public function test_endpoint_validation( string $path, bool $not_found ): void {
		$url = home_url( $path );
		$this->go_to( $url );

		$this->assertSame( $not_found ? 404 : 200, $this->status_code, 'The response status should match the endpoint path validity.' );
		$this->assertSame( $not_found, is_404(), 'The main query should use the 404 template only for rejected paths.' );

		if ( $not_found ) {
			$this->assertSame( '', $this->sut->get_current_endpoint(), 'Rejected requests must not dispatch any endpoint action.' );
			$this->assertNull( redirect_canonical( $url, false ), 'Rejected paths must not redirect to a successful page.' );
		}
	}

	/**
	 * @testdox Endpoint validators also apply to plain permalinks.
	 *
	 * @testWith ["valid", false]
	 *           ["invalid", true]
	 *
	 * @param string $value     Endpoint query-string value.
	 * @param bool   $not_found Whether the URL must return 404.
	 */
	public function test_plain_permalink_values( string $value, bool $not_found ): void {
		$this->refresh_endpoint_rewrites( '' );
		$url = home_url( '/?page_id=' . wc_get_page_id( 'myaccount' ) . '&test-route=' . $value );

		$this->go_to( $url );

		$this->assertSame( $not_found ? 404 : 200, $this->status_code, 'Plain permalinks should follow the same endpoint value rules.' );
		$this->assertSame( $not_found, is_404(), 'Invalid query-string values should produce a real 404.' );
		if ( $not_found ) {
			$this->assertNull( redirect_canonical( $url, false ), 'A page_id parameter must not redirect a rejected request to My Account.' );
		}
	}

	/**
	 * @testdox Endpoints without validators retain their existing parameter handling.
	 *
	 * @testWith ["orders/2/"]
	 *           ["test-route/custom/nested/value/"]
	 *
	 * @param string $path Endpoint path relative to My Account.
	 */
	public function test_unvalidated_endpoints_are_unchanged( string $path ): void {
		remove_filter( 'woocommerce_endpoint_test-endpoint_is_valid_request', $this->validator );

		$this->go_to( home_url( '/my-account/' . $path ) );

		$this->assertSame( 200, $this->status_code, 'Existing core and extension endpoints must remain unchanged unless they opt in.' );
		$this->assertFalse( is_404(), 'Unvalidated endpoints must not become 404s.' );
	}

	/**
	 * Regenerate endpoint rewrites after changing permalink or endpoint settings.
	 *
	 * @param string $structure Permalink structure.
	 */
	private function refresh_endpoint_rewrites( string $structure = '/%postname%/' ): void {
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $structure );
		$this->sut->add_endpoints();
		update_option( 'rewrite_rules', $wp_rewrite->rewrite_rules() );
	}
}
