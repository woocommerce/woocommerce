<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\SiteHealthChecks;
use WC_Unit_Test_Case;

/**
 * Integration tests for SiteHealthChecks: verifies all 15 checks are registered
 * and every callback returns a well-formed result array.
 */
class SiteHealthChecksIntegrationTest extends WC_Unit_Test_Case {

	private const EXPECTED_DIRECT = array(
		'woocommerce_pending_db_update',
		'woocommerce_required_pages',
		'woocommerce_hpos_status',
		'woocommerce_legacy_rest_api',
		'woocommerce_https',
		'woocommerce_payment_gateway',
		'woocommerce_postmeta_meta_value_index',
	);

	private const EXPECTED_ASYNC = array(
		'woocommerce_action_scheduler_overdue',
		'woocommerce_action_scheduler_total',
		'woocommerce_autoloaded_options',
		'woocommerce_sessions_table',
		'woocommerce_product_lookup_table',
		'woocommerce_webhook_failures',
		'woocommerce_outdated_templates',
		'woocommerce_cart_fragments_sitewide',
	);

	public function setUp(): void {
		parent::setUp();
		wc_get_container()->get( SiteHealthChecks::class )->register();
	}

	public function test_all_expected_direct_tests_registered() {
		$tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		foreach ( self::EXPECTED_DIRECT as $id ) {
			$this->assertArrayHasKey( $id, $tests['direct'], "Missing direct test {$id}" );
			$this->assertArrayHasKey( 'label', $tests['direct'][ $id ] );
			$this->assertIsCallable( $tests['direct'][ $id ]['test'] );
		}
	}

	public function test_all_expected_async_tests_registered() {
		$tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		foreach ( self::EXPECTED_ASYNC as $id ) {
			$this->assertArrayHasKey( $id, $tests['async'], "Missing async test {$id}" );
			$this->assertTrue( $tests['async'][ $id ]['async'] ?? false );
			$this->assertIsCallable( $tests['async'][ $id ]['async_direct_test'] );
		}
	}

	public function test_every_callback_returns_valid_result_shape() {
		$tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$valid_statuses = array( 'good', 'recommended', 'critical' );

		foreach ( array_merge( $tests['direct'], $tests['async'] ) as $entry ) {
			$callback = $entry['async_direct_test'] ?? $entry['test'] ?? null;
			if ( ! is_callable( $callback ) ) {
				continue;
			}
			$result = call_user_func( $callback );
			if ( empty( $result ) ) {
				continue; // disabled by filter — allowed.
			}
			$this->assertContains( $result['status'], $valid_statuses );
			foreach ( array( 'label', 'badge', 'description', 'test' ) as $key ) {
				$this->assertArrayHasKey( $key, $result );
			}
		}
	}
}
