<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Ucp;

use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * Tests for the `/.well-known/ucp` discovery profile.
 */
class UcpDiscoveryTest extends \WC_Unit_Test_Case {
	private const RULE = UcpDiscovery::RULE;

	/**
	 * System under test.
	 *
	 * @var UcpDiscovery
	 */
	private UcpDiscovery $sut;

	/**
	 * Static discovery file path, inside a per-test temp dir.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Set up the system under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut  = new UcpDiscovery();
		$this->file = get_temp_dir() . uniqid( 'wc-ucp-' ) . '/.well-known/ucp';
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $this->file );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Undo the global rewrite state register_feature_hooks() may have touched.
	 */
	public function tearDown(): void {
		global $wp_rewrite;
		unset( $wp_rewrite->extra_rules_top[ self::RULE ] );
		remove_filter( 'query_vars', array( $this->sut, 'add_query_var' ), 0 );
		remove_action( 'parse_request', array( $this->sut, 'maybe_render' ), 0 );
		remove_action( 'admin_init', array( $this->sut, 'maybe_sync_file' ) );
		remove_action( 'admin_notices', array( $this->sut, 'maybe_render_notice' ) );
		if ( is_file( $this->file ) ) {
			wp_delete_file( $this->file );
		}
		parent::tearDown();
	}

	/**
	 * @testdox The profile points at the local UCP REST namespace and lists every capability UcpUtils declares.
	 */
	public function test_profile_shape(): void {
		$profile = $this->sut->get_profile();

		$this->assertSame( array( 'ucp', 'links' ), array_keys( $profile ) );
		$this->assertSame( UcpUtils::VERSION, $profile['ucp']['version'] );
		$this->assertSame( rest_url( 'wc/ucp/v1' ), $profile['ucp']['services']['dev.ucp.shopping'][0]['endpoint'] );
		$declarations = UcpUtils::capability_declarations();
		$this->assertSame( array_keys( $declarations ), array_keys( $profile['ucp']['capabilities'] ) );
		foreach ( $declarations as $capability => $declaration ) {
			$this->assertSame( array( $declaration ), $profile['ucp']['capabilities'][ $capability ] );
		}
		$this->assertSame( home_url( '/.well-known/ucp' ), $profile['links'][0]['href'] );
	}

	/**
	 * @testdox The rewrite rule is registered only when UCP is enabled and pretty permalinks are on; the renderer whenever UCP is enabled.
	 * @testWith ["yes", "/%postname%/", true]
	 *           ["no", "/%postname%/", false]
	 *           ["yes", "", false]
	 *
	 * @param string $enabled   Feature option value.
	 * @param string $structure Permalink structure.
	 * @param bool   $expected  Whether the rule should be registered.
	 */
	public function test_rewrite_rule_registration( string $enabled, string $structure, bool $expected ): void {
		global $wp_rewrite;
		update_option( 'woocommerce_feature_ucp_enabled', $enabled );
		$this->set_permalink_structure( $structure );

		$this->sut->register_feature_hooks();

		$this->assertSame( $expected ? 'index.php?' . UcpDiscovery::QUERY_VAR . '=1' : null, $wp_rewrite->extra_rules_top[ self::RULE ] ?? null );
		$this->assertSame( $expected, in_array( UcpDiscovery::QUERY_VAR, apply_filters( 'query_vars', array() ), true ) );
		$this->assertSame( 'yes' === $enabled ? 0 : false, has_action( 'parse_request', array( $this->sut, 'maybe_render' ) ) );
	}

	/**
	 * @testdox Toggling the UCP feature queues a rewrite rules flush.
	 */
	public function test_feature_toggle_queues_flush(): void {
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );

