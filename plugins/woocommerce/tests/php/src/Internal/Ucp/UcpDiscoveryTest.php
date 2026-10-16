<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Ucp;

use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * Tests for the `/.well-known/ucp` discovery profile.
 */
class UcpDiscoveryTest extends \WC_Unit_Test_Case {
	private const RULE = '^\.well-known/ucp/?$';

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
		global $wp_rewrite, $wp;
		unset( $wp_rewrite->extra_rules_top[ self::RULE ] );
		$wp->public_query_vars = array_diff( $wp->public_query_vars, array( UcpDiscovery::QUERY_VAR ) );
		remove_action( 'template_redirect', array( $this->sut, 'maybe_render' ), 0 );
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
		$this->assertSame(
			array(
				UcpUtils::CAPABILITY_CATALOG_SEARCH => array( UcpUtils::capability_declarations()[ UcpUtils::CAPABILITY_CATALOG_SEARCH ] ),
				UcpUtils::CAPABILITY_CATALOG_LOOKUP => array( UcpUtils::capability_declarations()[ UcpUtils::CAPABILITY_CATALOG_LOOKUP ] ),
			),
			$profile['ucp']['capabilities']
		);
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
		global $wp_rewrite, $wp;
		update_option( 'woocommerce_feature_ucp_enabled', $enabled );
		$this->set_permalink_structure( $structure );

		$this->sut->register_feature_hooks();

		$this->assertSame( $expected, isset( $wp_rewrite->extra_rules_top[ self::RULE ] ) );
		$this->assertSame( $expected, in_array( UcpDiscovery::QUERY_VAR, $wp->public_query_vars, true ) );
		$this->assertSame( $expected ? 0 : false, has_action( 'template_redirect', array( $this->sut, 'maybe_render' ) ) );
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
}
