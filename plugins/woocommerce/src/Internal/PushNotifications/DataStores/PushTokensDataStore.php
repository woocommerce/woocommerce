<?php
/**
 * PushTokensDataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\DataStores;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenNotFoundException;
use Exception;
use InvalidArgumentException;
use WP_Query;

/**
 * Data store class for push tokens.
 *
 * @since 10.5.0
 */
class PushTokensDataStore {
	const SUPPORTED_META = array(
		'origin',
		'device_uuid',
		'token',
		'platform',
	);

	/**
	 * Creates a post representing the push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be created.
	 * @throws Exception If the token creation fails.
	 * @return void
	 */
	public function create( PushToken &$push_token ): void {
		if ( ! $push_token->can_be_created() ) {
			throw new InvalidArgumentException(
				'Can\'t create push token because the push token data provided is invalid.'
			);
		}

		$id = wp_insert_post(
			array(
				'post_author' => (int) $push_token->get_user_id(),
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => $this->build_meta_array_from_token( $push_token ),
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( $id->get_error_message() );
		}

		$push_token->set_id( $id );
	}

	/**
	 * Gets post representing a push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be read.
	 * @throws PushTokenNotFoundException If the token can't be found.
	 * @return void
	 */
	public function read( PushToken &$push_token ): void {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException(
				'Can\'t read push token because the push token data provided is invalid.'
			);
		}

		$post = get_post( $push_token->get_id() );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			throw new PushTokenNotFoundException( 'Push token could not be found.' );
		}

		$meta = $this->build_meta_array_from_database( $push_token );

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
				'Can\'t read push token because the push token record is malformed.'
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
	 * @throws PushTokenNotFoundException If the token can't be found.
	 * @throws Exception If the token update fails.
	 * @return void
	 */
	public function update( PushToken &$push_token ): void {
		if ( ! $push_token->can_be_updated() ) {
			throw new InvalidArgumentException(
				'Can\'t update push token because the push token data provided is invalid.'
			);
		}

		$post = get_post( $push_token->get_id() );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PushTokenNotFoundException( 'Push token could not be found.' );
		}

		$result = wp_update_post(
			array(
				'ID'          => (int) $push_token->get_id(),
				'post_author' => (int) $push_token->get_user_id(),
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => $this->build_meta_array_from_token( $push_token ),
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( $result->get_error_message() );
		}

		if ( null === $push_token->get_device_uuid() ) {
			delete_post_meta( (int) $push_token->get_id(), 'device_uuid' );
		}
	}

	/**
	 * Deletes a push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be deleted.
	 * @throws PushTokenNotFoundException If the token can't be found.
	 * @return void
	 */
	public function delete( PushToken &$push_token ): void {
		if ( ! $push_token->can_be_deleted() ) {
			throw new InvalidArgumentException(
				'Can\'t delete push token because the push token data provided is invalid.'
			);
		}

		$post = get_post( $push_token->get_id() );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			throw new PushTokenNotFoundException( 'Push token could not be found.' );
		}

		wp_delete_post( (int) $push_token->get_id(), true );
	}

	/**
	 * Find tokens for this user and platform that match either the token
	 * or device UUID. We check the token value to avoid creating a duplicate.
	 * We check the device UUID value because only one token should be issued
	 * per device, therefore if we already have one then we can update it to
	 * avoid creating a duplicate.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @return null|PushToken
	 * @throws InvalidArgumentException If push token is missing data.
	 */
	public function get_by_token_or_device_id( PushToken &$push_token ): ?PushToken {
		if (
			! $push_token->get_user_id()
			|| ! $push_token->get_platform()
			|| ! $push_token->get_origin()
			|| (
				/**
				 * Platforms iOS and Android require token OR device UUID.
				 */
				$push_token->get_platform() !== PushToken::PLATFORM_BROWSER
				&& ! $push_token->get_token()
				&& ! $push_token->get_device_uuid()
			)
			|| (
				/**
				 * Browsers don't have device UUIDs, so require token.
				 */
				$push_token->get_platform() === PushToken::PLATFORM_BROWSER
				&& ! $push_token->get_token()
			)
		) {
			throw new InvalidArgumentException(
				'Can\'t retrieve push token because the push token data provided is invalid.'
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => PushToken::POST_TYPE,
				'post_status'    => 'private',
				'author'         => $push_token->get_user_id(),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$post_ids = $query->posts;

		if ( empty( $post_ids ) ) {
			return null;
		}

		update_meta_cache( 'post', $post_ids );

		foreach ( $post_ids as $post_id ) {
			$candidate = new PushToken();
			$candidate->set_id( $post_id );

			try {
				$meta = $this->build_meta_array_from_database( $candidate );
			} catch ( Exception $e ) {
				wc_get_logger()->warning(
					'Failed to load meta for push token.',
					array(
						'token_id' => $post_id,
						'error'    => $e->getMessage(),
					)
				);

				continue;
			}

			if (
				$meta['platform'] === $push_token->get_platform()
				&& $meta['origin'] === $push_token->get_origin()
				&& (
					( $push_token->get_token() && $push_token->get_token() === $meta['token'] )
					|| ( $push_token->get_device_uuid() && $push_token->get_device_uuid() === $meta['device_uuid'] )
				)
			) {
				$push_token->set_id( $post_id );
				$push_token->set_token( $meta['token'] );
				$push_token->set_device_uuid( $meta['device_uuid'] );
				return $push_token;
			}
		}

		return null;
	}

	/**
	 * Returns an associative array of post meta as key => value pairs for the
	 * keys defined in SUPPORTED_META; missing keys return null.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @return array
	 * @throws InvalidArgumentException If the token can't be read.
	 */
	private function build_meta_array_from_database( PushToken &$push_token ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException(
				'Can\'t read meta for push token because the push token data provided is invalid.'
			);
		}

		$meta        = (array) get_post_meta( (int) $push_token->get_id() );
		$meta_by_key = (array) array_combine( static::SUPPORTED_META, static::SUPPORTED_META );

		foreach ( static::SUPPORTED_META as $key ) {
			if ( ! isset( $meta[ $key ] ) ) {
				$meta_by_key[ $key ] = null;
			} elseif ( is_array( $meta[ $key ] ) ) {
				$meta_by_key[ $key ] = $meta[ $key ][0];
			} else {
				$meta_by_key[ $key ] = $meta[ $key ];
			}
		}

		return $meta_by_key;
	}

	/**
	 * Returns an associative array of post meta as key => value pairs, built
	 * using push token properties.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @return array
	 * @throws InvalidArgumentException If the token can't be read.
	 */
	private function build_meta_array_from_token( PushToken &$push_token ) {
		return array_filter(
			array(
				'platform'    => $push_token->get_platform(),
				'token'       => $push_token->get_token(),
				'device_uuid' => $push_token->get_device_uuid(),
				'origin'      => $push_token->get_origin(),
			)
		);
	}
}
