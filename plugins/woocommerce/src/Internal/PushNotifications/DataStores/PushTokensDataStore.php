<?php
/**
 * PushTokensDataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\DataStores;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenInvalidDataException;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenNotFoundException;
use Exception;
use WC_Data_Exception;
use WP_Http;
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
	 * @param array $data Token data with keys: user_id, token, platform, device_uuid (optional), origin.
	 * @throws PushTokenInvalidDataException If the token data is invalid.
	 * @throws WC_Data_Exception If the token creation fails.
	 * @return PushToken The created push token with ID set.
	 */
	public function create( array $data ): PushToken {
		$push_token = new PushToken( $data );

		if ( ! $push_token->can_be_created() ) {
			throw new PushTokenInvalidDataException(
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
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new WC_Data_Exception(
				(string) $id->get_error_code(),
				$id->get_error_message(),
				WP_Http::INTERNAL_SERVER_ERROR
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$push_token->set_id( $id );

		return $push_token;
	}

	/**
	 * Gets post representing a push token.
	 *
	 * @since 10.5.0
	 * @param int $id The push token ID.
	 * @throws PushTokenInvalidDataException If the ID is invalid.
	 * @throws PushTokenNotFoundException If the token can't be found.
	 * @return PushToken The populated push token.
	 */
	public function read( int $id ): PushToken {
		$push_token = new PushToken( array( 'id' => $id ) );
		$post       = get_post( $push_token->get_id() );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			throw new PushTokenNotFoundException();
		}

		$meta = $this->build_meta_array_from_database( (int) $push_token->get_id() );

		if (
			empty( $meta['token'] )
			|| empty( $meta['platform'] )
			|| empty( $meta['origin'] )
			|| (
				empty( $meta['device_uuid'] )
				&& PushToken::PLATFORM_BROWSER !== $meta['platform']
			)
		) {
			throw new PushTokenInvalidDataException(
				'Can\'t read push token because the push token record is malformed.'
			);
		}

		$push_token->set_user_id( (int) $post->post_author );
		$push_token->set_token( $meta['token'] );
		$push_token->set_device_uuid( $meta['device_uuid'] ?? null );
		$push_token->set_platform( $meta['platform'] );
		$push_token->set_origin( $meta['origin'] );

		return $push_token;
	}

	/**
	 * Updates a post representing the push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token The push token to update.
	 * @throws PushTokenInvalidDataException If the token can't be updated.
	 * @throws WC_Data_Exception If the token update fails.
	 * @return bool True on success.
	 */
	public function update( PushToken $push_token ): bool {
		if ( ! $push_token->can_be_updated() ) {
			throw new PushTokenInvalidDataException(
				'Can\'t update push token because the push token data provided is invalid.'
			);
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
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new WC_Data_Exception(
				(string) $result->get_error_code(),
				$result->get_error_message(),
				WP_Http::INTERNAL_SERVER_ERROR
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( null === $push_token->get_device_uuid() ) {
			delete_post_meta( (int) $push_token->get_id(), 'device_uuid' );
		}

		return true;
	}

	/**
	 * Deletes a push token.
	 *
	 * @since 10.5.0
	 * @param int $id The push token ID.
	 * @throws PushTokenNotFoundException If the token can't be found.
	 * @return bool True on success.
	 */
	public function delete( int $id ): bool {
		$post = get_post( $id );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			throw new PushTokenNotFoundException();
		}

		return (bool) wp_delete_post( (int) $id, true );
	}

	/**
	 * Find tokens for this user and platform that match either the token
	 * or device UUID. We check the token value to avoid creating a duplicate.
	 * We check the device UUID value because only one token should be issued
	 * per device, therefore if we already have one then we can update it to
	 * avoid creating a duplicate.
	 *
	 * @since 10.5.0
	 * @param array $data Token data with keys: user_id, platform, origin, token (optional), device_uuid (optional).
	 * @return null|PushToken
	 * @throws PushTokenInvalidDataException If push token is missing data.
	 */
	public function get_by_token_or_device_id( array $data ): ?PushToken {
		$user_id     = $data['user_id'] ?? null;
		$platform    = $data['platform'] ?? null;
		$origin      = $data['origin'] ?? null;
		$token       = $data['token'] ?? null;
		$device_uuid = $data['device_uuid'] ?? null;

		if (
			! $user_id
			|| ! $platform
			|| ! $origin
			|| (
				/**
				 * Platforms iOS and Android require token OR device UUID.
				 */
				PushToken::PLATFORM_BROWSER !== $platform
				&& ! $token
				&& ! $device_uuid
			)
			|| (
				/**
				 * Browsers don't have device UUIDs, so require token.
				 */
				PushToken::PLATFORM_BROWSER === $platform
				&& ! $token
			)
		) {
			throw new PushTokenInvalidDataException(
				'Can\'t retrieve push token because the push token data provided is invalid.'
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => PushToken::POST_TYPE,
				'post_status'    => 'private',
				'author'         => $user_id,
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		/**
		 * Typehint for PHPStan, specifies these are IDs and not instances of
		 * WP_Post.
		 *
		 * @var int[] $post_ids
		 */
		$post_ids = $query->posts;

		if ( empty( $post_ids ) ) {
			return null;
		}

		update_meta_cache( 'post', $post_ids );

		foreach ( $post_ids as $post_id ) {
			try {
				$meta = $this->build_meta_array_from_database( $post_id );
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
				$meta['platform'] === $platform
				&& $meta['origin'] === $origin
				&& (
					( $token && $token === $meta['token'] )
					|| ( $device_uuid && $device_uuid === $meta['device_uuid'] )
				)
			) {
				return new PushToken(
					array(
						'id'          => $post_id,
						'user_id'     => $user_id,
						'token'       => $meta['token'],
						'device_uuid' => $meta['device_uuid'] ?? null,
						'platform'    => $meta['platform'],
						'origin'      => $meta['origin'],
					)
				);
			}
		}

		return null;
	}

	/**
	 * Returns an associative array of post meta as key => value pairs for the
	 * keys defined in SUPPORTED_META; missing keys return null.
	 *
	 * @since 10.5.0
	 * @param int $id The push token ID.
	 * @return array
	 */
	private function build_meta_array_from_database( int $id ): array {
		$meta        = (array) get_post_meta( $id );
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
	 */
	private function build_meta_array_from_token( PushToken $push_token ) {
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
