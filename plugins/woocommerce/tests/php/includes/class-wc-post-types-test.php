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
	 * Whether the original theme supported WooCommerce.
	 *
	 * @var bool
	 */
	private $original_theme_support;

	/**
	 * Original option values.
	 *
	 * @var array<string, mixed>
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

		$this->original_theme         = get_stylesheet();
		$this->original_theme_support = current_theme_supports( 'woocommerce' );
		$this->original_options       = array(
			'current_theme_supports_woocommerce'    => get_option( 'current_theme_supports_woocommerce', false ),
			'woocommerce_queue_flush_rewrite_rules' => get_option( 'woocommerce_queue_flush_rewrite_rules', false ),
			'rewrite_rules'                         => get_option( 'rewrite_rules', false ),
		);

		$this->flush_hook_was_registered = false !== has_action( 'woocommerce_after_register_post_type', array( 'WC_Post_Types', 'maybe_flush_rewrite_rules' ) );

		remove_action( 'woocommerce_after_register_post_type', array( 'WC_Post_Types', 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Restore global test state.
	 */
	public function tearDown(): void {
		wp_installing( false );

		if ( get_stylesheet() !== $this->original_theme ) {
			switch_theme( $this->original_theme );
		}

		if ( $this->original_theme_support ) {
			add_theme_support( 'woocommerce' );
		} else {
			remove_theme_support( 'woocommerce' );
		}

		if ( ! post_type_exists( 'product' ) ) {
			WC_Post_Types::register_post_types();
		}

		foreach ( $this->original_options as $option_name => $option_value ) {
			if ( false === $option_value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $option_value );
			}
		}

		if ( $this->flush_hook_was_registered ) {
			add_action( 'woocommerce_after_register_post_type', array( 'WC_Post_Types', 'maybe_flush_rewrite_rules' ) );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Installing mode preserves stored theme support while continuing post type registration and hooks.
	 */
	public function test_installing_mode_preserves_theme_support_state_during_registration(): void {
		$this->prepare_unsupported_classic_theme();
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );

		$before_hook_count = 0;
		$after_hook_count  = 0;
		$before_hook       = static function () use ( &$before_hook_count ): void {
			++$before_hook_count;
		};
		$after_hook        = static function () use ( &$after_hook_count ): void {
			++$after_hook_count;
		};

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
		$this->assertFalse( get_option( 'woocommerce_queue_flush_rewrite_rules', false ), 'Installing mode should not queue a theme-support rewrite flush.' );
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
	 * @testdox Installing mode preserves an existing rewrite queue and persisted rules.
	 */
	public function test_installing_mode_preserves_queued_rewrite_flush(): void {
		$sentinel_rules = array( '^rewrite-state-verification/?$' => 'index.php?rewrite-state-verification=1' );
		update_option( 'rewrite_rules', $sentinel_rules );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		wp_installing( true );

		WC_Post_Types::maybe_flush_rewrite_rules();

		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'Installing mode should preserve the queued flush for a normal request.' );
		$this->assertSame( $sentinel_rules, get_option( 'rewrite_rules' ), 'Installing mode should not persist a replacement rewrite rule set.' );
	}

	/**
	 * @testdox Normal requests consume an existing rewrite queue and persist complete rules.
	 */
	public function test_normal_request_consumes_queued_rewrite_flush(): void {
		$sentinel_rules = array( '^rewrite-state-verification/?$' => 'index.php?rewrite-state-verification=1' );
		update_option( 'rewrite_rules', $sentinel_rules );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		wp_installing( false );

		WC_Post_Types::maybe_flush_rewrite_rules();

		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'Normal requests should consume the queued flush.' );
		$this->assertNotSame( $sentinel_rules, get_option( 'rewrite_rules' ), 'Normal requests should persist regenerated rewrite rules.' );
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