		$this->sut->maybe_flush_rewrite_rules( 'some_other_feature' );
		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );

		$this->sut->maybe_flush_rewrite_rules( UcpUtils::FEATURE_ID );
		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
	}

	/**
	 * @testdox The static file is written under plain permalinks, removed under pretty ones, and removed when UCP is turned off.
	 */
	public function test_sync_file(): void {
		$this->set_permalink_structure( '' );
		$this->sut->maybe_sync_file();
		$this->assertSame( wp_json_encode( $this->sut->get_profile(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), file_get_contents( $this->file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$this->set_permalink_structure( '/%postname%/' );
		$this->sut->maybe_sync_file();
		$this->assertFileDoesNotExist( $this->file );

		$this->set_permalink_structure( '' );
		$this->sut->maybe_sync_file();
		$this->sut->maybe_flush_rewrite_rules( UcpUtils::FEATURE_ID, false );
		$this->assertFileDoesNotExist( $this->file );
	}

	/**
	 * @testdox The static file is not written for ajax requests or users who cannot manage the store.
	 * @testWith [true, "administrator"]
	 *           [false, "subscriber"]
	 *
	 * @param bool   $ajax Whether the request is an ajax request.
	 * @param string $role Current user role.
	 */
	public function test_sync_file_is_gated( bool $ajax, string $role ): void {
		$this->set_permalink_structure( '' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		add_filter( 'wp_doing_ajax', $ajax ? '__return_true' : '__return_false' );

		$this->sut->maybe_sync_file();

		$this->assertFileDoesNotExist( $this->file );
	}

	/**
	 * @testdox The notice shows when the endpoint is unreachable under plain permalinks or shadowed by a read-only file under pretty ones.
	 * @testWith ["", null, true]
	 *           ["", 420, false]
	 *           ["/%postname%/", 292, true]
	 *           ["/%postname%/", 420, false]
	 *           ["/%postname%/", null, false]
	 *
	 * @param string   $structure Permalink structure.
	 * @param int|null $mode      Static file mode, or null when the file is missing.
	 * @param bool     $expected  Whether the notice should show.
	 */
	public function test_notice( string $structure, ?int $mode, bool $expected ): void {
		$this->set_permalink_structure( $structure );
		if ( null !== $mode ) {
			wp_mkdir_p( dirname( $this->file ) );
			file_put_contents( $this->file, '{}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			chmod( $this->file, $mode ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		}

		ob_start();
		$this->sut->maybe_render_notice();
		$output = (string) ob_get_clean();

		$this->assertSame( $expected, str_contains( $output, 'notice-warning' ) );
	}

	/**
	 * Run maybe_render() for a request, capturing output and treating wp_send_json()'s exit as an exception.
	 *
	 * @param string $matched_rule Value of WP::$matched_rule.
	 * @param string $query_var    Value of the discovery query var.
	 * @param string $uri          Request URI.
	 * @return string|null Captured output, or null when the renderer returned without sending.
	 */
	private function render( string $matched_rule, string $query_var, string $uri = '/?wc_ucp_discovery=1' ): ?string {
		$wp               = new \WP();
		$wp->matched_rule = $matched_rule;
		$wp->query_vars   = array( UcpDiscovery::QUERY_VAR => $query_var );

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI']    = $uri;
		$die_handler               = function () {
			return function () {
				throw new \WPDieException();
			};
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $die_handler );

		ob_start();
		try {
			$this->sut->maybe_render( $wp );
			ob_end_clean();
			return null;
		} catch ( \WPDieException $e ) {
			return (string) ob_get_clean();
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', $die_handler );
		}
	}

	/**
	 * @testdox maybe_render sends the profile as JSON when the rewrite rule matched.
	 */
	public function test_maybe_render_sends_profile(): void {
		$output = $this->render( self::RULE, '1' );

		$this->assertNotNull( $output );
		$this->assertSame( $this->sut->get_profile(), json_decode( (string) $output, true ) );
	}

	/**
	 * @testdox maybe_render sends the profile for a /.well-known/ucp request URI when no rewrite rule matched.
	 */
	public function test_maybe_render_request_uri_fallback(): void {
		$output = $this->render( '', '', '/.well-known/ucp/' );

		$this->assertSame( $this->sut->get_profile(), json_decode( (string) $output, true ) );
	}

	/**
	 * @testdox maybe_render ignores requests where the query var is set but the rewrite rule did not match.
	 * @testWith ["", "1"]
	 *           ["^shop/?$", "1"]
	 *           ["^\\.well-known/ucp/?$", ""]
	 *
	 * @param string $matched_rule Matched rewrite rule.
	 * @param string $query_var    Discovery query var value.
	 */
	public function test_maybe_render_ignores_other_requests( string $matched_rule, string $query_var ): void {
		$this->assertNull( $this->render( $matched_rule, $query_var ) );
	}
}
