<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Post_Types.
 */
class WC_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * Original active theme stylesheet.
	 *
	 * @var string
	 */
	private $original_theme;

	/**
	 * Original WooCommerce theme support arguments.
	 *
	 * @var array|bool
	 */
	private $original_theme_support;

	/**
	 * Original installing state.
	 *
	 * @var bool
	 */
	private $original_installing;

	/**
	 * Original in-memory rewrite rules.
	 *
	 * @var array|null
	 */
	private $original_rewrite_rules;

	/**
	 * Original permalink structure.
	 *
	 * @var string
	 */
	private $original_permalink_structure;

	/**
	 * Original option values.
	 *
	 * @var array<string, array{exists: bool, value: mixed}>
	 */
	private $original_options;

	/**
	 * Whether the queued flush callback was initially registered.
	 *
	 * @var bool
	 */
	private $flush_hook_was_registered;

	/**
	 * Set up the test state.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rewrite;

		$this->original_theme               = get_stylesheet();
		$this->original_theme_support       = get_theme_support( 'woocommerce' );
		$this->original_installing          = wp_installing();
		$this->original_rewrite_rules       = $wp_rewrite->rules;
		$this->original_permalink_structure = $wp_rewrite->permalink_structure;
		$this->original_options             = array();
		$missing_option                     = new stdClass();

		foreach ( array( 'current_theme_supports_woocommerce', 'woocommerce_queue_flush_rewrite_rules', 'rewrite_rules', 'permalink_structure' ) as $option_name ) {
			$value                                  = get_option( $option_name, $missing_option );
			$this->original_options[ $option_name ] = array(
				'exists' => $missing_option !== $value,
				'value'  => $value,
			);
		}

		$this->flush_hook_was_registered = false !== has_action( 'woocommerce_after_register_post_type', array( 'WC_Post_Types', 'maybe_flush_rewrite_rules' ) );

		remove_action( 'woocommerce_after_register_post_type', array( 'WC_Post_Types', 'maybe_flush_rewrite_rules' ) );
		add_filter( 'flush_rewrite_rules_hard', '__return_false' );
	}

	/**
	 * Restore global test state.
	 */
	public function tearDown(): void {
		global $_wp_theme_features, $wp_rewrite;

		wp_installing( false );

		if ( get_stylesheet() !== $this->original_theme ) {
			switch_theme( $this->original_theme );
		}

		if ( false !== $this->original_theme_support ) {
			$_wp_theme_features['woocommerce'] = $this->original_theme_support;
		} else {
			unset( $_wp_theme_features['woocommerce'] );
		}

		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();

		$wp_rewrite->set_permalink_structure( $this->original_permalink_structure );

		foreach ( $this->original_options as $option_name => $option ) {
			if ( ! $option['exists'] ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $option['value'] );
			}
		}
		$wp_rewrite->rules = $this->original_rewrite_rules;

		if ( $this->flush_hook_was_registered ) {
			add_action( 'woocommerce_after_register_post_type', array( 'WC_Post_Types', 'maybe_flush_rewrite_rules' ) );
		}
		remove_filter( 'flush_rewrite_rules_hard', '__return_false' );
		wp_installing( $this->original_installing );

		parent::tearDown();
	}

	/**
	 * @testdox Installing mode preserves stored theme support while continuing post type registration and hooks.
	 */
	public function test_installing_mode_preserves_theme_support_state_during_registration(): void {
		$sentinel_rules = array( '^rewrite-state-verification/?$' => 'index.php?rewrite-state-verification=1' );
		$this->prepare_unsupported_classic_theme();
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		update_option( 'rewrite_rules', $sentinel_rules );

		$before_hook_count = 0;
		$after_hook_count  = 0;
		$before_hook       = static function () use ( &$before_hook_count ): void {
			++$before_hook_count;
		};
		$after_hook        = static function () use ( &$after_hook_count ): void {
			++$after_hook_count;
		};

		$this->assertTrue( $this->flush_hook_was_registered, 'The queued flush callback should be registered on the post-type lifecycle hook.' );
		add_action( 'woocommerce_after_register_post_type', array( 'WC_Post_Types', 'maybe_flush_rewrite_rules' ) );
		add_action( 'woocommerce_register_post_type', $before_hook );
		add_action( 'woocommerce_after_register_post_type', $after_hook );
		wp_installing( true );

		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();

		remove_action( 'woocommerce_register_post_type', $before_hook );
		remove_action( 'woocommerce_after_register_post_type', $after_hook );

		$this->assertTrue( post_type_exists( 'product' ), 'Product registration should continue while WordPress is installing.' );
		$this->assertSame( 1, $before_hook_count, 'The pre-registration hook should still fire.' );
		$this->assertSame( 1, $after_hook_count, 'The post-registration hook should still fire.' );
		$this->assertSame( 'yes', get_option( 'current_theme_supports_woocommerce' ), 'Installing mode should not persist request-local missing theme support.' );
		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'Installing mode should preserve the queued flush for a normal request.' );
		$this->assertSame( $sentinel_rules, get_option( 'rewrite_rules' ), 'Installing mode should not persist a replacement rewrite rule set.' );
	}

	/**
	 * @testdox Normal requests persist legitimate theme support changes and queue a rewrite flush.
	 */
	public function test_normal_request_tracks_theme_support_changes_during_registration(): void {
		$this->prepare_unsupported_classic_theme();
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );
		wp_installing( false );

		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();

		$this->assertSame( 'no', get_option( 'current_theme_supports_woocommerce' ), 'Normal requests should persist a legitimate theme support change.' );
		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'Normal requests should queue a rewrite flush after theme support changes.' );
	}

	/**
	 * @testdox Normal requests consume an existing rewrite queue and persist current rules.
	 */
	public function test_normal_request_consumes_queued_rewrite_flush(): void {
		global $wp_rewrite;

		$sentinel_rules = array( '^rewrite-state-verification/?$' => 'index.php?rewrite-state-verification=1' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();
		update_option( 'rewrite_rules', $sentinel_rules );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		wp_installing( false );

		WC_Post_Types::maybe_flush_rewrite_rules();

		$rules         = (array) get_option( 'rewrite_rules' );
		$product_rules = array_filter(
			$rules,
			static function ( $query ): bool {
				return str_contains( $query, 'index.php?product=' );
			}
		);

		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'Normal requests should consume the queued flush.' );
		$this->assertNotEmpty( $rules, 'Normal requests should persist regenerated rewrite rules.' );
		$this->assertNotEmpty( $product_rules, 'Regenerated rules should include WooCommerce product rewrites.' );
	}

	/**
	 * Switch to a classic theme without runtime WooCommerce support.
	 */
	private function prepare_unsupported_classic_theme(): void {
		switch_theme( 'storefront' );
		remove_theme_support( 'woocommerce' );

		$this->assertFalse( wp_is_block_theme(), 'The test requires a classic theme.' );
		$this->assertFalse( wc_current_theme_supports_woocommerce_or_fse(), 'The test requires missing runtime WooCommerce theme support.' );
	}
}
