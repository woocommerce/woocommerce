<?php
/**
 * PushTokensDataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\DataStores;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Exception;
use InvalidArgumentException;
use WP_Http;

/**
 * Data store class for push tokens.
 *
 * @since 10.5.0
 */
class PushTokensDataStore {
	/**
	 * Creates a post representing the push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be created.
	 * @throws Exception If the token creation fails.
	 */
	public function create( &$push_token ) {
		if ( ! $push_token->can_be_created() ) {
			throw new InvalidArgumentException(
				'Can\'t create push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		$id = wp_insert_post(
			array(
				'post_author' => $push_token->get_user_id(),
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array_filter(
					array(
						'platform'    => $push_token->get_platform(),
						'token'       => $push_token->get_token(),
						'device_uuid' => $push_token->get_device_uuid(),
						'origin'      => $push_token->get_origin(),
					),
					static fn ( $value, $key ) => 'device_uuid' !== $key || null !== $value,
					ARRAY_FILTER_USE_BOTH
				),
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( $id->get_error_message(), WP_Http::INTERNAL_SERVER_ERROR );
		}

		$push_token->set_id( $id );
	}

	/**
	 * Gets post representing a push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be read.
	 * @throws Exception If the token can't be found.
	 * @throws Exception If the ID doesn't belong to a push token.
	 */
	public function read( &$push_token ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException(
				'Can\'t read push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		$post = get_post( $push_token->get_id() );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'Push token could not be found.', WP_Http::NOT_FOUND );
		}

		$meta = $this->read_meta( $push_token );

		if (
			empty( $meta['token'] )
			|| empty( $meta['platform'] )
			|| empty( $meta['origin'] )
			|| (
				empty( $meta['device_uuid'] )
				&& PushToken::PLATFORM_BROWSER !== $meta['platform']
			)
		) {
			throw new InvalidArgumentException(
				'Can\'t read push token because the push token record is malformed.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		$push_token->set_user_id( (int) $post->post_author );
		$push_token->set_token( $meta['token'] );
		$push_token->set_platform( $meta['platform'] );
		$push_token->set_device_uuid( $meta['device_uuid'] ?? null );
		$push_token->set_origin( $meta['origin'] );
	}

	/**
	 * Updates a post representing the push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be updated.
	 * @throws Exception If the token update fails.
	 */
	public function update( &$push_token ) {
		if ( ! $push_token->can_be_updated() ) {
			throw new InvalidArgumentException(
				'Can\'t update push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		$post = get_post( $push_token->get_id() );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'Push token could not be found.', WP_Http::NOT_FOUND );
		}

		$result = wp_update_post(
			array(
				'ID'          => $push_token->get_id(),
				'post_author' => $push_token->get_user_id(),
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array_filter(
					array(
						'platform'    => $push_token->get_platform(),
						'token'       => $push_token->get_token(),
						'device_uuid' => $push_token->get_device_uuid(),
						'origin'      => $push_token->get_origin(),
					),
					static fn ( $value, $key ) => 'device_uuid' !== $key || null !== $value,
					ARRAY_FILTER_USE_BOTH
				),
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( $result->get_error_message(), WP_Http::INTERNAL_SERVER_ERROR );
		}

		if ( null === $push_token->get_device_uuid() ) {
			delete_post_meta( $push_token->get_id(), 'device_uuid' );
		}
	}

	/**
	 * Returns an array of post meta objects as key => value pairs.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @return array
	 * @throws InvalidArgumentException If the token can't be read.
	 */
	public function read_meta( &$push_token ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException(
				'Can\'t read meta for push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		return array_map(
			static fn ( $meta ) => $meta[0] ?? $meta,
			get_post_meta( $push_token->get_id() )
		);
	}
}
