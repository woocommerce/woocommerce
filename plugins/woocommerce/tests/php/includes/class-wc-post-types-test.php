<?php
/**
 * Tests for WC_Post_Types.
 *
 * @package WooCommerce\Tests\PostTypes
 */

declare( strict_types = 1 );

/**
 * Tests for WC_Post_Types.
 */
class WC_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * Post type standing in for one owned by a third-party plugin.
	 */
	private const THIRD_PARTY_POST_TYPE = 'wc_test_third_party';

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
	 * Original taxonomies associated with products.
	 *
	 * @var string[]
	 */
	private $original_product_taxonomies;

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
		$this->original_product_taxonomies  = get_object_taxonomies( 'product' );
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

		if ( post_type_exists( self::THIRD_PARTY_POST_TYPE ) ) {
			unregister_post_type( self::THIRD_PARTY_POST_TYPE );
		}

		if ( get_stylesheet() !== $this->original_theme ) {
			switch_theme( $this->original_theme );
		}

		if ( false !== $this->original_theme_support ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact global state changed by the test.
			$_wp_theme_features['woocommerce'] = $this->original_theme_support;
		} else {
			unset( $_wp_theme_features['woocommerce'] );
		}

		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();
		foreach ( $this->original_product_taxonomies as $taxonomy ) {
			register_taxonomy_for_object_type( $taxonomy, 'product' );
		}

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
	 * @testdox Product archive registration uses runtime support unless the active theme may not be loaded.
	 * @dataProvider provide_product_archive_theme_support_cases
	 *
	 * @param bool   $runtime_support      Whether the current request reports theme support.
	 * @param bool   $theme_unavailable    Whether the active theme may not be loaded on this request.
	 * @param string $stored_support       Stored support from the last trusted request.
	 * @param bool   $expected             Expected resolved support.
	 */
	public function test_should_register_product_archive(
		bool $runtime_support,
		bool $theme_unavailable,
		string $stored_support,
		bool $expected
	): void {
		update_option( 'current_theme_supports_woocommerce', $stored_support );

		$method = new ReflectionMethod( WC_Post_Types::class, 'should_register_product_archive' );
		$method->setAccessible( true );

		$this->assertSame(
			$expected,
			$method->invoke( null, $runtime_support, $theme_unavailable ),
			'Product archive support should only fall back to trusted stored support when the active theme may not be loaded.'
		);
	}

	/**
	 * Data provider for product archive theme support resolution.
	 *
	 * @return array<string, array{bool, bool, string, bool}>
	 */
	public function provide_product_archive_theme_support_cases(): array {
		return array(
			'supported runtime'                    => array( true, false, 'no', true ),
			'ordinary cron ignores stored support' => array( false, false, 'yes', false ),
			'unavailable theme, stored support'    => array( false, true, 'yes', true ),
			'unavailable theme, stored no support' => array( false, true, 'no', false ),
			'supported runtime, unavailable theme' => array( true, true, 'no', true ),
		);
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
		$this->assertNotFalse(
			get_post_type_object( 'product' )->has_archive,
			'Installing mode should register the product archive from trusted stored support.'
		);
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
	 * @testdox An installer flush during installing mode is deferred and the next normal request restores complete rules.
	 */
	public function test_installer_flush_during_installing_mode_recovers_on_next_normal_request(): void {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		// Phase 1 - healthy baseline: supported theme, a third-party post type, complete persisted rules.
		switch_theme( 'storefront' );
		add_theme_support( 'woocommerce' );
		update_option( 'current_theme_supports_woocommerce', 'yes' );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		$this->register_third_party_post_type();
		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();
		WC_Post_Types::flush_rewrite_rules();

		$baseline_archive     = $this->count_rules_matching( 'post_type=product' );
		$baseline_third_party = $this->count_rules_matching( self::THIRD_PARTY_POST_TYPE );

		$this->assertGreaterThan( 0, $baseline_archive, 'The baseline must contain product archive rewrite rules.' );
		$this->assertGreaterThan( 0, $baseline_third_party, 'The baseline must contain the third-party rewrite rules.' );

		// Phase 2 - installing mode: no theme, no third-party plugin, then the installer flush.
		remove_theme_support( 'woocommerce' );
		unregister_post_type( self::THIRD_PARTY_POST_TYPE );
		wp_installing( true );

		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();

		/**
		 * Simulate the installer flush that WC_Install::install() fires.
		 *
		 * @since 2.7.0
		 */
		do_action( 'woocommerce_flush_rewrite_rules' );

		wp_installing( false );

		$this->assertSame(
			$baseline_archive,
			$this->count_rules_matching( 'post_type=product' ),
			'An installer flush during installing mode must not drop the persisted product archive rules.'
		);
		$this->assertSame(
			$baseline_third_party,
			$this->count_rules_matching( self::THIRD_PARTY_POST_TYPE ),
			'An installer flush during installing mode must not drop rules owned by plugins that were not loaded.'
		);
		$this->assertSame(
			'yes',
			get_option( 'woocommerce_queue_flush_rewrite_rules' ),
			'An installer flush during installing mode should be deferred to the next normal request.'
		);

		// Phase 3 - next normal request with the theme and the third-party plugin back.
		add_theme_support( 'woocommerce' );
		$this->register_third_party_post_type();
		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();
		WC_Post_Types::maybe_flush_rewrite_rules();

		$this->assertSame(
			'no',
			get_option( 'woocommerce_queue_flush_rewrite_rules' ),
			'The next normal request should consume the deferred flush.'
		);
		$this->assertSame(
			$baseline_archive,
			$this->count_rules_matching( 'post_type=product' ),
			'The next normal request must serve complete product archive rules.'
		);
		$this->assertSame(
			$baseline_third_party,
			$this->count_rules_matching( self::THIRD_PARTY_POST_TYPE ),
			'The next normal request must restore rules owned by plugins that were not loaded.'
		);
	}

	/**
	 * @testdox A flush deferred during installing mode survives a persistent object cache.
	 * @dataProvider provide_queue_option_autoload_cases
	 *
	 * @param bool $autoload Whether the queue option is autoloaded.
	 */
	public function test_deferred_flush_is_visible_to_the_next_request( bool $autoload ): void {
		// update_option() skips every cache write while WordPress is installing, and a new PHP
		// request does not clear a persistent object cache, so the queue has to be evicted wherever
		// the option happens to be cached.
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );
		add_option( 'woocommerce_queue_flush_rewrite_rules', 'no', '', $autoload );

		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'The queue should start cached and unset.' );

		wp_installing( true );
		WC_Post_Types::flush_rewrite_rules();
		wp_installing( false );

		$this->assertSame(
			'yes',
			get_option( 'woocommerce_queue_flush_rewrite_rules' ),
			'The next request should read the queued flush rather than a stale cached value.'
		);
	}

	/**
	 * Data provider covering both places the queue option can be cached.
	 *
	 * @return array<string, array{bool}>
	 */
	public function provide_queue_option_autoload_cases(): array {
		return array(
			'autoloaded, cached in the alloptions bundle' => array( true ),
			'not autoloaded, cached on its own key'       => array( false ),
		);
	}

	/**
	 * @testdox A flush deferred during installing mode stays scoped to the site that raised it.
	 * @group ms-required
	 */
	public function test_deferred_flush_is_scoped_to_the_current_site(): void {
		$this->skipWithoutMultisite();

		$original_blog_id = get_current_blog_id();
		$other_blog_id    = $this->factory->blog->create( array( 'path' => '/wc-flush-scope/' ) );
		$sentinel_rules   = array( '^wc-other-site-sentinel/?$' => 'index.php?wc-other-site-sentinel=1' );

		// Give the neighbouring site its own settled state, read once so both option caches are warm.
		switch_to_blog( $other_blog_id );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		update_option( 'rewrite_rules', $sentinel_rules );
		get_option( 'woocommerce_queue_flush_rewrite_rules' );
		get_option( 'rewrite_rules' );
		restore_current_blog();

		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );

		wp_installing( true );
		WC_Post_Types::flush_rewrite_rules();
		wp_installing( false );

		$this->assertSame(
			'yes',
			get_option( 'woocommerce_queue_flush_rewrite_rules' ),
			'The site that raised the flush should carry the queue.'
		);
		$this->assertSame( $original_blog_id, get_current_blog_id(), 'Queueing the flush should not switch sites.' );

		switch_to_blog( $other_blog_id );
		$other_queue = get_option( 'woocommerce_queue_flush_rewrite_rules' );
		$other_rules = get_option( 'rewrite_rules' );
		restore_current_blog();

		$this->assertSame( 'no', $other_queue, 'A deferred flush should not queue work on a neighbouring site.' );
		$this->assertSame( $sentinel_rules, $other_rules, 'A deferred flush should not disturb a neighbouring site\'s rules.' );
		$this->assertSame( $original_blog_id, get_current_blog_id(), 'The original site context should be restored.' );
	}

	/**
	 * Register a public post type standing in for one owned by a third-party plugin.
	 */
	private function register_third_party_post_type(): void {
		register_post_type(
			self::THIRD_PARTY_POST_TYPE,
			array(
				'public'      => true,
				'has_archive' => true,
				'rewrite'     => array( 'slug' => self::THIRD_PARTY_POST_TYPE ),
			)
		);
	}

	/**
	 * Count persisted rewrite rules whose query string contains the given needle.
	 *
	 * @param string $needle Query string fragment to match.
	 * @return int
	 */
	private function count_rules_matching( string $needle ): int {
		$rules = (array) get_option( 'rewrite_rules' );

		return count(
			array_filter(
				$rules,
				static function ( $query ) use ( $needle ): bool {
					return str_contains( (string) $query, $needle );
				}
			)
		);
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
