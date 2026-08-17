<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\Notices;
use WC_Unit_Test_Case;
use WP_Hook;

/**
 * Tests for the Notices service.
 */
class NoticesTest extends WC_Unit_Test_Case {

	/**
	 * Original active theme stylesheet slug.
	 *
	 * @var string|null
	 */
	private static $original_theme = null;

	/**
	 * Hooks restored after each test.
	 *
	 * @var string[]
	 */
	private const SNAPSHOT_HOOKS = array(
		'after_setup_theme',
		'wc_get_template',
		'wp_head',
		'woocommerce_kses_notice_allowed_tags',
		'woocommerce_use_block_notices_in_classic_theme',
		'doing_it_wrong_trigger_error',
	);

	/**
	 * Action counts restored after each test.
	 *
	 * @var string[]
	 */
	private const SNAPSHOT_ACTION_COUNTS = array(
		'after_setup_theme',
		'wp_head',
	);

	/**
	 * Original hook state.
	 *
	 * @var array<string, WP_Hook|null>
	 */
	private $hook_snapshots = array();

	/**
	 * Original action counts.
	 *
	 * @var array<string, int|null>
	 */
	private $action_counts = array();

	/**
	 * Original style queue state.
	 *
	 * @var array<string, mixed>
	 */
	private $style_snapshot = array();

	/**
	 * Theme directories registered before each test.
	 *
	 * @var string[]
	 */
	private $theme_directories = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_theme_directories;

