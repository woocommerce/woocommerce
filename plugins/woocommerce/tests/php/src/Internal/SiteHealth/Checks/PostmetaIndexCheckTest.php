<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\PostmetaIndexCheck;
use WC_Unit_Test_Case;

/**
 * PostmetaIndexCheckTest class.
 */
class PostmetaIndexCheckTest extends WC_Unit_Test_Case {

	/**
	 * Whether the meta_value index existed before the test class ran.
	 *
	 * @var bool
	 */
	private bool $index_existed_before;

	/**
	 * Drop the meta_value index if present so every test starts from a known
	 * baseline (index absent).  Record whether it was present so tearDown can
	 * restore the original state.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		// Detect current state.
		$rows                       = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'meta_value'" );
		$this->index_existed_before = ! empty( $rows );

		// Unconditionally drop so each test starts with the index absent.
		if ( $this->index_existed_before ) {
			$wpdb->query( "ALTER TABLE {$wpdb->postmeta} DROP INDEX meta_value" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}

	/**
	 * Restore the meta_value index to the state it was in before the test ran.
	 */
	public function tearDown(): void {
		global $wpdb;

		// Check current state to avoid errors from duplicate / missing index.
		$rows         = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'meta_value'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$index_exists = ! empty( $rows );

		if ( $this->index_existed_before && ! $index_exists ) {
			$wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_value (meta_value(191))" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} elseif ( ! $this->index_existed_before && $index_exists ) {
			$wpdb->query( "ALTER TABLE {$wpdb->postmeta} DROP INDEX meta_value" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		parent::tearDown();
	}

	/**
	 * The check returns "recommended" when the meta_value index is missing.
	 */
	public function test_returns_recommended_when_meta_value_index_missing(): void {
		// setUp() already dropped the index — no extra DDL needed.
		$check  = new PostmetaIndexCheck();
		$result = $check->run();
		$this->assertSame( 'recommended', $result['status'] );
	}

	/**
	 * The check returns "good" when the meta_value index is present.
	 */
	public function test_returns_good_when_meta_value_index_present(): void {
		global $wpdb;
		$wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_value (meta_value(191))" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$check  = new PostmetaIndexCheck();
		$result = $check->run();
		$this->assertSame( 'good', $result['status'] );
	}

	/**
	 * The result array contains all required keys and the expected test identifier.
	 */
	public function test_result_contains_required_keys(): void {
		global $wpdb;
		$wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_value (meta_value(191))" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$check  = new PostmetaIndexCheck();
		$result = $check->run();
		foreach ( array( 'label', 'status', 'badge', 'description', 'actions', 'test' ) as $key ) {
			$this->assertArrayHasKey( $key, $result );
		}
		$this->assertSame( 'woocommerce_postmeta_meta_value_index', $result['test'] );
	}

	/**
	 * The enabled filter suppresses the check, returning an empty result.
	 */
	public function test_enabled_filter_suppresses_result(): void {
		add_filter( 'woocommerce_site_health_check_postmeta_meta_value_index_enabled', '__return_false' );
		$check  = new PostmetaIndexCheck();
		$result = $check->run();
		$this->assertSame( array(), $result );
		remove_filter( 'woocommerce_site_health_check_postmeta_meta_value_index_enabled', '__return_false' );
	}

	/**
	 * The result filter can override the check's result.
	 */
	public function test_result_filter_can_override_result(): void {
		global $wpdb;
		$wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_value (meta_value(191))" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$callback = function ( array $result ) {
			$result['status'] = 'critical';
			return $result;
		};
		add_filter( 'woocommerce_site_health_check_postmeta_meta_value_index_result', $callback );
		$check  = new PostmetaIndexCheck();
		$result = $check->run();
		$this->assertSame( 'critical', $result['status'] );
		remove_filter( 'woocommerce_site_health_check_postmeta_meta_value_index_result', $callback );
	}

	/**
	 * get_id() returns the prefixed check identifier.
	 */
	public function test_get_id_returns_prefixed_id(): void {
		$check = new PostmetaIndexCheck();
		$this->assertSame( 'woocommerce_postmeta_meta_value_index', $check->get_id() );
	}

	/**
	 * The check is not asynchronous.
	 */
	public function test_is_not_async(): void {
		$check = new PostmetaIndexCheck();
		$this->assertFalse( $check->is_async() );
	}
}
