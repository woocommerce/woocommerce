<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the Notification class.
 */
class NotificationTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should return an identifier combining blog ID, type, and resource ID.
	 */
	public function test_get_identifier(): void {
		$notification = $this->getMockBuilder( NewOrderNotification::class )
			->setConstructorArgs( array( 42 ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();

		$this->assertSame( get_current_blog_id() . '_store_order_42', $notification->get_identifier() );
	}

	/**
	 * @testdox Should return notification data as an array.
	 */
	public function test_to_array(): void {
		$notification = $this->getMockBuilder( NewReviewNotification::class )
			->setConstructorArgs( array( 99 ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();

		$result = $notification->to_array();

		$this->assertArrayHasKey( 'type', $result );
		$this->assertSame( 'store_review', $result['type'] );
		$this->assertArrayHasKey( 'resource_id', $result );
		$this->assertSame( 99, $result['resource_id'] );
	}

	/**
	 * @testdox Should throw when resource_id is $resource_id.
	 * @testWith [0]
	 *           [-1]
	 *
	 * @param int $resource_id The invalid resource ID.
	 */
	public function test_throws_for_non_positive_resource_id( int $resource_id ): void {
		$this->expectException( InvalidArgumentException::class );

		new NewOrderNotification( $resource_id );
	}

	/**
	 * @testdox from_array should create correct notification for $type type.
	 * @testWith ["store_order", "Automattic\\WooCommerce\\Internal\\PushNotifications\\Notifications\\NewOrderNotification"]
	 *           ["store_review", "Automattic\\WooCommerce\\Internal\\PushNotifications\\Notifications\\NewReviewNotification"]
	 *           ["store_stock", "Automattic\\WooCommerce\\Internal\\PushNotifications\\Notifications\\StockNotification"]
	 *
	 * @param string $type           The notification type.
	 * @param string $expected_class The expected class name.
	 */
	public function test_from_array_creates_notification( string $type, string $expected_class ): void {
		$notification = Notification::from_array(
			array(
				'type'        => $type,
				'resource_id' => 42,
			)
		);

		$this->assertInstanceOf( $expected_class, $notification );
		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox from_array should throw for an unknown notification type.
	 */
	public function test_from_array_throws_for_unknown_type(): void {
		$this->expectException( InvalidArgumentException::class );

		Notification::from_array(
			array(
				'type'        => 'unknown_type',
				'resource_id' => 1,
			)
		);
	}

	/**
	 * @testdox Should throw when type is missing from array data.
	 */
	public function test_from_array_throws_for_missing_type(): void {
		$this->expectException( InvalidArgumentException::class );

		Notification::from_array(
			array(
				'resource_id' => 1,
			)
		);
	}

	/**
	 * Default `should_send_to_user` should:
	 *  - treat `null` (no stored value) as opt-in,
	 *  - read `enabled` from the array shape today's storage produces,
	 *  - default to `true` when the array shape is missing the `enabled`
	 *    key (so newly-added notification types are opt-in by default),
	 *  - and fall back to a defensive bool cast for unexpected scalars.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_should_send_to_user_default(): array {
		return array(
			'null pref means opt-in by default'         => array( null, true ),
			'array with enabled true'                   => array( array( 'enabled' => true ), true ),
			'array with enabled false'                  => array( array( 'enabled' => false ), false ),
			'array missing enabled defaults to true'    => array( array( 'min_value' => 500 ), true ),
			'empty array defaults to true'              => array( array(), true ),
			'array with truthy enabled (1) is true'     => array( array( 'enabled' => 1 ), true ),
			'array with falsy enabled (0) is false'     => array( array( 'enabled' => 0 ), false ),
			'scalar bool true (defensive fallback)'     => array( true, true ),
			'scalar bool false (defensive fallback)'    => array( false, false ),
			'scalar truthy string (defensive fallback)' => array( '1', true ),
			'scalar empty string (defensive fallback)'  => array( '', false ),
		);
	}

	/**
	 * @testdox Default should_send_to_user with $_dataName returns $expected.
	 * @dataProvider provider_should_send_to_user_default
	 *
	 * @param mixed $pref_value The stored preference value.
	 * @param bool  $expected   The expected decision.
	 */
	public function test_should_send_to_user_default_behavior( $pref_value, bool $expected ): void {
		$notification = $this->getMockBuilder( NewOrderNotification::class )
			->setConstructorArgs( array( 1 ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta', 'delete_meta' ) )
			->getMock();

		$this->assertSame( $expected, $notification->should_send_to_user( $pref_value ) );
	}

	/**
	 * @testdox from_array should call hydrate() on classes that implement it.
	 */
	public function test_from_array_hydrates_extra_fields(): void {
		$notification = Notification::from_array(
			array(
				'type'        => 'store_stock',
				'resource_id' => 42,
				'event_type'  => 'out_of_stock',
			)
		);

		$this->assertInstanceOf( StockNotification::class, $notification );
		$this->assertSame( 'out_of_stock', $notification->get_event_type() );
	}

	/**
	 * @testdox from_array should not break for types without hydrate() when extra data is present.
	 */
	public function test_from_array_ignores_extra_fields_for_types_without_hydrate(): void {
		$notification = Notification::from_array(
			array(
				'type'        => 'store_order',
				'resource_id' => 42,
				'extra'       => 'should be ignored',
			)
		);

		$this->assertInstanceOf( NewOrderNotification::class, $notification );
		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * Action Scheduler matches the safety-net cancel call and the retry dedupe
	 * guard on the exact serialized arguments, and both derive from
	 * get_identity_data(). A subclass that returns anything varying between two
	 * instances of the same notification would stop those matching, which leaves
	 * an uncancelled safety net to re-send and a row per trigger in
	 * wp_actionscheduler_actions.
	 *
	 * @testdox Should return identity data that does not vary with volatile constructor state.
	 * @dataProvider provider_notification_identity_pairs
	 *
	 * @param callable $build              Builds a notification from a volatile value.
	 * @param bool     $has_volatile_state Whether this subclass has volatile state for $build to vary.
	 */
	public function test_get_identity_data_ignores_volatile_state( callable $build, bool $has_volatile_state ): void {
		$one   = $build( 1 );
		$other = $build( 999 );

		if ( $has_volatile_state ) {
			$this->assertNotSame(
				$one->to_array(),
				$other->to_array(),
				'The provider case has to vary the volatile state, or the assertion below proves nothing.'
			);
		}

		$this->assertSame( $one->get_identity_data(), $other->get_identity_data() );
	}

	/**
	 * Action Scheduler matches a scheduled action on its serialized arguments, so
	 * the schedule and cancel paths agreeing is not enough. Both derive from
	 * get_safety_net_args(), so a change there moves them together and stays
	 * green while silently dropping the event type from every stock safety net.
	 * These are the literal arguments that have to be stored.
	 *
	 * @testdox Should build the expected safety-net arguments for each notification subclass.
	 * @dataProvider provider_notification_identity_pairs
	 *
	 * @param callable          $build              Builds a notification from a volatile value.
	 * @param bool              $has_volatile_state Unused here; see the identity data test.
	 * @param array<int, mixed> $expected_args      The safety-net arguments this subclass must produce.
	 */
	public function test_get_safety_net_args( callable $build, bool $has_volatile_state, array $expected_args ): void {
		$this->assertSame( $expected_args, $build( 1 )->get_safety_net_args() );
	}

	/**
	 * @testdox Should have an identity data case for every notification subclass.
	 */
	public function test_identity_data_provider_covers_every_subclass(): void {
		$this->assertEqualsCanonicalizing(
			array_keys( Notification::NOTIFICATION_CLASSES ),
			array_keys( $this->provider_notification_identity_pairs() ),
			'A new Notification subclass needs a case in the provider, so its identity data is checked too.'
		);
	}

	/**
	 * One entry per Notification subclass: a callable that builds the same
	 * notification from a volatile value, a flag saying whether the subclass has
	 * any volatile state for the callable to vary, and the exact safety-net
	 * arguments Action Scheduler has to store for it.
	 *
	 * @return array<string, array{callable, bool, array<int, mixed>}>
	 */
	public function provider_notification_identity_pairs(): array {
		return array(
			'store_order'  => array( fn() => new NewOrderNotification( 42 ), false, array( 'store_order', 42 ) ),
			'store_review' => array( fn() => new NewReviewNotification( 42 ), false, array( 'store_review', 42 ) ),
			'store_stock'  => array( fn( int $volatile ) => new StockNotification( 42, StockNotification::EVENT_LOW_STOCK, $volatile ), true, array( 'store_stock', 42, array( 'event_type' => StockNotification::EVENT_LOW_STOCK ) ) ),
		);
	}
}
