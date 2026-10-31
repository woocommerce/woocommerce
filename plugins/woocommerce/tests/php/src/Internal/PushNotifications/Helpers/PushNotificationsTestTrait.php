<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\NewOrderNotificationTrigger;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

/**
 * Shared test helpers for the PushNotifications module.
 *
 * Mocks the Jetpack connection state and resets the memoized enablement flag on
 * the container's `PushNotifications` instance — the two things every
 * push-notifications-related controller test needs in setUp. Also builds the
 * push tokens data store mock that PendingNotificationStore tests need, and
 * backdates token registrations for the tests that depend on token age.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
trait PushNotificationsTestTrait {
	/**
	 * @var JetpackConnectionManager|MockObject|null
	 */
	protected $jetpack_connection_manager_mock;

	/**
	 * Mocks the JetpackConnectionManager so its `is_connected()` returns the
	 * supplied value, and resets the PushNotifications enablement cache so
	 * `should_be_enabled()` re-evaluates against the new mock.
	 *
	 * @param bool $is_connected Whether the manager should report Jetpack is connected.
	 */
	protected function mock_jetpack_connection_manager_is_connected( bool $is_connected = true ) {
		$this->jetpack_connection_manager_mock = $this
			->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected' ) )
			->getMock();

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $this->jetpack_connection_manager_mock )
		);

		$this->jetpack_connection_manager_mock
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( $is_connected );

		$this->reset_push_notifications_cache();
	}

	/**
	 * Resets the cached enablement state on the container's PushNotifications
	 * instance so subsequent `should_be_enabled()` calls re-evaluate.
	 */
	protected function reset_push_notifications_cache() {
		$push_notifications = wc_get_container()->get( PushNotifications::class );
		$reflection         = new ReflectionClass( $push_notifications );
		$property           = $reflection->getProperty( 'enabled' );

		$property->setAccessible( true );
		$property->setValue( $push_notifications, null );
	}

	/**
	 * Creates a push tokens data store whose has_tokens() returns $has_tokens.
	 *
	 * @param bool $has_tokens What has_tokens() should report.
	 * @return PushTokensDataStore|MockObject
	 */
	protected function create_data_store_with_tokens( bool $has_tokens ) {
		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->method( 'has_tokens' )->willReturn( $has_tokens );

		return $data_store;
	}

	/**
	 * Removes new order trigger callbacks that WooCommerce registered when it booted.
	 * Push notifications are off in tests, so the off-state marker is registered and
	 * would otherwise mark orders before the code under test does.
	 *
	 * @return void
	 */
	protected function remove_trigger_callbacks_registered_at_boot(): void {
		global $wp_filter;

		foreach ( array( 'woocommerce_new_order', 'woocommerce_order_status_changed' ) as $hook ) {
			$callbacks = isset( $wp_filter[ $hook ] ) ? $wp_filter[ $hook ]->callbacks : array();

			foreach ( $callbacks as $priority => $by_priority ) {
				foreach ( $by_priority as $callback ) {
					if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof NewOrderNotificationTrigger ) {
						remove_action( $hook, $callback['function'], $priority );
					}
				}
			}
		}
	}

	/**
	 * Sets the date a token was first registered and the date it was last registered.
	 *
	 * @param int         $post_id        The token's post ID.
	 * @param string      $created        The first registration date, as GMT.
	 * @param string      $modified       The last registration date, as GMT.
	 * @param string|null $modified_local The last registration date in site time, defaulting to $modified.
	 */
	protected function set_token_dates( int $post_id, string $created, string $modified, ?string $modified_local = null ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'         => $created,
				'post_date_gmt'     => $created,
				'post_modified'     => $modified_local ?? $modified,
				'post_modified_gmt' => $modified,
			),
			array( 'ID' => $post_id )
		);

		clean_post_cache( $post_id );
	}
}
