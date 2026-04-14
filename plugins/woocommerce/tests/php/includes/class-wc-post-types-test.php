<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Post_Types.
 */
class WC_Post_Types_Test extends \WC_Unit_Test_Case {

	/**
	 * Stored option values to restore after each test.
	 *
	 * @var array<string, mixed>
	 */
	private $original_options = array();

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_options = array(
			'rewrite_rules'                         => get_option( 'rewrite_rules', '__missing__' ),
			'current_theme_supports_woocommerce'    => get_option( 'current_theme_supports_woocommerce', '__missing__' ),
			'woocommerce_queue_flush_rewrite_rules' => get_option( 'woocommerce_queue_flush_rewrite_rules', '__missing__' ),
			$this->get_verification_option_name()   => get_option( $this->get_verification_option_name(), '__missing__' ),
		);
	}

	/**
	 * Tear down each test.
	 */
	public function tearDown(): void {
		foreach ( $this->original_options as $option_name => $value ) {
			if ( '__missing__' === $value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $value );
			}
		}

		parent::tearDown();
	}

	/**
	 * @testdox maybe_queue_flush_rewrite_rules queues a flush and verification when theme support changes.
	 */
	public function test_maybe_queue_flush_rewrite_rules_queues_flush_and_verification_when_theme_support_changes(): void {
		update_option( 'current_theme_supports_woocommerce', 'no' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );

		$this->invoke_static_method(
			'maybe_queue_flush_rewrite_rules',
			array( 'yes', $this->get_shop_archive() )
		);

		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
		$this->assertSame( 'yes', get_option( $this->get_verification_option_name() ) );
	}

	/**
	 * @testdox maybe_queue_flush_rewrite_rules repairs missing product archive rules only when verification is queued.
	 */
	public function test_maybe_queue_flush_rewrite_rules_repairs_missing_product_archive_rules_when_verification_is_queued(): void {
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		update_option( $this->get_verification_option_name(), 'yes' );
		update_option( 'rewrite_rules', array() );

		$this->invoke_static_method(
			'maybe_queue_flush_rewrite_rules',
			array( 'yes', $this->get_shop_archive() )
		);

		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
		$this->assertSame( 'yes', get_option( $this->get_verification_option_name() ) );
	}

	/**
	 * @testdox maybe_queue_flush_rewrite_rules skips archive verification when no retry is queued.
	 */
	public function test_maybe_queue_flush_rewrite_rules_skips_archive_verification_when_no_retry_is_queued(): void {
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		update_option( 'rewrite_rules', array() );

		$this->invoke_static_method(
			'maybe_queue_flush_rewrite_rules',
			array( 'yes', $this->get_shop_archive() )
		);

		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
		$this->assertFalse( get_option( $this->get_verification_option_name(), false ) );
	}

	/**
	 * @testdox maybe_queue_flush_rewrite_rules repairs stale product archive rules when the shop archive slug changes and verification is queued.
	 */
	public function test_maybe_queue_flush_rewrite_rules_repairs_stale_product_archive_rules_when_verification_is_queued(): void {
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		update_option( $this->get_verification_option_name(), 'yes' );
		update_option(
			'rewrite_rules',
			$this->invoke_static_method(
				'get_expected_product_archive_rewrite_rules',
				array( 'outdated-shop' )
			)
		);

		$this->invoke_static_method(
			'maybe_queue_flush_rewrite_rules',
			array( 'yes', $this->get_shop_archive() )
		);

		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
		$this->assertSame( 'yes', get_option( $this->get_verification_option_name() ) );
	}

	/**
	 * @testdox maybe_queue_flush_rewrite_rules repairs stale product archive rules when the archive is disabled and verification is queued.
	 */
	public function test_maybe_queue_flush_rewrite_rules_repairs_stale_product_archive_rules_when_archive_is_disabled_and_verification_is_queued(): void {
		update_option( 'current_theme_supports_woocommerce', 'no' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		update_option( $this->get_verification_option_name(), 'yes' );
		update_option(
			'rewrite_rules',
			$this->invoke_static_method(
				'get_expected_product_archive_rewrite_rules',
				array( $this->get_shop_archive() )
			)
		);

		$this->invoke_static_method(
			'maybe_queue_flush_rewrite_rules',
			array( 'no', false )
		);

		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
		$this->assertSame( 'yes', get_option( $this->get_verification_option_name() ) );
	}

	/**
	 * @testdox maybe_queue_flush_rewrite_rules clears verification when product archive rules already match.
	 */
	public function test_maybe_queue_flush_rewrite_rules_clears_verification_when_product_archive_rules_match(): void {
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		update_option( $this->get_verification_option_name(), 'yes' );
		update_option(
			'rewrite_rules',
			$this->invoke_static_method(
				'get_expected_product_archive_rewrite_rules',
				array( $this->get_shop_archive() )
			)
		);

		$this->invoke_static_method(
			'maybe_queue_flush_rewrite_rules',
			array( 'yes', $this->get_shop_archive() )
		);

		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
		$this->assertFalse( get_option( $this->get_verification_option_name(), false ) );
	}

	/**
	 * @testdox maybe_queue_flush_rewrite_rules ignores extra custom product archive rules when the expected rules are present and verification is queued.
	 */
	public function test_maybe_queue_flush_rewrite_rules_ignores_extra_custom_product_archive_rules_when_verification_is_queued(): void {
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		update_option( $this->get_verification_option_name(), 'yes' );

		$rewrite_rules                              = $this->invoke_static_method(
			'get_expected_product_archive_rewrite_rules',
			array( $this->get_shop_archive() )
		);
		$rewrite_rules['custom-product-archive/?$'] = 'index.php?post_type=product&custom_archive=1';

		update_option( 'rewrite_rules', $rewrite_rules );

		$this->invoke_static_method(
			'maybe_queue_flush_rewrite_rules',
			array( 'yes', $this->get_shop_archive() )
		);

		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
		$this->assertFalse( get_option( $this->get_verification_option_name(), false ) );
	}

	/**
	 * Invoke a private static method.
	 *
	 * @param string $method_name Method name to call.
	 * @param array  $parameters Array of parameters to pass to method.
	 * @return mixed
	 */
	private function invoke_static_method( string $method_name, array $parameters = array() ) {
		$reflection = new \ReflectionMethod( WC_Post_Types::class, $method_name );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( null, $parameters );
	}

	/**
	 * Get the current shop archive slug.
	 *
	 * @return string
	 */
	private function get_shop_archive(): string {
		$shop_page_id = wc_get_page_id( 'shop' );

		if ( ! $shop_page_id || ! get_post( $shop_page_id ) ) {
			return 'shop';
		}

		$shop_page_uri = get_page_uri( $shop_page_id );

		if ( false === $shop_page_uri ) {
			return 'shop';
		}

		return urldecode( $shop_page_uri );
	}

	/**
	 * Get the verification option name used by WC_Post_Types.
	 *
	 * @return string
	 */
	private function get_verification_option_name(): string {
		return 'woocommerce_verify_product_archive_rewrite_rules';
	}
}
