<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * A silent notification WPCOM requests to check delivery to one push token.
 * The app acknowledges it to WPCOM instead of displaying it.
 *
 * The resource ID is the test ID WPCOM created, so there is no store object to
 * hold the delivery state; it is kept in transients instead.
 *
 * @since 11.3.0
 */
class TestNotification extends Notification {
	/**
	 * The notification type identifier for test notifications.
	 */
	const TYPE = 'store_test';

	/**
	 * Largest test ID accepted, so the ID reads the same in every app language.
	 */
	const MAX_TEST_ID = 2147483647;

	/**
	 * How long delivery state is kept for one test.
	 */
	const STATE_TTL = DAY_IN_SECONDS;

	/**
	 * The push token post ID the test is sent to.
	 *
	 * @var int
	 */
	private int $token_id;

	/**
	 * Creates a new TestNotification instance.
	 *
	 * @param int $resource_id The test ID created by WPCOM.
	 * @param int $token_id    The push token post ID to send to. Set by {@see hydrate()} when rebuilt.
	 *
	 * @throws InvalidArgumentException If the test ID is out of range.
	 *
	 * @since 11.3.0
	 */
	public function __construct( int $resource_id, int $token_id = 0 ) {
		parent::__construct( $resource_id );

		if ( $resource_id > self::MAX_TEST_ID ) {
			throw new InvalidArgumentException( 'Test notification ID is out of range.' );
		}

		$this->token_id = $token_id;
	}

	/**
	 * Restores the token ID from a serialized notification array.
	 *
	 * @param array $data The serialized notification data.
	 *
	 * @throws InvalidArgumentException If the token ID is missing, so a rebuilt test is never sent to every token.
	 *
	 * @since 11.3.0
	 */
	public function hydrate( array $data ): void {
		$token_id = (int) ( $data['token_id'] ?? 0 );

		if ( $token_id <= 0 ) {
			throw new InvalidArgumentException( 'Test notification has no token ID.' );
		}

		$this->token_id = $token_id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return self::TYPE;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return int
	 */
	public function get_target_token_id(): ?int {
		return $this->token_id;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array{token_id: int}
	 */
	public function get_identity_data(): array {
		return array( 'token_id' => $this->token_id );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array{type: string, resource_id: int, token_id: int}
	 */
	public function to_array(): array {
		return array_merge( parent::to_array(), $this->get_identity_data() );
	}

	/**
	 * Returns the WPCOM-ready payload. It has no title or message, because
	 * WPCOM sends this type as a background push the app does not display.
	 *
	 * @return array
	 *
	 * @since 11.3.0
	 */
	public function to_payload(): ?array {
		return array(
			'type'        => $this->get_type(),
			'timestamp'   => $this->get_triggered_timestamp(),
			'resource_id' => $this->get_resource_id(),
			'meta'        => array(
				'test_id'  => $this->get_resource_id(),
				'token_id' => $this->token_id,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * A test checks delivery, so the user's preferences never hold it back.
	 *
	 * @param mixed $pref_value The user's stored preference value, or null.
	 * @return bool
	 */
	public function should_send_to_user( $pref_value ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by the parent signature.
		return true;
	}

	/**
	 * Whether this test ID has been claimed or sent already.
	 *
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public function is_used(): bool {
		return $this->has_meta( NotificationProcessor::CLAIMED_META_KEY )
			|| $this->has_meta( NotificationProcessor::SENT_META_KEY );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function has_meta( string $key ): bool {
		return false !== get_transient( $this->transient_name( $key ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function read_meta( string $key ): string {
		$value = get_transient( $this->transient_name( $key ) );

		return false === $value ? '' : (string) $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string   $key       The meta key.
	 * @param int|null $timestamp Unix timestamp to record. Defaults to the current time.
	 */
	public function write_meta( string $key, ?int $timestamp = null ): void {
		set_transient( $this->transient_name( $key ), (string) ( $timestamp ?? time() ), self::STATE_TTL );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function delete_meta( string $key ): void {
		delete_transient( $this->transient_name( $key ) );
	}

	/**
	 * Builds the transient name holding one piece of delivery state for this test.
	 *
	 * @param string $key The meta key.
	 * @return string
	 */
	private function transient_name( string $key ): string {
		return $key . '_test_' . $this->get_resource_id();
	}
}
