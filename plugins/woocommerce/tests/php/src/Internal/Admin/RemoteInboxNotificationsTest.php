<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use Automattic\WooCommerce\Internal\Admin\Events;
use Automattic\WooCommerce\Internal\Admin\RemoteInboxNotifications;
use WC_Unit_Test_Case;

/**
 * Tests for the marketplace suggestions gating of remote inbox notifications.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\RemoteInboxNotifications
 * @covers \Automattic\WooCommerce\Internal\Admin\Events::is_remote_inbox_notifications_enabled
 */
class RemoteInboxNotificationsTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// The engine is initialized by the plugin bootstrap; detach its hooks so
		// each test observes initialization from scratch. No manual restore is
		// needed: parent::tearDown() puts the full wp_filter map back to the
		// snapshot WP_UnitTestCase_Base::_backup_hooks() took before the first
		// test, which reattaches these hooks and discards any callbacks that
		// RemoteInboxNotificationsEngine::init() registered during the test.
		$this->detach_engine_hooks();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME );

		parent::tearDown();
	}

	/**
	 * @testdox The feature loader should initialize the engine only when marketplace suggestions are enabled.
	 * @dataProvider marketplace_suggestions_option_provider
	 *
	 * @param string|null $option_value   Marketplace suggestions option value, or null when absent.
	 * @param bool        $expected_value Whether the engine is expected to be initialized.
	 */
	public function test_engine_initialization_respects_marketplace_suggestions_option( ?string $option_value, bool $expected_value ): void {
		$this->set_marketplace_suggestions_option( $option_value );

		new RemoteInboxNotifications();

		$this->assert_engine_initialized( $expected_value );
	}

	/**
	 * @testdox The daily cron gate should agree with the marketplace suggestions option.
	 * @dataProvider marketplace_suggestions_option_provider
	 *
	 * Events::is_remote_inbox_notifications_enabled() duplicates the option check the feature
	 * loader performs; running both gates against the same provider pins them to the same behavior.
	 *
	 * @param string|null $option_value   Marketplace suggestions option value, or null when absent.
	 * @param bool        $expected_value Expected gate value.
	 */
	public function test_daily_cron_gate_respects_marketplace_suggestions_option( ?string $option_value, bool $expected_value ): void {
		$this->set_marketplace_suggestions_option( $option_value );

		$this->assertSame(
			$expected_value,
			$this->is_daily_cron_gate_enabled(),
			'The daily cron gate should match the configured marketplace suggestions option.'
		);
	}

	/**
	 * Values for the marketplace suggestions option tests.
	 *
	 * @return array<string, array{string|null, bool}>
	 */
	public function marketplace_suggestions_option_provider(): array {
		return array(
			'option absent'    => array( null, true ),
			'option enabled'   => array( 'yes', true ),
			'option disabled'  => array( 'no', false ),
			'unexpected value' => array( 'invalid', false ),
			'truthy non-yes'   => array( '1', false ),
		);
	}

	/**
	 * Set or delete the marketplace suggestions option.
	 *
	 * @param string|null $value Option value, or null to delete the option.
	 */
	private function set_marketplace_suggestions_option( ?string $value ): void {
		if ( null === $value ) {
			delete_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME );
		} else {
			update_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME, $value );
		}
	}

	/**
	 * Engine hooks observed by these tests, keyed by hook name.
	 *
	 * @return array<string, array{class-string, string}>
	 */
	private function get_engine_hooks(): array {
		return array(
			'init'       => array( RemoteInboxNotificationsEngine::class, 'on_init' ),
			'admin_init' => array( RemoteInboxNotificationsEngine::class, 'on_admin_init' ),
		);
	}

	/**
	 * Detach the engine hooks at their currently registered priorities.
	 */
	private function detach_engine_hooks(): void {
		foreach ( $this->get_engine_hooks() as $hook => $callback ) {
			$priority = has_action( $hook, $callback );

			if ( false !== $priority ) {
				remove_action( $hook, $callback, $priority );
			}
		}
	}

	/**
	 * Assert whether the engine registered its hooks.
	 *
	 * @param bool $expected Whether the engine is expected to be initialized.
	 */
	private function assert_engine_initialized( bool $expected ): void {
		foreach ( $this->get_engine_hooks() as $hook => $callback ) {
			// has_action() returns the priority, which is the falsy int 0 for the init hook.
			$is_registered = false !== has_action( $hook, $callback );

			$this->assertSame(
				$expected,
				$is_registered,
				$expected
					? "The engine should register its {$hook} hook."
					: "The engine should not register its {$hook} hook."
			);
		}
	}

	/**
	 * Invoke the protected daily cron gate.
	 */
	private function is_daily_cron_gate_enabled(): bool {
		$method = new \ReflectionMethod( Events::class, 'is_remote_inbox_notifications_enabled' );
		$method->setAccessible( true );

		return $method->invoke( Events::instance() );
	}
}
