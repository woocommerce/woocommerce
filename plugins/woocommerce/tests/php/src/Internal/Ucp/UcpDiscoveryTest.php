<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Ucp;

use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

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
	 * Set up the system under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new UcpDiscovery();
	}

	/**
	 * Undo the global rewrite state register_feature_hooks() may have touched.
	 */
	public function tearDown(): void {
		global $wp_rewrite;
		unset( $wp_rewrite->extra_rules_top[ self::RULE ] );
		remove_filter( 'query_vars', array( $this->sut, 'add_query_var' ), 0 );
		remove_action( 'parse_request', array( $this->sut, 'maybe_render' ), 0 );
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );
		parent::tearDown();
	}

	/**
	 * @testdox The profile points at the local UCP REST namespace and lists every capability Utils declares.
	 */
	public function test_profile_shape(): void {
		$profile = $this->sut->get_profile();

		$this->assertSame( array( 'ucp', 'links' ), array_keys( $profile ) );
		$this->assertSame( Utils::VERSION, $profile['ucp']['version'] );
		$this->assertSame( rest_url( 'wc/ucp/v1' ), $profile['ucp']['services']['dev.ucp.shopping'][0]['endpoint'] );
		$declarations = Utils::capability_declarations();
		$this->assertSame( array_keys( $declarations ), array_keys( $profile['ucp']['capabilities'] ) );
		foreach ( $declarations as $capability => $declaration ) {
			$this->assertSame( array( $declaration ), $profile['ucp']['capabilities'][ $capability ] );
		}
		$this->assertSame( home_url( '/.well-known/ucp' ), $profile['links'][0]['href'] );
	}

	/**
	 * @testdox The rewrite rule is registered only when UCP is enabled and pretty permalinks are on.
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
		$this->assertSame( $expected ? 0 : false, has_action( 'parse_request', array( $this->sut, 'maybe_render' ) ) );
	}

	/**
	 * @testdox register_feature_hooks queues a rewrite flush when the stored rules disagree with the feature flag.
	 * @testWith ["yes", "present", false]
	 *           ["yes", "missing", true]
	 *           ["yes", "empty", false]
	 *           ["no", "present", true]
	 *
	 * @param string $enabled  Feature option value.
	 * @param string $stored   Whether the stored rules contain the rule, lack it, or are empty.
	 * @param bool   $expected Whether a flush should be queued.
	 */
	public function test_register_feature_hooks_heals_stale_rewrite_rules( string $enabled, string $stored, bool $expected ): void {
		update_option( 'woocommerce_feature_ucp_enabled', $enabled );
		$this->set_permalink_structure( '/%postname%/' );
		$rules = array( '^shop/?$' => 'index.php?post_type=product' );
		if ( 'present' === $stored ) {
			$rules[ self::RULE ] = 'index.php?' . UcpDiscovery::QUERY_VAR . '=1';
		}
		update_option( 'rewrite_rules', 'empty' === $stored ? array() : $rules );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );

		$this->sut->register_feature_hooks();

		$this->assertSame( $expected ? 'yes' : 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
	}

	/**
	 * @testdox Toggling the UCP feature queues a rewrite rules flush.
	 */
	public function test_feature_toggle_queues_flush(): void {
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );

		$this->sut->maybe_flush_rewrite_rules( 'some_other_feature' );
		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );

		$this->sut->maybe_flush_rewrite_rules( Utils::FEATURE_ID );
		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
	}

	/**
	 * @testdox serve_status reads the permalink setting and the static file: no file is fine with pretty permalinks and missing with Plain; a file must be WooCommerce's and current.
	 * @testWith ["/%postname%/", null, "ok"]
	 *           ["/%postname%/", "current", "ok"]
	 *           ["/%postname%/", "foreign", "file_in_the_way"]
	 *           ["/%postname%/", "outdated", "file_outdated"]
	 *           ["", null, "no_file"]
	 *           ["", "current", "ok"]
	 *           ["", "current, other formatting", "ok"]
	 *           ["", "foreign", "file_in_the_way"]
	 *           ["", "not json", "file_in_the_way"]
	 *           ["", "cut short", "file_in_the_way"]
	 *           ["", "outdated", "file_outdated"]
	 *
	 * @param string      $structure Permalink structure.
	 * @param string|null $file      The static file, or null for none.
	 * @param string      $expected  Expected status.
	 */
	public function test_serve_status( string $structure, ?string $file, string $expected ): void {
		$path = get_temp_dir() . uniqid( 'wc-ucp-' ) . '/.well-known/ucp';
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $path );
		$this->set_permalink_structure( $structure );
		if ( null !== $file ) {
			$ours            = $this->sut->get_profile();
			$ours['links'][] = array( 'rel' => UcpDiscovery::LINK_REL );
			$old             = $ours;

			$old['ucp']['services']['dev.ucp.shopping'][0]['endpoint'] = 'http://old.example/wp-json/wc/ucp/v1';

			$current  = (string) wp_json_encode( $ours, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			$contents = array(
				'current'                   => $current,
				'current, other formatting' => (string) wp_json_encode( $ours ),
				'foreign'                   => (string) wp_json_encode( $this->sut->get_profile() ),
				'outdated'                  => (string) wp_json_encode( $old ),
				'not json'                  => 'not json',
				'cut short'                 => substr( $current, 0, (int) ( strlen( $current ) / 2 ) ),
			);
			wp_mkdir_p( dirname( $path ) );
			file_put_contents( $path, $contents[ $file ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		try {
			$this->assertSame( $expected, UcpDiscovery::serve_status() );
		} finally {
			self::remove_ucp_file( $path );
		}
	}

	/**
	 * @testdox serve_status counts a file it can't read as in the way, even when it holds WooCommerce's current profile.
	 */
	public function test_serve_status_unreadable_file(): void {
		$path = get_temp_dir() . uniqid( 'wc-ucp-' ) . '/.well-known/ucp';
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $path );
		$this->set_permalink_structure( '' );

		$profile            = $this->sut->get_profile();
		$profile['links'][] = array( 'rel' => UcpDiscovery::LINK_REL );
		wp_mkdir_p( dirname( $path ) );
		file_put_contents( $path, (string) wp_json_encode( $profile ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		chmod( $path, 0000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod

		try {
			if ( is_readable( $path ) ) {
				$this->markTestSkipped( 'The test runs as a user who can read any file (root).' );
			}
			$this->assertSame( 'file_in_the_way', UcpDiscovery::serve_status() );
		} finally {
			chmod( $path, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			self::remove_ucp_file( $path );
		}
	}

	/**
	 * Run maybe_render() for a request, capturing output and treating wp_send_json()'s exit as an exception.
	 *
	 * @param string $matched_rule Value of WP::$matched_rule.
	 * @param string $query_var    Value of the discovery query var.
	 * @return string|null Captured output, or null when the renderer returned without sending.
	 */
	private function render( string $matched_rule, string $query_var ): ?string {
		$wp               = new \WP();
		$wp->matched_rule = $matched_rule;
		$wp->query_vars   = array( UcpDiscovery::QUERY_VAR => $query_var );

		$_SERVER['REQUEST_METHOD'] = 'GET';
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

	/**
	 * Delete a test discovery file and the two folders created for it.
	 *
	 * @param string $path File path under a `wc-ucp-*` temp folder.
	 */
	private static function remove_ucp_file( string $path ): void {
		wp_delete_file( $path );
		foreach ( array( dirname( $path ), dirname( $path, 2 ) ) as $dir ) {
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			}
		}
	}
}