		$this->theme_directories = $wp_theme_directories;
		register_theme_directory( trailingslashit( WC_ABSPATH ) . 'tests/e2e/themes/blocks' );
		search_theme_directories( true );
		if ( null === self::$original_theme ) {
			self::$original_theme = get_stylesheet();
		}
		$this->snapshot_hooks();
		$this->snapshot_action_counts();
		$this->snapshot_styles();
		$this->reset_wc_blocks_style_queue();
		wc_clear_template_cache();
	}

	/**
	 * Restore test fixtures.
	 */
	public function tearDown(): void {
		global $wp_theme_directories;

		if ( null !== self::$original_theme && get_stylesheet() !== self::$original_theme ) {
			switch_theme( self::$original_theme );
		}

		$this->restore_hooks();
		$this->restore_action_counts();
		$this->restore_styles();
		wc_clear_template_cache();
		$wp_theme_directories = $this->theme_directories; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact theme roots registered before this test.
		search_theme_directories( true );

		parent::tearDown();
	}

	/**
	 * Restore the original theme after the class completes.
	 */
	public static function tearDownAfterClass(): void {
		if ( null !== self::$original_theme && get_stylesheet() !== self::$original_theme ) {
			switch_theme( self::$original_theme );
		}
	}

	/**
	 * @testdox Notices activate the expected hooks, locate the expected template, and enqueue styles for every block-theme branch.
	 */
	public function test_block_theme_notice_template_matrix(): void {
		$this->setExpectedIncorrectUsage( 'WP_Block_Templates_Registry::register' );
		add_filter( 'doing_it_wrong_trigger_error', array( $this, 'suppress_block_template_registry_notice' ), 10, 4 );

		foreach ( array( false, true ) as $opt_in_filter ) {
			foreach ( array( 'none', 'block', 'classic' ) as $fixture_kind ) {
				foreach ( array( 'notices/error.php', 'notices/notice.php', 'notices/success.php' ) as $template_name ) {
					$this->assert_notice_template_row( 'twentytwentyfour', $opt_in_filter, $fixture_kind, $template_name );
				}
			}
		}
	}

	/**
	 * @testdox Notices activate the expected hooks, locate the expected template, and enqueue styles for every classic-theme branch.
	 */
	public function test_classic_theme_notice_template_matrix(): void {
		foreach ( array( false, true ) as $opt_in_filter ) {
			foreach ( array( 'none', 'block', 'classic' ) as $fixture_kind ) {
				foreach ( array( 'notices/error.php', 'notices/notice.php', 'notices/success.php' ) as $template_name ) {
					$this->assert_notice_template_row( 'storefront', $opt_in_filter, $fixture_kind, $template_name );
				}
			}
		}
	}

	/**
	 * @testdox Notices leave unrelated templates untouched.
	 */
	public function test_unrelated_templates_pass_through_unchanged(): void {
		$base_template = wc_locate_template( 'cart/cart-empty.php' );
		$resolved      = ( new Notices( new Package( 'test', WC_ABSPATH ) ) )->get_notices_template(
			$base_template,
			'cart/cart-empty.php',
			array(),
			'',
			trailingslashit( WC_ABSPATH ) . 'templates/'
		);

		$this->assertSame( $base_template, $resolved, 'The service should not replace templates outside the three notice files.' );
		$this->assertFalse( wp_style_is( 'wc-blocks-style', 'enqueued' ), 'Passing through an unrelated template must not enqueue block notice styles.' );
	}

	/**
	 * @testdox Notices preserve existing allowed tags and add the exact SVG/path allow-list.
	 */
	public function test_notice_kses_tags_preserve_existing_tags_and_add_svg_support(): void {
		$sut          = new Notices( new Package( 'test', WC_ABSPATH ) );
		$allowed_tags = array(
			'a' => array(
				'href' => true,
			),
		);

		$sut->init();

		/**
		 * Run the notices allow-list filter through the live callback stack.
		 *
		 * @since 11.1.0
		 */
		$result = apply_filters( 'woocommerce_kses_notice_allowed_tags', $allowed_tags );

		$this->assertArrayHasKey( 'a', $result, 'Existing allow-listed tags should be preserved.' );
		$this->assertSame( $allowed_tags['a'], $result['a'], 'Existing allow-listed tag attributes should remain unchanged.' );
		$this->assertSame(
			array(
				'aria-hidden' => true,
				'xmlns'       => true,
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'focusable'   => true,
			),
			$result['svg'] ?? null,
			'The filter should add the exact SVG attributes used by block notices.'
		);
		$this->assertSame(
			array(
				'd' => true,
			),
			$result['path'] ?? null,
			'The filter should add the exact SVG path attributes used by block notices.'
		);
	}

	/**
	 * Snapshot the hooks touched by the suite.
	 */
	private function snapshot_hooks(): void {
		foreach ( self::SNAPSHOT_HOOKS as $hook_name ) {
			$this->hook_snapshots[ $hook_name ] = isset( $GLOBALS['wp_filter'][ $hook_name ] ) ? clone $GLOBALS['wp_filter'][ $hook_name ] : null;
		}
	}

	/**
	 * Restore the hooks touched by the suite.
	 */
	private function restore_hooks(): void {
		foreach ( self::SNAPSHOT_HOOKS as $hook_name ) {
			if ( $this->hook_snapshots[ $hook_name ] instanceof WP_Hook ) {
				$GLOBALS['wp_filter'][ $hook_name ] = clone $this->hook_snapshots[ $hook_name ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact hook stack captured at setUp.
			} elseif ( isset( $GLOBALS['wp_filter'][ $hook_name ] ) ) {
				unset( $GLOBALS['wp_filter'][ $hook_name ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Remove callbacks registered by this test.
			}
		}
	}

	/**
	 * Snapshot the action counts touched by the suite.
	 */
	private function snapshot_action_counts(): void {
		foreach ( self::SNAPSHOT_ACTION_COUNTS as $action_name ) {
			$this->action_counts[ $action_name ] = $GLOBALS['wp_actions'][ $action_name ] ?? null;
		}
	}

	/**
	 * Restore the action counts touched by the suite.
	 */
	private function restore_action_counts(): void {
		foreach ( self::SNAPSHOT_ACTION_COUNTS as $action_name ) {
			if ( null !== $this->action_counts[ $action_name ] ) {
				$GLOBALS['wp_actions'][ $action_name ] = $this->action_counts[ $action_name ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action count captured at setUp.
			} elseif ( isset( $GLOBALS['wp_actions'][ $action_name ] ) ) {
				unset( $GLOBALS['wp_actions'][ $action_name ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Remove counts introduced by this test.
			}
		}
	}

	/**
	 * Snapshot the style queue state touched by the suite.
	 */
	private function snapshot_styles(): void {
		$wp_styles            = wp_styles();
		$this->style_snapshot = array(
			'queue'  => $wp_styles->queue,
			'done'   => $wp_styles->done,
			'to_do'  => $wp_styles->to_do,
			'groups' => $wp_styles->groups,
			'args'   => $wp_styles->args,
		);
	}

	/**
	 * Restore the style queue state touched by the suite.
	 */
	private function restore_styles(): void {
		$wp_styles         = wp_styles();
		$wp_styles->queue  = $this->style_snapshot['queue'];
		$wp_styles->done   = $this->style_snapshot['done'];
		$wp_styles->to_do  = $this->style_snapshot['to_do'];
		$wp_styles->groups = $this->style_snapshot['groups'];
		$wp_styles->args   = $this->style_snapshot['args'];
	}

	/**
	 * Remove wc-blocks-style from the working queue so each test starts clean.
	 */
	private function reset_wc_blocks_style_queue(): void {
		$wp_styles        = wp_styles();
		$wp_styles->queue = array_values( array_diff( $wp_styles->queue, array( 'wc-blocks-style' ) ) );
		$wp_styles->done  = array_values( array_diff( $wp_styles->done, array( 'wc-blocks-style' ) ) );
		$wp_styles->to_do = array_values( array_diff( $wp_styles->to_do, array( 'wc-blocks-style' ) ) );
		unset( $wp_styles->args['wc-blocks-style'] );
	}

	/**
	 * Assert one row from the notices matrix.
	 *
	 * @param string $theme_slug Active base theme slug.
	 * @param bool   $opt_in_filter Whether the classic-theme opt-in filter returns true.
	 * @param string $fixture_kind Fixture type.
	 * @param string $template_name Notice template to resolve.
	 */
	private function assert_notice_template_row( string $theme_slug, bool $opt_in_filter, string $fixture_kind, string $template_name ): void {
		$this->restore_hooks();
		$this->restore_action_counts();
		$this->restore_styles();
		$this->reset_wc_blocks_style_queue();
		wc_clear_template_cache();

		$sut               = new Notices( new Package( 'test', WC_ABSPATH ) );
		$active_theme_slug = $this->get_active_theme_slug( $theme_slug, $fixture_kind );

		if ( 'twentytwentyfour' === $theme_slug ) {
			add_filter( 'doing_it_wrong_trigger_error', array( $this, 'suppress_block_template_registry_notice' ), 10, 4 );
		}

		switch_theme( $active_theme_slug );
		add_filter(
			'woocommerce_use_block_notices_in_classic_theme',
			$opt_in_filter ? '__return_true' : '__return_false'
		);

		$sut->init();

		$this->assertSame(
			10,
			has_filter( 'woocommerce_kses_notice_allowed_tags', array( $sut, 'add_kses_notice_allowed_tags' ) ),
			'The SVG/path allow-list callback should always be registered at priority 10.'
		);

		/**
		 * Trigger the service's theme-setup gate after init() has registered its callbacks.
		 *
		 * @since 11.1.0
		 */
		do_action( 'after_setup_theme' );

		$service_is_active = wp_is_block_theme() || $opt_in_filter;
		$expected_path     = $this->get_expected_template_path( $theme_slug, $fixture_kind, $template_name, $service_is_active );
		$result            = $this->resolve_notice_template( $template_name );

		$this->assertSame(
			$service_is_active ? 10 : false,
			has_filter( 'wc_get_template', array( $sut, 'get_notices_template' ) ),
			'The wc_get_template callback should match the theme/filter activation branch.'
		);
		$this->assertSame(
			$service_is_active ? 10 : false,
			has_action( 'wp_head', array( $sut, 'enqueue_notice_styles' ) ),
			'The wp_head enqueue callback should match the theme/filter activation branch.'
		);
		$this->assertSame(
			$expected_path,
			$result['located'],
			'The resolved template path should follow the exact theme/filter/fixture matrix.'
		);
		$this->assertSame(
			$service_is_active && 'none' === $fixture_kind,
			$result['style_enqueued_after_resolution'],
			'The packaged block template should enqueue wc-blocks-style during resolution only when no child override handled the template.'
		);

		ob_start();
		/**
		 * Mirror the front-end head render so the shared stylesheet enqueue can run.
		 *
		 * @since 11.1.0
		 */
		do_action( 'wp_head' );
		ob_end_clean();

		$this->assertSame(
			$service_is_active,
			wp_style_is( 'wc-blocks-style', 'enqueued' ),
			'The shared notice stylesheet should only be enqueued after wp_head when block notices are active.'
		);
	}

	/**
	 * Resolve a notice template through the real Woo template loader.
	 *
	 * @param string $template_name Template name.
	 * @return array{located: string, style_enqueued_after_resolution: bool}
	 */
	private function resolve_notice_template( string $template_name ): array {
		$captured_template = '';
		$capture_callback  = static function ( $name, $template_path, $located ) use ( $template_name, &$captured_template ): void {
			if ( $template_name === $name ) {
				$captured_template = $located;
			}
		};

		wc_clear_template_cache();
		add_action( 'woocommerce_before_template_part', $capture_callback, 10, 3 );

		try {
			wc_get_template_html(
				$template_name,
				array(
					'notices' => array(
						array(
							'notice' => 'Test notice',
							'data'   => array(),
						),
					),
				)
			);
		} finally {
			remove_action( 'woocommerce_before_template_part', $capture_callback, 10 );
		}

		$this->assertNotSame( '', $captured_template, 'Resolving the notice template should capture the located path from the real Woo template loader.' );

		return array(
			'located'                         => $captured_template,
			'style_enqueued_after_resolution' => wp_style_is( 'wc-blocks-style', 'enqueued' ),
		);
	}

	/**
	 * Resolve the active theme slug for a matrix row.
	 *
	 * @param string $theme_slug Active theme slug.
	 * @param string $fixture_kind Fixture type.
	 * @return string
	 */
	private function get_active_theme_slug( string $theme_slug, string $fixture_kind ): string {
		if ( 'none' === $fixture_kind ) {
			return $theme_slug;
		}

		return 'twentytwentyfour' === $theme_slug
			? sprintf( 'twentytwentyfour-child__%s-notices-template', $fixture_kind )
			: sprintf( 'storefront-child__%s-notices-template', $fixture_kind );
	}

	/**
	 * Compute the exact expected resolved template path for a matrix row.
	 *
	 * @param string $theme_slug Active theme slug.
	 * @param string $fixture_kind Fixture type.
	 * @param string $template_name Template name.
	 * @param bool   $service_is_active Whether block notices are active.
	 * @return string
	 */
	private function get_expected_template_path(
		string $theme_slug,
		string $fixture_kind,
		string $template_name,
		bool $service_is_active
	): string {
		if ( 'none' !== $fixture_kind ) {
			$active_theme_slug = $this->get_active_theme_slug( $theme_slug, $fixture_kind );

			return trailingslashit( get_theme_root( $active_theme_slug ) ) . $active_theme_slug . '/woocommerce/' . $template_name;
		}

		if ( $service_is_active ) {
			return trailingslashit( WC_ABSPATH ) . 'templates/block-' . $template_name;
		}

		return trailingslashit( WC_ABSPATH ) . 'templates/' . $template_name;
	}

	/**
	 * Suppress the unrelated block template registry duplicate warning during block-theme setup.
	 *
	 * @param bool   $trigger_error Whether WordPress should trigger the warning.
	 * @param string $function_name Function reporting incorrect usage.
	 * @param string $message Warning message.
	 * @param string $version Version that introduced the warning.
	 * @return bool
	 */
	public function suppress_block_template_registry_notice( bool $trigger_error, string $function_name, string $message, string $version ): bool {
		unset( $version );

		if (
			'WP_Block_Templates_Registry::register' === $function_name
			&& str_contains( $message, 'Template "woocommerce//archive-product" is already registered.' )
		) {
			return false;
		}

		return $trigger_error;
	}
}
