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
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
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
	/**
	 * In-memory cache for get_tokens_for_roles() results, keyed by the
	 * comma-joined role list (with optional pagination suffix). Avoids
	 * repeated DB queries within the same PHP request.
	 *
	 * @var array<string, PushToken[]|array{tokens: PushToken[], total: int, total_pages: int}>
	 */
	private array $tokens_by_roles_cache = array();

	/**
	 * Meta key holding the GMT datetime of the last successful send to WPCOM.
	 *
	 * Deliberately absent from `build_meta_array_from_token()`: it is written
	 * only by `record_last_send()`, so an unrelated token update (e.g. the app
	 * re-registering with a new locale) cannot clobber it.
	 */
	const LAST_SEND_AT_META_KEY = 'last_send_at_gmt';

	const SUPPORTED_META = array(
		'origin',
		'device_uuid',
		'token',
		'platform',
		'device_locale',
		'metadata',
		self::LAST_SEND_AT_META_KEY,
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

		/**
		 * These meta items were added after the ability to store tokens, so may
		 * not be available for older tokens. Use sensible defaults.
		 */
		$push_token->set_device_locale( $meta['device_locale'] ?? PushToken::DEFAULT_DEVICE_LOCALE );
		$push_token->set_metadata( $meta['metadata'] ?? array() );
		$push_token->set_last_send_at_gmt( $meta[ self::LAST_SEND_AT_META_KEY ] ?? null );

		/**
		 * Both timestamps come from the post record rather than meta, because
		 * WordPress already maintains them. See {@see PushToken::$last_confirmed_at_gmt}
		 * for what `post_modified_gmt` means for a push token.
		 */
		$push_token->set_created_at_gmt( $post->post_date_gmt );
		$push_token->set_last_confirmed_at_gmt( $post->post_modified_gmt );

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
						'source'   => PushNotifications::FEATURE_NAME,
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
						'id'            => $post_id,
						'user_id'       => $user_id,
						'token'         => $meta['token'],
						'device_uuid'   => $meta['device_uuid'] ?? null,
						'platform'      => $meta['platform'],
						'origin'        => $meta['origin'],
						/**
						 * These meta items were added after the ability to store
						 * tokens, so may not be available for older tokens. Use
						 * sensible defaults.
						 */
						'device_locale' => $meta['device_locale'] ?? PushToken::DEFAULT_DEVICE_LOCALE,
						'metadata'      => $meta['metadata'] ?? array(),
						'last_send_at_gmt'  => $meta[ self::LAST_SEND_AT_META_KEY ] ?? null,
					)
				);
			}
		}

		return null;
	}

	/**
	 * Returns push tokens belonging to users with the given roles.
	 *
	 * When called without pagination parameters, returns all tokens as a
	 * flat array (cached per-request). When $page and $per_page are
	 * provided, returns a paginated result with total counts.
	 *
	 * The eligible-user lookup is restricted to users that actually own
	 * push tokens, so the role check runs against a handful of IDs instead
	 * of scanning every user's capabilities meta, which does not scale on
	 * sites with very large user tables.
	 *
	 * @param string[] $roles    The roles to query tokens for.
	 * @param int|null $page     Optional page number (1-based).
	 * @param int|null $per_page Optional number of tokens per page.
	 * @return PushToken[]|array{tokens: PushToken[], total: int, total_pages: int}
	 *
	 * @since 10.7.0
	 */
	public function get_tokens_for_roles( array $roles, ?int $page = null, ?int $per_page = null ) {
		$paginate  = null !== $page && null !== $per_page;
		$cache_key = $paginate ? implode( ',', $roles ) . ":$page:$per_page" : implode( ',', $roles );

		$empty_result = $paginate
			? array(
				'tokens'      => array(),
				'total'       => 0,
				'total_pages' => 0,
			)
			: array();

		if ( empty( $roles ) ) {
			return $empty_result;
		}

		if ( isset( $this->tokens_by_roles_cache[ $cache_key ] ) ) {
			return $this->tokens_by_roles_cache[ $cache_key ];
		}

		global $wpdb;

		// Exactly this SQL to leverage the wp_posts type_status_author index; low token cardinality keeps it fast at any store size.
		$users_with_tokens = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'private'",
				PushToken::POST_TYPE
			)
		);

		// An empty include must short-circuit: WP_User_Query would ignore it and scan all users by role.
		$user_ids = empty( $users_with_tokens ) ? array() : get_users(
			array(
				'role__in' => $roles,
				'fields'   => 'ID',
				'include'  => $users_with_tokens,
			)
		);

		if ( empty( $user_ids ) ) {
			$this->tokens_by_roles_cache[ $cache_key ] = $empty_result;
			return $this->tokens_by_roles_cache[ $cache_key ];
		}

		$query_args = array(
			'post_type'      => PushToken::POST_TYPE,
			'post_status'    => 'private',
			'author__in'     => $user_ids,
			'posts_per_page' => $paginate ? $per_page : -1,
			'fields'         => 'ids',
		);

		if ( $paginate ) {
			$query_args['paged']   = $page;
			$query_args['orderby'] = 'ID';
			$query_args['order']   = 'ASC';
		}

		$query = new WP_Query( $query_args );

		/**
		 * Typehint for PHPStan, specifies these are IDs and not instances of
		 * WP_Post.
		 *
		 * @var int[] $post_ids
		 */
		$post_ids = $query->posts;

		if ( empty( $post_ids ) ) {
			$this->tokens_by_roles_cache[ $cache_key ] = $empty_result;
			return $this->tokens_by_roles_cache[ $cache_key ];
		}

		_prime_post_caches( $post_ids, false, true );

		$tokens = array();

		foreach ( $post_ids as $post_id ) {
			try {
				$tokens[] = $this->read( (int) $post_id );
			} catch ( WC_Data_Exception $e ) {
				wc_get_logger()->warning(
					'Skipping malformed push token during role-based query.',
					array(
						'source'   => PushNotifications::FEATURE_NAME,
						'token_id' => $post_id,
						'error'    => $e->getMessage(),
					)
				);
			}
		}

		$result = $paginate
			? array(
				'tokens'      => $tokens,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
			)
			: $tokens;

		$this->tokens_by_roles_cache[ $cache_key ] = $result;
		return $result;
	}

	/**
	 * Records that the given tokens were successfully sent to WPCOM.
	 *
	 * Written as two fixed queries — a delete followed by a single multi-row
	 * insert — rather than one `update_post_meta()` call per token. A store
	 * with a dozen registered devices would otherwise cost dozens of round
	 * trips on the send path, which grows with the number of devices rather
	 * than staying constant.
	 *
	 * Failure is swallowed: not knowing when a token was last used is a
	 * diagnostic gap, and must never turn a delivered notification into a
	 * failed one.
	 *
	 * @since 11.2.0
	 *
	 * @param PushToken[] $push_tokens The tokens WPCOM accepted.
	 * @return void
	 */
	public function record_last_send( array $push_tokens ): void {
		global $wpdb;

		$post_ids = array_values(
			array_filter(
				array_map(
					fn ( PushToken $push_token ) => $push_token->get_id(),
					$push_tokens
				)
			)
		);

		if ( empty( $post_ids ) ) {
			return;
		}

		$timestamp = gmdate( 'Y-m-d H:i:s' );

		/**
		 * Both statements interpolate a placeholder list whose length depends on
		 * the number of tokens. The interpolated strings are built here from
		 * literals only — never from token data — and every value still travels
		 * through `$wpdb->prepare()`, which is why the sniffs are suppressed
		 * below rather than the queries being restructured.
		 */
		$id_placeholders  = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
		$row_placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '( %d, %s, %s )' ) );

		$insert_args = array();

		foreach ( $post_ids as $post_id ) {
			$insert_args[] = $post_id;
			$insert_args[] = self::LAST_SEND_AT_META_KEY;
			$insert_args[] = $timestamp;
		}

		try {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ( $id_placeholders )",
					array_merge( array( self::LAST_SEND_AT_META_KEY ), $post_ids )
				)
			);

			$inserted = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value ) VALUES $row_placeholders",
					$insert_args
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// The rows were written behind the meta API's back, so the cached
			// meta for these posts is now stale and must be dropped.
			wp_cache_delete_multiple( $post_ids, 'post_meta' );

			if ( false === $inserted ) {
				wc_get_logger()->warning(
					'Failed to record last send time for push tokens.',
					array(
						'token_ids' => $post_ids,
						'error'     => $wpdb->last_error,
					)
				);
			}
		} catch ( Exception $e ) {
			wc_get_logger()->warning(
				'Failed to record last send time for push tokens.',
				array(
					'token_ids' => $post_ids,
					'error'     => $e->getMessage(),
				)
			);
		}//end try
	}

	/**
	 * Returns an associative array of post meta as key => value pairs for the
	 * keys defined in SUPPORTED_META; missing keys return null. Use
	 * `update_meta_cache` with `get_post_meta` to allow reading the meta as
	 * single values which automatically unserialize when requires,
	 * rather than nested arrays that don't.
	 *
	 * @since 10.5.0
	 * @param int $id The push token ID.
	 * @return array
	 */
	private function build_meta_array_from_database( int $id ): array {
		$meta_by_key = array_fill_keys( static::SUPPORTED_META, null );

		foreach ( static::SUPPORTED_META as $key ) {
			$meta = get_post_meta( $id, $key, true );

			if ( '' !== $meta ) {
				$meta_by_key[ $key ] = $meta;
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
				'platform'      => $push_token->get_platform(),
				'token'         => $push_token->get_token(),
				'device_uuid'   => $push_token->get_device_uuid(),
				'origin'        => $push_token->get_origin(),
				'device_locale' => $push_token->get_device_locale(),
				'metadata'      => $push_token->get_metadata(),
			),
			fn ( $value ) => null !== $value && '' !== $value
		);
	}
}
