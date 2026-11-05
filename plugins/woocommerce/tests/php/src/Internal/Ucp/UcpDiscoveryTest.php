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
}
