<?php
/**
 * SystemStatusGetTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests for the system-status-get ability.
 */
class SystemStatusGetTest extends \WC_Unit_Test_Case {

	/**
	 * Administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

	/**
	 * Create the administrator once for the class.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Start each test from an empty abilities registry, so the abilities register again.
	 */
	public function setUp(): void {
		global $wp_actions;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		// The Abilities API registry refuses to initialize before `init` has fired.
		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

		wc_get_container()->get( AbilitiesLoader::class )->init();
		self::reset_registries();
		wp_set_current_user( self::$administrator_id );
	}

	/**
	 * Leave the registries and the init count as the next test expects them.
	 */
	public function tearDown(): void {
		global $wp_actions;

		try {
			self::reset_registries();
		} finally {
			parent::tearDown();
		}

		if ( null !== $this->original_init_count ) {
			$wp_actions['init'] = $this->original_init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Drop every registered ability, so the next read registers them again.
	 */
	private static function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'] );
	}

	/**
	 * Run the ability as the current user; execute() also validates the output against the output schema.
	 *
	 * @return mixed|\WP_Error
	 */
	private function get_status() {
		$ability = wp_get_ability( 'woocommerce/system-status-get' );
		$this->assertNotNull( $ability, 'The ability should be registered.' );

		return $ability->execute( array() );
	}

	/**
	 * @testdox Should return every allowlisted field, valid against the output schema.
	 */
	public function test_returns_the_allowlisted_fields(): void {
		$result = $this->get_status();

		$this->assertNotWPError( $result );
		$this->assertSame(
			array( 'woocommerce_version', 'woocommerce_db_version', 'order_storage', 'environment', 'active_plugins', 'inactive_plugins', 'mu_plugins', 'dropins', 'enabled_features', 'theme', 'pages', 'security', 'post_type_counts' ),
			array_keys( $result['system_status'] )
		);
		$this->assertSame( WC()->version, $result['system_status']['woocommerce_version'] );
		$this->assertIsObject( $result['system_status']['post_type_counts'], 'Post type counts should encode as a JSON object.' );
	}

	/**
	 * @testdox Should leave out log paths, server paths, the database prefix, table names and URLs.
	 */
	public function test_output_has_no_server_details(): void {
		global $wpdb;

		set_transient(
			'wc_system_status_active_plugins',
			array(
				array(
					'plugin'            => 'canary/canary.php',
					'name'              => 'Canary',
					'version'           => '1.0.0',
					'version_latest'    => '1.0.0',
					'url'               => 'https://canary.example/plugin',
					'author_name'       => 'Canary Co',
					'author_url'        => 'https://canary.example/author',
					'network_activated' => false,
				),
			)
		);

		$result = $this->get_status();
		$json   = (string) wp_json_encode( $result, JSON_UNESCAPED_SLASHES );

		$this->assertNotWPError( $result );
		$this->assertContains( 'Canary', array_column( $result['system_status']['active_plugins'], 'name' ) );
		$this->assertStringNotContainsString( 'canary.example', $json, 'Plugin and author URLs should be left out.' );
		$this->assertStringNotContainsString( 'http', $json, 'No URL should reach the output.' );
		$this->assertStringNotContainsString( untrailingslashit( ABSPATH ), $json, 'Server paths should be left out.' );
		$this->assertStringNotContainsString( untrailingslashit( WC_LOG_DIR ), $json, 'The log directory should be left out.' );
		// Field names such as wp_cron share the default wp_ prefix, so only values are checked for it.
		$values = array();
		array_walk_recursive(
			$result,
			static function ( $value ) use ( &$values ) {
				if ( is_string( $value ) ) {
					$values[] = $value;
				}
			}
		);
		$this->assertStringNotContainsString( $wpdb->prefix, implode( "\n", $values ), 'The database prefix and table names should be left out.' );
	}

	/**
	 * @testdox Should report a published checkout page with its block as set, existing, visible and complete, without its ID.
	 */
	public function test_pages_report_setup_without_ids(): void {
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:woocommerce/checkout /-->',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $page_id );

		$result = $this->get_status();
		$pages  = array_column( $result['system_status']['pages'], null, 'name' );

		$this->assertArrayHasKey( 'Checkout', $pages );
		$this->assertTrue( $pages['Checkout']['set'] );
		$this->assertTrue( $pages['Checkout']['exists'] );
		$this->assertTrue( $pages['Checkout']['visible'] );
		$this->assertTrue( $pages['Checkout']['block_present'] );
		$this->assertFalse( $pages['Checkout']['shortcode_present'] );
		$this->assertArrayNotHasKey( 'page_id', $pages['Checkout'] );
	}

	/**
	 * @testdox Should leave out the parent theme fields when the theme is not a child theme.
	 */
	public function test_theme_without_parent_omits_parent_fields(): void {
		$this->assertFalse( is_child_theme(), 'The test site should run a theme without a parent.' );

		$theme = $this->get_status()['system_status']['theme'];

		$this->assertSame( wp_get_theme()->get( 'Name' ), $theme['name'] );
		$this->assertArrayNotHasKey( 'parent_name', $theme );
		$this->assertArrayNotHasKey( 'parent_version', $theme );
	}

	/**
	 * @testdox Should report outdated templates when the theme overrides a template with an older version.
	 */
	public function test_reports_outdated_template_override(): void {
		$theme_dir = get_temp_dir() . 'wc-system-status-' . wp_generate_password( 8, false );
		wp_mkdir_p( $theme_dir . '/woocommerce/cart' );
		file_put_contents( $theme_dir . '/woocommerce/cart/cart.php', "<?php\n/**\n * @version 1.0.0\n */\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$use_theme_dir = static fn() => $theme_dir;
		add_filter( 'stylesheet_directory', $use_theme_dir );
		wp_set_template_globals();

		try {
			$theme = $this->get_status()['system_status']['theme'];
		} finally {
			remove_filter( 'stylesheet_directory', $use_theme_dir );
			wp_set_template_globals();
			unlink( $theme_dir . '/woocommerce/cart/cart.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			rmdir( $theme_dir . '/woocommerce/cart' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $theme_dir . '/woocommerce' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $theme_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}

		$this->assertTrue( $theme['has_outdated_templates'] );
	}

	/**
	 * @testdox Should report outdated templates when a plugin points WooCommerce to an older template through wc_get_template.
	 */
	public function test_reports_outdated_template_from_filter(): void {
		$dir = get_temp_dir() . 'wc-system-status-' . wp_generate_password( 8, false );
		wp_mkdir_p( $dir );
		file_put_contents( $dir . '/cart.php', "<?php\n/**\n * @version 1.0.0\n */\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$redirect = static fn( $located, $name ) => 'cart/cart.php' === $name ? $dir . '/cart.php' : $located;
		add_filter( 'wc_get_template', $redirect, 10, 2 );

		try {
			$theme = $this->get_status()['system_status']['theme'];
		} finally {
			remove_filter( 'wc_get_template', $redirect, 10 );
			unlink( $dir . '/cart.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}

		$this->assertTrue( $theme['has_outdated_templates'] );
	}

	/**
	 * @testdox Should give the same hide_errors answer whether or not WordPress turned display_errors off for a JSON request.
	 */
	public function test_hide_errors_does_not_depend_on_request_type(): void {
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) ) {
			$this->markTestSkipped( 'Needs WP_DEBUG and WP_DEBUG_DISPLAY on, where the request type used to change the answer.' );
		}

		$original = (string) ini_get( 'display_errors' );

		try {
			ini_set( 'display_errors', '1' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
			$html = $this->get_status()['system_status']['security']['hide_errors'];
			ini_set( 'display_errors', '0' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
			$json = $this->get_status()['system_status']['security']['hide_errors'];
		} finally {
			ini_set( 'display_errors', $original ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
		}

		$this->assertSame( $html, $json );
		$this->assertFalse( $json, 'WP_DEBUG_DISPLAY shows errors to visitors.' );
	}

	/**
	 * @testdox Should report plugin headers as plain text, without HTML or links.
	 */
	public function test_plugin_headers_are_plain_text(): void {
		set_transient(
			'wc_system_status_active_plugins',
			array(
				array(
					'plugin'      => 'anchor/anchor.php',
					'name'        => '<code>Canary</code> Plugin',
					'version'     => '1.0 <a href="https://anchor.example/v">notes</a>',
					'author_name' => '<a href="https://anchor.example/">Anchor &amp; Co</a>',
				),
			)
		);
		set_transient(
			'wc_system_status_inactive_plugins',
			array(
				array(
					'plugin'      => 'raw/raw.php',
					'name'        => 'Raw',
					'version'     => '2.0',
					'author_name' => '<script>x</script>Raw Author',
				),
				array(
					'plugin'      => 'encoded/encoded.php',
					'name'        => 'Encoded',
					'version'     => '3.0',
					'author_name' => '&lt;a href="https://anchor.example/e"&gt;Encoded Author&lt;/a&gt;',
				),
			)
		);

		$status   = $this->get_status()['system_status'];
		$active   = array_column( $status['active_plugins'], null, 'plugin' )['anchor/anchor.php'];
		$inactive = array_column( $status['inactive_plugins'], null, 'plugin' );
		$json     = (string) wp_json_encode( array( $active, $inactive ), JSON_UNESCAPED_SLASHES );

		$this->assertSame( 'Canary Plugin', $active['name'] );
		$this->assertSame( '1.0 notes', $active['version'] );
		$this->assertSame( 'Anchor & Co', $active['author'] );
		$this->assertSame( 'Raw Author', $inactive['raw/raw.php']['author'] );
		$this->assertSame( 'Encoded Author', $inactive['encoded/encoded.php']['author'] );
		$this->assertStringNotContainsString( 'anchor.example', $json );
		$this->assertStringNotContainsString( '<', $json );
	}

	/**
	 * @testdox Should skip a wc_get_template result that is not a path.
	 */
	public function test_ignores_non_string_template_filter_result(): void {
		$return_array = static fn() => array();
		add_filter( 'wc_get_template', $return_array );

		try {
			$result = $this->get_status();
		} finally {
			remove_filter( 'wc_get_template', $return_array );
		}

		$this->assertNotWPError( $result );
		$this->assertFalse( $result['system_status']['theme']['has_outdated_templates'] );
	}

	/**
	 * @testdox Should report the parent theme name and version for a child theme.
	 */
	public function test_child_theme_reports_parent(): void {
		global $wp_theme_directories;

		$root           = get_temp_dir() . 'wc-system-status-themes-' . wp_generate_password( 8, false );
		$original_theme = get_stylesheet();
		$original_dirs  = $wp_theme_directories;
		$theme_headers  = array(
			'wc-canary-parent' => "/*\nTheme Name: Canary Parent\nVersion: 2.0.0\n*/",
			'wc-canary-child'  => "/*\nTheme Name: Canary Child\nTemplate: wc-canary-parent\nVersion: 1.0.0\n*/",
		);
		foreach ( $theme_headers as $slug => $header ) {
			wp_mkdir_p( "$root/$slug" );
			file_put_contents( "$root/$slug/style.css", $header ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( "$root/$slug/index.php", '<?php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		register_theme_directory( $root );
		wp_clean_themes_cache();

		try {
			switch_theme( 'wc-canary-child' );
			$theme = $this->get_status()['system_status']['theme'];
		} finally {
			switch_theme( $original_theme );
			$wp_theme_directories = $original_dirs; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wp_clean_themes_cache();
			foreach ( array_keys( $theme_headers ) as $slug ) {
				unlink( "$root/$slug/style.css" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( "$root/$slug/index.php" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				rmdir( "$root/$slug" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			}
			rmdir( $root ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}

		$this->assertSame( 'Canary Child', $theme['name'] );
		$this->assertSame( 'Canary Parent', $theme['parent_name'] );
		$this->assertSame( '2.0.0', $theme['parent_version'] );
	}
}
