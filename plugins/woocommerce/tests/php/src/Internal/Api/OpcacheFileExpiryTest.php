<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Api\Infrastructure\Main;
use Automattic\WooCommerce\Internal\Api\OpcacheFileExpiry;
use Automattic\WooCommerce\Internal\Api\QueryCache;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WC_Unit_Test_Case;

/**
 * Tests for {@see OpcacheFileExpiry} — TTL-based deletion of cached files.
 */
class OpcacheFileExpiryTest extends WC_Unit_Test_Case {

	/**
	 * Track temp dirs for removal in tearDown.
	 *
	 * @var string[]
	 */
	private array $temp_dirs_to_clean = array();

	/**
	 * Skip on PHP < 8.1 because OpcacheFileExpiry imports from the GraphQL
	 * stack autoloaded only on PHP 8.1+.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'OpcacheFileExpiry tests require PHP 8.1+.' );
		}
	}

	/**
	 * Clean up filters, temp dirs, and scheduled actions between tests.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_graphql_opcache_cache_dir' );
		foreach ( $this->temp_dirs_to_clean as $dir ) {
			$this->rrmdir( $dir );
		}
		$this->temp_dirs_to_clean = array();
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( OpcacheFileExpiry::ACTION_HOOK );
		}
		$this->enable_or_disable_feature( false );
		$this->set_plugin_endpoints_registered( false );
		parent::tearDown();
	}

	/**
	 * Enable or disable the dual_code_graphql_api feature via its underlying option.
	 *
	 * @param bool $enable True to enable, false to disable.
	 */
	private function enable_or_disable_feature( bool $enable ): void {
		update_option(
			wc_get_container()->get( FeaturesController::class )->feature_enable_option_name( 'dual_code_graphql_api' ),
			$enable ? 'yes' : 'no'
		);
	}

	/**
	 * Set the static plugin endpoint registration flag in Main, normally
	 * set by Main::register_graphql_endpoint().
	 *
	 * @param bool $registered The value to set.
	 */
	private function set_plugin_endpoints_registered( bool $registered ): void {
		$property = ( new \ReflectionClass( Main::class ) )->getProperty( 'plugin_endpoints_registered' );
		$property->setAccessible( true );
		$property->setValue( null, $registered );
	}

	/**
	 * @testdox delete_expired_files removes only files whose mtime is older than the TTL.
	 */
	public function test_delete_expired_files_removes_only_expired(): void {
		$dir = $this->register_temp_cache_dir();

		$fresh   = $dir . '/' . str_repeat( 'a', 64 ) . '.php';
		$expired = $dir . '/' . str_repeat( 'b', 64 ) . '.php';
		file_put_contents( $fresh, '<?php return array();' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $expired, '<?php return array();' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		touch( $expired, time() - QueryCache::get_cache_ttl() - 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch

		$deleted = OpcacheFileExpiry::delete_expired_files();

		$this->assertSame( 1, $deleted );
		$this->assertFileExists( $fresh );
		$this->assertFileDoesNotExist( $expired );
	}

	/**
	 * @testdox delete_expired_files returns 0 when the cache directory does not exist.
	 */
	public function test_delete_expired_files_returns_zero_when_dir_missing(): void {
		add_filter(
			'woocommerce_graphql_opcache_cache_dir',
			static function () {
				return '/nonexistent/path/that/does/not/exist';
			}
		);

		$this->assertSame( 0, OpcacheFileExpiry::delete_expired_files() );
	}

	/**
	 * @testdox handle_cleanup_action does not reschedule when no dual-API endpoint is active.
	 */
	public function test_handle_cleanup_action_does_not_reschedule_when_no_endpoint_is_active(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			$this->markTestSkipped( 'Action Scheduler is not available.' );
		}

		as_unschedule_all_actions( OpcacheFileExpiry::ACTION_HOOK );
		$this->enable_or_disable_feature( false );

		OpcacheFileExpiry::handle_cleanup_action();

		$this->assertFalse(
			as_has_scheduled_action( OpcacheFileExpiry::ACTION_HOOK, array(), OpcacheFileExpiry::ACTION_GROUP ),
			'The cleanup should not be rescheduled when nothing writes to the cache.'
		);
	}

	/**
	 * @testdox handle_cleanup_action reschedules when the feature is off but a plugin endpoint is registered.
	 */
	public function test_handle_cleanup_action_reschedules_when_plugin_endpoints_exist(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			$this->markTestSkipped( 'Action Scheduler is not available.' );
		}

		$this->enable_or_disable_feature( false );
		$this->set_plugin_endpoints_registered( true );

		OpcacheFileExpiry::handle_cleanup_action();

		$this->assertTrue(
			as_has_scheduled_action( OpcacheFileExpiry::ACTION_HOOK, array(), OpcacheFileExpiry::ACTION_GROUP ),
			'The cleanup should be rescheduled while plugin endpoints use the cache, regardless of the feature flag.'
		);
	}

	/**
	 * @testdox handle_cleanup_action reschedules when the feature is enabled.
	 */
	public function test_handle_cleanup_action_reschedules_when_feature_is_on(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			$this->markTestSkipped( 'Action Scheduler is not available.' );
		}

		$this->enable_or_disable_feature( true );

		OpcacheFileExpiry::handle_cleanup_action();

		$this->assertTrue(
			as_has_scheduled_action( OpcacheFileExpiry::ACTION_HOOK, array(), OpcacheFileExpiry::ACTION_GROUP )
		);
	}

	/**
	 * Create a per-test cache directory, point the OPcache filter at it, and
	 * register it for cleanup in tearDown.
	 */
	private function register_temp_cache_dir(): string {
		$dir = sys_get_temp_dir() . '/wc-graphql-cleanup-test-' . bin2hex( random_bytes( 6 ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		mkdir( $dir, 0700, true );

		add_filter(
			'woocommerce_graphql_opcache_cache_dir',
			static function () use ( $dir ) {
				return $dir;
			}
		);

		$this->temp_dirs_to_clean[] = $dir;
		return $dir;
	}

	/**
	 * Recursively remove a directory tree.
	 *
	 * @param string $dir Path to remove.
	 */
	private function rrmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		foreach ( scandir( $dir ) as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$path = $dir . '/' . $entry;
			if ( is_dir( $path ) ) {
				$this->rrmdir( $path );
			} else {
				wp_delete_file( $path );
			}
		}
		rmdir( $dir );
		// phpcs:enable
	}
}
