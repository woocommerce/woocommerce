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
			array( 'woocommerce_version', 'woocommerce_db_version', 'order_storage', 'active_plugins', 'inactive_plugins', 'mu_plugins', 'dropins', 'enabled_features', 'theme', 'pages', 'security', 'post_type_counts' ),
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
		$this->assertStringNotContainsString( $wpdb->prefix, $json, 'The database prefix and table names should be left out.' );
	}

	/**
	 * @testdox Should report a published checkout page as set, existing and visible, without its ID.
	 */
	public function test_pages_report_setup_without_ids(): void {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $page_id );

		$result = $this->get_status();
		$pages  = array_column( $result['system_status']['pages'], null, 'name' );

		$this->assertArrayHasKey( 'Checkout', $pages );
		$this->assertTrue( $pages['Checkout']['set'] );
		$this->assertTrue( $pages['Checkout']['exists'] );
		$this->assertTrue( $pages['Checkout']['visible'] );
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
}
