<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ComingSoon;

use Automattic\WooCommerce\Blocks\BlockTemplatesController;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;

/**
 * Tests for the coming soon cache invalidator class.
 */
class ComingSoonRequestHandlerTest extends \WC_Unit_Test_Case {

	private const ENTIRE_SITE_TEMPLATE = WC_ABSPATH . 'templates/coming-soon/coming-soon-entire-site.php';

	/**
	 * System under test.
	 *
	 * @var ComingSoonRequestHandler;
	 */
	private $sut;

	/**
	 * Process state not restored by the WordPress test transaction.
	 *
	 * @var array
	 */
	private $original_state;

	/**
	 * Static visibility cache, isolated for each request fixture.
	 *
	 * @var \ReflectionProperty
	 */
	private $visibility;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->visibility = new \ReflectionProperty( ComingSoonRequestHandler::class, 'show_coming_soon' );
		$this->visibility->setAccessible( true );
		$this->original_state = array(
			'theme'      => get_stylesheet(),
			'features'   => $GLOBALS['_wp_theme_features'],
			'styles'     => $GLOBALS['wp_styles'] ?? null,
			'get'        => $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot simulated request data, not a submitted action.
			'cookie'     => $_COOKIE,
			'visibility' => $this->visibility->getValue(),
		);
		switch_theme( 'storefront' );
		remove_theme_support( 'block-template-parts' );
		remove_theme_support( 'block-templates' );
		$GLOBALS['wp_styles'] = new \WP_Styles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Isolate queued/rendered assets from other request fixtures.
		$this->visibility->setValue( null, false );
		wp_set_current_user( 0 );
		unset( $_GET['woo-share'], $_COOKIE['woo-share'] );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'no' );
		update_option( 'woocommerce_private_link', 'no' );
		$this->sut = wc_get_container()->get( ComingSoonRequestHandler::class );
	}

	/**
	 * Restore process state.
	 */
	public function tearDown(): void {
		try {
			switch_theme( $this->original_state['theme'] );
			$GLOBALS['_wp_theme_features'] = $this->original_state['features']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore theme capabilities changed by the request fixture.
			$GLOBALS['wp_styles']          = $this->original_state['styles']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the original assets registry after rendering a document.
			$_GET                          = $this->original_state['get'];
			$_COOKIE                       = $this->original_state['cookie'];
			$this->visibility->setValue( null, $this->original_state['visibility'] );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should select the appropriate PHP document without rendering inside the filter.
	 * @testWith ["no", "coming-soon-entire-site.php"]
	 *           ["yes", "coming-soon-store-only.php"]
	 *
	 * @param string $store_only Whether to restrict Coming Soon to store pages.
	 * @param string $filename   Expected PHP template filename.
	 */
	public function test_classic_template_selection( string $store_only, string $filename ): void {
		update_option( 'woocommerce_store_pages_only', $store_only );
		$shop_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_id );
		$this->go_to( get_permalink( $shop_id ) );

		$template = null;
		$output   = $this->capture(
			function () use ( &$template ) {
				$template = $this->sut->handle_classic_template_include( '/original.php' );
			}
		);

		$this->assertSame( WC_ABSPATH . 'templates/coming-soon/' . $filename, $template );
		$this->assertSame( '', $output, 'Selection must let WordPress render the template.' );
		$this->assertFalse( current_theme_supports( 'block-templates' ), 'The PHP fallback must not force block support.' );
		$this->assertTrue( wp_style_is( 'woocommerce-coming-soon-classic', 'enqueued' ) );
		$this->assertSame( WC()->plugin_url() . '/assets/css/coming-soon-classic.css', wp_styles()->registered['woocommerce-coming-soon-classic']->src );
	}

	/**
	 * @testdox Should return the original path without PHP assets on bypass requests.
	 * @testWith ["live"]
	 *           ["administrator"]
	 *           ["shop_manager"]
	 *           ["private_cookie"]
	 *           ["excluded"]
	 *           ["non_store"]
	 *           ["store_404"]
	 *           ["block_parts"]
	 *           ["block_theme"]
	 *
	 * @param string $scenario Access or theme bypass condition.
	 */
	public function test_classic_template_bypass( string $scenario ): void {
		$this->go_to( home_url( '/' ) );
		switch ( $scenario ) {
			case 'live':
				update_option( 'woocommerce_coming_soon', 'no' );
				break;
			case 'administrator':
			case 'shop_manager':
				wp_set_current_user( $this->factory->user->create( array( 'role' => $scenario ) ) );
				break;
			case 'private_cookie':
				update_option( 'woocommerce_private_link', 'yes' );
				update_option( 'woocommerce_share_key', 'test-share-key' );
				$_COOKIE['woo-share'] = 'test-share-key';
				break;
			case 'excluded':
				add_filter( 'woocommerce_coming_soon_exclude', '__return_true' );
				break;
			case 'non_store':
				update_option( 'woocommerce_store_pages_only', 'yes' );
				break;
			case 'store_404':
				update_option( 'woocommerce_store_pages_only', 'yes' );
				$GLOBALS['wp_query']->set_404();
				break;
			case 'block_parts':
				add_theme_support( 'block-template-parts' );
				break;
			case 'block_theme':
				switch_theme( 'twentytwentytwo' );
				$this->assertTrue( wp_is_block_theme(), 'This fixture must exercise a real block theme.' );
				break;
		}

		$this->assertSame( '/original.php', $this->sut->handle_classic_template_include( '/original.php' ) );
		$this->assertFalse( wp_style_is( 'woocommerce-coming-soon-classic', 'enqueued' ) );
	}

	/**
	 * @testdox Should retain only applicable published saved Coming Soon designs.
	 * @dataProvider saved_template_cases
	 *
	 * @param string $type      Saved template post type.
	 * @param string $slug      Saved template slug.
	 * @param string $status    Saved template status.
	 * @param string $scope     Saved template theme or plugin scope.
	 * @param bool   $preserved Whether to retain the block renderer.
	 */
	public function test_saved_template_selection( string $type, string $slug, string $status, string $scope, bool $preserved ): void {
		$this->create_saved_design( $type, $slug, $status, 'current' === $scope ? get_stylesheet() : $scope );

		$expected = $preserved ? '/original.php' : self::ENTIRE_SITE_TEMPLATE;
		$this->assertSame( $expected, $this->sut->handle_classic_template_include( '/original.php' ) );
		$this->assertSame( ! $preserved, wp_style_is( 'woocommerce-coming-soon-classic', 'enqueued' ) );
	}

	/**
	 * Saved template scope and status boundaries.
	 *
	 * @return array
	 */
	public function saved_template_cases(): array {
		$cases = array();
		$slugs = array(
			'wp_template'      => 'coming-soon',
			'wp_template_part' => 'coming-soon-social-links',
		);
		foreach ( $slugs as $type => $slug ) {
			foreach ( array( 'current', 'woocommerce/woocommerce', 'woocommerce' ) as $scope ) {
				$cases[ $type . '-' . $scope ] = array( $type, $slug, 'publish', $scope, true );
			}
			$cases[ $type . '-draft' ]       = array( $type, $slug, 'draft', 'current', false );
			$cases[ $type . '-trash' ]       = array( $type, $slug, 'trash', 'current', false );
			$cases[ $type . '-other-theme' ] = array( $type, $slug, 'publish', 'another-theme', false );
			$cases[ $type . '-unrelated' ]   = array( $type, 'unrelated', 'publish', 'current', false );
		}
		$cases['wrong-post-type'] = array( 'wp_template_part', 'coming-soon', 'publish', 'current', false );
		return $cases;
	}

	/**
	 * @testdox Should preserve only filesystem Coming Soon roots without enabling block support.
	 * @testWith ["child", "templates", "coming-soon", true]
	 *           ["parent", "templates", "coming-soon", true]
	 *           ["child", "block-templates", "coming-soon", true]
	 *           ["parent", "block-templates", "coming-soon", true]
	 *           ["child", "templates", "unrelated", false]
	 *           ["parent", "templates", "unrelated", false]
	 *           ["child", "parts", "coming-soon-social-links", false]
	 *
	 * @param string $theme     Theme directory holding the fixture.
	 * @param string $directory Modern or deprecated template directory.
	 * @param string $slug      Fixture template slug.
	 * @param bool   $preserved Whether to retain the existing renderer.
	 */
	public function test_filesystem_template_selection( string $theme, string $directory, string $slug, bool $preserved ): void {
		$root = $this->create_fixture_directory();
		$path = $root . '/' . $theme . '/' . $directory . '/' . $slug . '.html';
		try {
			$this->assertTrue( wp_mkdir_p( dirname( $path ) ) );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write only a fresh task-owned template fixture, never an installed theme design.
			$this->assertNotFalse( file_put_contents( $path, '<!-- wp:paragraph --><p>WC50520 root design</p><!-- /wp:paragraph -->' ) );
			add_filter( 'stylesheet_directory', fn() => $root . '/child' );
			add_filter( 'template_directory', fn() => $root . '/parent' );

			$expected = $preserved ? '/original.php' : self::ENTIRE_SITE_TEMPLATE;
			$this->assertSame( $expected, $this->sut->handle_classic_template_include( '/original.php' ) );
			$this->assertSame( ! $preserved, wp_style_is( 'woocommerce-coming-soon-classic', 'enqueued' ) );
			$this->assertFalse( current_theme_supports( 'block-templates' ) );
		} finally {
			wp_delete_file( $path );
			$this->remove_fixture_directory( dirname( $path ) );
			$this->remove_fixture_directory( $root . '/' . $theme );
			$this->remove_fixture_directory( $root );
		}
	}

	/**
	 * Create an isolated filesystem fixture without touching installed themes.
	 *
	 * @return string Fresh task-owned directory.
	 */
	private function create_fixture_directory(): string {
		$path = get_temp_dir() . 'wc50520-' . wp_generate_uuid4();
		$this->assertFileDoesNotExist( $path );
		$this->assertTrue( wp_mkdir_p( $path ) );
		return $path;
	}

	/**
	 * Remove only an empty directory created by this test.
	 *
	 * @param string $path Task-owned directory.
	 */
	private function remove_fixture_directory( string $path ): void {
		if ( is_dir( $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove only an empty task-owned fixture directory.
			$this->assertTrue( rmdir( $path ) );
		}
	}

	/**
	 * Create a saved Coming Soon design scoped to a theme or plugin.
	 *
	 * @param string      $type   Template post type.
	 * @param string      $slug   Template slug.
	 * @param string      $status Post status.
	 * @param string|null $scope  `wp_theme` term; defaults to the active theme.
	 */
	private function create_saved_design( string $type = 'wp_template', string $slug = 'coming-soon', string $status = 'publish', ?string $scope = null ): void {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => $type,
				'post_name'   => $slug,
				'post_status' => $status,
			)
		);
		wp_set_object_terms( $post_id, $scope ?? get_stylesheet(), 'wp_theme' );
	}

	/**
	 * Run a callback and return what it printed, closing the buffer even on failure.
	 *
	 * @param callable $callback Code that may print.
	 * @return string Captured output.
	 */
	private function capture( callable $callback ): string {
		ob_start();
		try {
			$callback();
			return (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}

	/**
	 * Render a selected template file.
	 *
	 * @param string $template Template path.
	 * @return string Rendered output.
	 */
	private function render( string $template ): string {
		return $this->capture(
			function () use ( $template ) {
				include $template;
			}
		);
	}

	/**
	 * @testdox Should pass the original query-template filter arguments exactly once and honor a replacement.
	 */
	public function test_classic_query_template_filters(): void {
		$hierarchy_calls = array();
		$template_calls  = array();
		add_filter(
			'coming-soon_template_hierarchy',
			function ( $templates ) use ( &$hierarchy_calls ) {
				$hierarchy_calls[] = $templates;
				return $templates;
			}
		);
		add_filter(
			'coming-soon_template',
			function ( $template, $type, $templates ) use ( &$template_calls ) {
				$template_calls[] = array( $template, $type, $templates );
				return WC_ABSPATH . 'templates/single-product.php';
			},
			10,
			3
		);

		$this->assertSame( WC_ABSPATH . 'templates/single-product.php', $this->sut->handle_classic_template_include( '/original.php' ) );
		$this->assertSame( array( array( 'coming-soon.php' ) ), $hierarchy_calls );
		$this->assertCount( 1, $template_calls );
		$this->assertSame( self::ENTIRE_SITE_TEMPLATE, $template_calls[0][0] );
		$this->assertSame( 'coming-soon', $template_calls[0][1] );
		$this->assertSame( array( 'coming-soon.php' ), $template_calls[0][2] );
	}

	/**
	 * @testdox Should never expose the protected page when a template filter returns an unusable path.
	 * @dataProvider unusable_template_cases
	 *
	 * @param string $filter Template path filter to exercise.
	 * @param mixed  $value  Unusable filtered value.
	 */
	public function test_classic_template_failure_is_closed( string $filter, $value ): void {
		add_filter( $filter, fn() => $value );

		$this->assertSame( '', $this->sut->handle_classic_template_include( '/protected.php' ) );
		$this->assertFalse( wp_style_is( 'woocommerce-coming-soon-classic', 'enqueued' ) );
	}

	/**
	 * Unusable values from each template path filter.
	 *
	 * @return array
	 */
	public function unusable_template_cases(): array {
		$cases  = array();
		$values = array(
			'false'     => false,
			'null'      => null,
			'array'     => array( 'invalid' ),
			'empty'     => '',
			'missing'   => '/missing-coming-soon.php',
			'directory' => __DIR__,
		);
		foreach ( array( 'coming-soon_template', 'woocommerce_locate_template' ) as $filter ) {
			foreach ( $values as $name => $value ) {
				$cases[ $filter . '-' . $name ] = array( $filter, $value );
			}
		}
		return $cases;
	}

	/**
	 * @testdox Should reject an unreadable file returned by a template filter.
	 */
	public function test_classic_template_rejects_unreadable_file(): void {
		$root = $this->create_fixture_directory();
		$path = $root . '/unreadable.php';
		try {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write only a fresh task-owned unreadable-path fixture.
			$this->assertNotFalse( file_put_contents( $path, '<?php /* WC50520 fixture */' ) );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Change only task-owned fixture permissions to exercise unreadability.
			$this->assertTrue( chmod( $path, 0000 ) );
			clearstatcache( true, $path );
			if ( is_readable( $path ) ) {
				$this->markTestSkipped( 'This test process can still read chmod 0000 files; unreadability cannot be reproduced.' );
			}
			add_filter( 'coming-soon_template', fn() => $path );

			$this->assertSame( '', $this->sut->handle_classic_template_include( '/protected.php' ) );
			$this->assertFalse( wp_style_is( 'woocommerce-coming-soon-classic', 'enqueued' ) );
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Restore task-owned fixture permissions before deleting it.
			chmod( $path, 0600 );
			wp_delete_file( $path );
			$this->remove_fixture_directory( $root );
		}
	}

	/**
	 * @testdox Should render a complete escaped document with the Coming Soon marker and native login.
	 */
	public function test_entire_site_php_output(): void {
		update_option( 'blogname', 'Test & shop' );
		update_option( 'blogdescription', 'A <script>alert(1)</script> tagline' );
		$hook_counts = array();
		foreach ( array( 'wp_head', 'wp_body_open', 'wp_footer' ) as $hook ) {
			$hook_counts[ $hook ] = 0;
			add_action(
				$hook,
				function () use ( &$hook_counts, $hook ) {
					++$hook_counts[ $hook ];
				}
			);
		}
		$output = $this->render( $this->sut->handle_classic_template_include( '/original.php' ) );

		$this->assertSame( 1, substr_count( strtolower( $output ), '<!doctype html>' ) );
		$this->assertSame( 1, $hook_counts['wp_head'] );
		$this->assertSame( 1, $hook_counts['wp_body_open'] );
		$this->assertSame( 1, $hook_counts['wp_footer'] );
		$this->assertSame( 1, substr_count( $output, '</html>' ) );
		$this->assertSame( 1, substr_count( $output, "<meta name='woo-coming-soon-page' content='yes'>" ) );
		$this->assertStringContainsString( 'Test &amp; shop is coming soon', $output );
		$this->assertStringContainsString( esc_url( wp_login_url() ), $output );
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $output );
		$this->assertStringNotContainsString( 'wp-block-woocommerce-coming-soon', $output );
		$this->assertStringNotContainsString( 'facebook.com', $output );
	}

	/**
	 * @testdox Should render one nonempty escaped document title with and without theme title support.
	 * @testWith [false, "Shop & <launch>"]
	 *           [true, "Shop &amp; &lt;launch&gt;"]
	 *
	 * @param bool   $title_support  Whether the theme supports native title-tag rendering.
	 * @param string $filtered_title Raw fallback input, or the escaped title expected by WordPress's native renderer.
	 */
	public function test_entire_site_php_title( bool $title_support, string $filtered_title ): void {
		// Theme features are restored in tearDown(), and hooks by the base test case.
		remove_theme_support( 'title-tag' );
		if ( $title_support ) {
			$GLOBALS['_wp_theme_features']['title-tag'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate support after wp_loaded, when WordPress rejects add_theme_support for title-tag.
		}
		$this->assertSame( $title_support, current_theme_supports( 'title-tag' ) );
		remove_action( 'wp_head', '_block_template_render_title_tag', 1 );
		add_action( 'wp_head', '_wp_render_title_tag', 1 );
		add_filter( 'pre_get_document_title', fn() => $filtered_title );

		$output = $this->render( $this->sut->handle_classic_template_include( '/original.php' ) );

		preg_match_all( '/<title>(.*?)<\/title>/is', $output, $titles );
		$this->assertCount( 1, $titles[1], 'The standalone document must have exactly one title.' );
		$this->assertSame( 'Shop &amp; &lt;launch&gt;', trim( $titles[1][0] ) );
	}

	/**
	 * @testdox Should defer the legacy renderer without output for an ordinary classic request.
	 */
	public function test_legacy_renderer_defers_to_php(): void {
		$template = null;
		$output   = $this->capture(
			function () use ( &$template ) {
				$template = $this->sut->handle_template_include( '/original.php' );
			}
		);

		$this->assertSame( '/original.php', $template );
		$this->assertSame( '', $output );
	}

	/**
	 * @testdox Should keep the PHP Coming Soon page when other template_include callbacks run later.
	 */
	public function test_classic_template_wins_over_later_template_filters(): void {
		$handler = new ComingSoonRequestHandler();
		remove_all_actions( 'plugins_loaded' );
		remove_all_actions( 'template_redirect' );
		remove_all_filters( 'template_include' );
		$handler->init( wc_get_container()->get( ComingSoonHelper::class ) );
		do_action( 'plugins_loaded' );
		add_filter( 'template_include', fn() => '/theme-builder-999.php', 999 );
		add_filter( 'template_include', fn() => '/theme-builder-max.php', PHP_INT_MAX );
		do_action( 'template_redirect' );

		$this->assertSame(
			self::ENTIRE_SITE_TEMPLATE,
			apply_filters( 'template_include', '/original.php' ),
			'A later template_include callback must not replace the private page.'
		);
		$this->assertSame( 999, has_action( 'after_setup_theme', array( $handler, 'possibly_init_block_templates' ) ), 'The initializer must stay directly registered so it can be unhooked.' );
	}

	/**
	 * @testdox Should initialize block templates only when the legacy renderer is used.
	 * @testWith [false, false]
	 *           [true, true]
	 *
	 * @param bool $saved_design Whether a published Coming Soon design exists.
	 * @param bool $initialized  Whether the block template controller should be initialized.
	 */
	public function test_block_templates_initialize_only_for_legacy_renderer( bool $saved_design, bool $initialized ): void {
		if ( $saved_design ) {
			$this->create_saved_design();
		}
		$controller = BlocksPackage::container()->get( BlockTemplatesController::class );
		remove_all_filters( 'get_block_templates' );
		// Earlier registry inits leave process-wide registrations; start from a clean slate and restore it after.
		$registry      = \WP_Block_Templates_Registry::get_instance();
		$template_name = 'woocommerce//coming-soon';
		$original      = $registry->get_registered( $template_name );
		if ( $original ) {
			$registry->unregister( $template_name );
		}

		try {
			$this->sut->possibly_init_block_templates();

			$this->assertSame( $initialized, is_int( has_filter( 'get_block_templates', array( $controller, 'add_db_templates_with_woo_slug' ) ) ) );
		} finally {
			if ( $registry->is_registered( $template_name ) ) {
				$registry->unregister( $template_name );
			}
			if ( $original ) {
				$registry->register(
					$template_name,
					array(
						'title'       => $original->title,
						'description' => $original->description,
						'content'     => $original->content,
						'post_types'  => $original->post_types,
						'plugin'      => $original->plugin,
					)
				);
			}
		}
	}

	/**
	 * @testdox Should detect a saved design even when query filters would hide it.
	 */
	public function test_saved_template_detection_ignores_query_filters(): void {
		$this->create_saved_design();
		add_filter( 'posts_where', fn( $where ) => $where . ' AND 1 = 0' );

		$this->assertSame( '/original.php', $this->sut->handle_classic_template_include( '/original.php' ) );
	}

	/**
	 * @testdox Test request parser displays a coming soon page to public visitor.
	 */
	public function test_coming_soon_mode_shown_to_visitor() {
		$this->markTestSkipped( 'The die statement breaks the test. To be improved.' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertSame( $wp->query_vars['page_id'], 99 );
	}

	/**
	 * @testdox Test request parser displays a live page to public visitor.
	 */
	public function test_live_mode_shown_to_visitor() {
		$this->markTestSkipped( 'The die statement breaks the test. To be improved.' );
		update_option( 'woocommerce_coming_soon', 'no' );
		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertArrayNotHasKey( 'page_id', $wp->query_vars );
	}

	/**
	 * @testdox Test request parser excludes admins.
	 */
	public function test_shop_manager_exclusion() {
		$this->markTestSkipped( 'Failing in CI but not locally. To be investigated.' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		$user_id = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);
		wp_set_current_user( $user_id );

		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertSame( $wp->query_vars['page_id'], null );
	}

	/**
	 * @testdox Tests that the method adds the 'Inter' and 'Cardo' fonts to the theme JSON data.
	 */
	public function test_experimental_filter_theme_json_theme() {
		$theme_json   = $this->createMock( \WP_Theme_JSON_Data::class );
		$initial_data = array(
			'settings' => array(
				'typography' => array(
					'fontFamilies' => array(
						'theme' => array(
							array(
								'fontFamily' => 'Existing Font',
								'name'       => 'Existing Font',
								'slug'       => 'existing-font',
								'fontFace'   => array(
									array(
										'fontFamily' => 'Existing Font',
										'fontStyle'  => 'normal',
										'fontWeight' => '400',
										'src'        => array( 'existing-font.woff2' ),
									),
								),
							),
							array(
								'fontFamily' => 'Unnamed Font',
								'slug'       => 'unnamed-font',
								'fontFace'   => array(
									array(
										'fontFamily' => 'Unnamed Font',
										'fontStyle'  => 'normal',
										'fontWeight' => '400',
										'src'        => array( 'unnamed-font.woff2' ),
									),
								),
							),
						),
					),
				),
			),
		);

		$theme_json->method( 'get_data' )->willReturn( $initial_data );

		$theme_json->expects( $this->once() )
			->method( 'update_with' )
			->with(
				$this->callback(
					function ( $new_data ) {
						$fonts      = $new_data['settings']['typography']['fontFamilies']['theme'];
						$font_names = array_column( $fonts, 'name' );
						return in_array( 'Inter', $font_names, true ) && in_array( 'Cardo', $font_names, true );
					}
				)
			);

		$this->sut->experimental_filter_theme_json_theme( $theme_json );
	}
}
