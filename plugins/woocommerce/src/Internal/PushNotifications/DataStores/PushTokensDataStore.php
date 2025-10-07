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
use WC_Object_Data_Store_Interface;

/**
 * Data store class for push tokens.
 */
class PushTokensDataStore implements WC_Object_Data_Store_Interface {
	/**
	 * Creates a post representing the push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be created.
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
				'post_status' => 'publish',
				'meta_input'  => array(
					'platform'    => $push_token->get_platform(),
					'token'       => $push_token->get_token(),
					'device_uuid' => $push_token->get_device_uuid(),
				),
			)
		);

		$push_token->set_id( $id );
	}

	/**
	 * Gets post representing a push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be read.
	 * @throws Exception If the token can't be found.
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

		if ( ! $post ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'Push token could not be found.', WP_Http::NOT_FOUND );
		}

		$meta = $this->read_meta( $push_token );

		$push_token->set_user_id( (int) $post->post_author );
		$push_token->set_token( $meta['token'] ?? null );
		$push_token->set_platform( $meta['platform'] ?? null );
		$push_token->set_device_uuid( $meta['device_uuid'] ?? null );
	}

	/**
	 * Updates a post representing the push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be updated.
	 */
	public function update( &$push_token ) {
		if ( ! $push_token->can_be_updated() ) {
			throw new InvalidArgumentException(
				'Can\'t update push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		wp_update_post(
			array(
				'ID'          => $push_token->get_id(),
				'post_author' => $push_token->get_user_id(),
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'publish',
				'meta_input'  => array(
					'platform'    => $push_token->get_platform(),
					'token'       => $push_token->get_token(),
					'device_uuid' => $push_token->get_device_uuid(),
				),
			)
		);
	}

	/**
	 * Deletes a push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $args Not used, enforced by interface.
	 * @return void
	 * @throws InvalidArgumentException If the token can't be deleted.
	 */
	public function delete( &$push_token, $args = array() ) {
		if ( ! $push_token->can_be_deleted() ) {
			throw new InvalidArgumentException(
				'Can\'t delete push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		wp_delete_post( $push_token->get_id() );
	}

	/**
	 * Returns an array of post meta objects as key => value pairs.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 * @return array
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
			fn ( $meta ) => $meta[0] ?? $meta,
			get_post_meta( $push_token->get_id() )
		);
	}

	/**
	 * Add new piece of meta to the given push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing the meta key and value.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 */
	public function add_meta( &$push_token, $meta ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException(
				'Can\'t add meta for push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		if ( empty( $meta['meta_key'] ) ) {
			throw new InvalidArgumentException(
				'Can\'t add meta for push token because the meta key was not provided.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		return add_post_meta(
			$push_token->get_id(),
			$meta['meta_key'],
			$meta['meta_value'] ?? null,
			true
		);
	}

	/**
	 * Updates meta for the given push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing the meta key and value.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 */
	public function update_meta( &$push_token, $meta ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException(
				'Can\'t update meta for push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		if ( empty( $meta['meta_key'] ) ) {
			throw new InvalidArgumentException(
				'Can\'t update meta for push token because the meta key was not provided.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		return update_post_meta(
			$push_token->get_id(),
			$meta['meta_key'],
			$meta['meta_value'] ?? null
		);
	}

	/**
	 * Deletes meta for the given push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing at least the meta key.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 */
	public function delete_meta( &$push_token, $meta ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException(
				'Can\'t delete meta for push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		if ( empty( $meta['meta_key'] ) ) {
			throw new InvalidArgumentException(
				'Can\'t delete meta for push token because the meta key was not provided.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		return delete_post_meta( $push_token->get_id(), $meta['meta_key'] );
	}

	/**
	 * Find tokens for this user and platform that match either the token
	 * or device UUID. We check the token value to avoid creating a duplicate.
	 * We check the device UUID value because only one token should be issued
	 * per device, therefore if we already have one then we can update it to
	 * avoid creating a duplicate.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @return null|PushToken
	 * @throws InvalidArgumentException If push token is missing data.
	 */
	public function get_by_token_or_device_id( &$push_token ): ?PushToken {
		if ( ! $push_token->get_user_id() || ! $push_token->get_platform() ) {
			throw new InvalidArgumentException(
				sprintf(
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					'Can\'t retrieve push token using token or device UUID because %s was not provided.',
					$push_token->get_platform() ? 'user ID' : 'platform',
				),
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$push_token_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					posts.ID,
					posts.post_author,
					platform_meta.meta_value as platform,
					token_meta.meta_value as token,
					device_uuid_meta.meta_value as device_uuid
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS platform_meta
					ON posts.ID = platform_meta.post_id
					AND platform_meta.meta_key = 'platform'
				INNER JOIN {$wpdb->postmeta} AS token_meta
					ON posts.ID = token_meta.post_id
					AND token_meta.meta_key = 'token'
				INNER JOIN {$wpdb->postmeta} AS device_uuid_meta
					ON posts.ID = device_uuid_meta.post_id
					AND device_uuid_meta.meta_key = 'device_uuid'
				WHERE posts.post_type = %s
				AND posts.post_author = %d
				AND platform_meta.meta_value = %s
				AND (
					token_meta.meta_value = %s
					OR device_uuid_meta.meta_value = %s
				)
				LIMIT 1",
				PushToken::POST_TYPE,
				$push_token->get_user_id(),
				$push_token->get_platform(),
				$push_token->get_token(),
				$push_token->get_device_uuid()
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $push_token_data ) {
			return null;
		}

		$push_token->set_id( (int) $push_token_data->ID );
		$push_token->set_user_id( (int) $push_token_data->post_author );
		$push_token->set_token( $push_token_data->token );
		$push_token->set_device_uuid( $push_token_data->device_uuid );
		$push_token->set_platform( $push_token_data->platform );

		return $push_token;
	}
}
