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
 *
 * @since 10.5.0
 */
class PushTokensDataStore implements WC_Object_Data_Store_Interface {
	const META_KEYS = array(
		'origin',
		'device_uuid',
		'token',
		'platform',
	);

	/**
	 * Registers the push token custom post type.
	 *
	 * @since 10.5.0
	 * @internal
	 */
	public static function register_post_type() {
		register_post_type(
			PushToken::POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'Push Tokens', 'woocommerce' ),
					'singular_name' => __( 'Push Token', 'woocommerce' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array( 'author' ),
				'can_export'         => false,
				'delete_with_user'   => true,
			)
		);
	}

	/**
	 * Returns array of meta keys whose persistence should be managed via
	 * class setters.
	 *
	 * @since 10.5.0
	 * @return array
	 */
	public function get_internal_meta_keys() {
		return array();
	}

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
					)
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

		$meta_map = $this->meta_objects_to_map( $push_token->get_meta_data() );

		if (
			empty( $meta_map['token'] )
			|| empty( $meta_map['platform'] )
			|| empty( $meta_map['origin'] )
			|| (
				empty( $meta_map['device_uuid'] )
				&& PushToken::PLATFORM_BROWSER !== $meta_map['platform']
			)
		) {
			throw new InvalidArgumentException(
				'Can\'t read push token because the push token record is malformed.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		$push_token->set_user_id( (int) $post->post_author );
		$push_token->set_token( $meta_map['token'] );
		$push_token->set_platform( $meta_map['platform'] );
		$push_token->set_device_uuid( $meta_map['device_uuid'] ?? null );
		$push_token->set_origin( $meta_map['origin'] );
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
					)
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
	 * Deletes a push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $args Not used, enforced by WC_Object_Data_Store_Interface.
	 * @return void
	 * @throws InvalidArgumentException If the token can't be deleted.
	 * @throws Exception If the item to delete is not a push token.
	 *
	 * phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	 */
	public function delete( &$push_token, $args = array() ) {
		if ( ! $push_token->can_be_deleted() ) {
			throw new InvalidArgumentException(
				'Can\'t delete push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		$post = get_post( $push_token->get_id() );

		if ( ! $post || PushToken::POST_TYPE !== $post->post_type ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'Push token could not be found.', WP_Http::NOT_FOUND );
		}

		foreach ( static::META_KEYS as $key ) {
			delete_post_meta( $push_token->get_id(), $key );
		}

		wp_delete_post( $push_token->get_id(), true );
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

		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( static::META_KEYS ), '%s' ) );

		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $wpdb->postmeta WHERE post_id = %d and meta_key IN ( $placeholders );",
				$push_token->get_id(),
				...static::META_KEYS
			)
		);
	}

	/**
	 * Add new piece of meta to the given push token.
	 *
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing the meta key and value.
	 * @return int|false Meta ID on success, false on failure.
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

		if ( ! in_array( $meta['meta_key'], static::META_KEYS, true ) ) {
			throw new InvalidArgumentException(
				'Can\'t add meta for push token because the meta key is not valid.',
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
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing the meta key and value.
	 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
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

		if ( ! in_array( $meta['meta_key'], static::META_KEYS, true ) ) {
			throw new InvalidArgumentException(
				'Can\'t update meta for push token because the meta key is not valid.',
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
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing at least the meta key.
	 * @return bool True on success, false on failure.
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

		if ( ! in_array( $meta['meta_key'], static::META_KEYS, true ) ) {
			throw new InvalidArgumentException(
				'Can\'t delete meta for push token because the meta key is not valid.',
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
	 * @since 10.5.0
	 * @param PushToken $push_token An instance of PushToken.
	 * @return null|PushToken
	 * @throws InvalidArgumentException If push token is missing data.
	 */
	public function get_by_token_or_device_id( &$push_token ): ?PushToken {
		if (
			! $push_token->get_user_id()
			|| ! $push_token->get_platform()
			|| ! $push_token->get_origin()
			|| ( ! $push_token->get_token() && ! $push_token->get_device_uuid() )
		) {
			throw new InvalidArgumentException(
				'Can\'t retrieve push token because the push token data provided is invalid.',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				WP_Http::BAD_REQUEST
			);
		}

		global $wpdb;

		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_status = 'private'
					AND post_author = %d
				ORDER BY ID DESC",
				PushToken::POST_TYPE,
				$push_token->get_user_id()
			)
		);

		foreach ( $posts as $post ) {
			$candidate = new PushToken();
			$candidate->set_id( (int) $post->ID );

			try {
				$meta = $candidate->get_meta_data();
			} catch ( Exception $e ) {
				wc_get_logger()->warning(
					'Failed to load meta for push token.',
					array(
						'token_id' => $post->ID,
						'error'    => $e->getMessage(),
					)
				);

				continue;
			}

			$meta_map = $this->meta_objects_to_map( $meta );

			if (
				$meta_map['platform'] === $push_token->get_platform()
				&& $meta_map['origin'] === $push_token->get_origin()
				&& (
					( $push_token->get_token() && $push_token->get_token() === $meta_map['token'] )
					|| ( $push_token->get_device_uuid() && $push_token->get_device_uuid() === $meta_map['device_uuid'] )
				)
			) {
				$push_token->set_id( (int) $post->ID );
				$push_token->set_token( $meta_map['token'] );
				$push_token->set_device_uuid( $meta_map['device_uuid'] );
				return $push_token;
			}
		}

		return null;
	}

	/**
	 * Converts an array of WC_Meta_Data objects to a key-value array.
	 * Sets any missing META_KEYS to null.
	 *
	 * @since 10.5.0
	 * @param array $meta_objects Array of WC_Meta_Data objects from get_meta_data().
	 * @return array Associative array with meta keys as keys.
	 */
	private function meta_objects_to_map( array $meta_objects ): array {
		$meta_map = array_fill_keys( static::META_KEYS, null );

		foreach ( $meta_objects as $meta_object ) {
			$data                     = $meta_object->get_data();
			$meta_map[ $data['key'] ] = $data['value'];
		}

		return $meta_map;
	}
}
