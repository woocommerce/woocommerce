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
use Throwable;
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
	 * Buffered last-send stamps awaiting a write, as token post ID => GMT
	 * datetime. Holds the most recent time recorded for each token this
	 * request. Flushed by {@see self::flush_last_send()} on shutdown.
	 *
	 * @var array<int, string>
	 */
	private array $pending_last_send = array();

	/**
	 * Whether the shutdown flush for `$pending_last_send` has been registered.
	 *
	 * @var bool
	 */
	private bool $last_send_flush_registered = false;

	/**
	 * How many tokens to write per statement when flushing last-send stamps.
	 */
	const LAST_SEND_CHUNK_SIZE = 100;

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
						'id'               => $post_id,
						'user_id'          => $user_id,
						'token'            => $meta['token'],
						'device_uuid'      => $meta['device_uuid'] ?? null,
						'platform'         => $meta['platform'],
						'origin'           => $meta['origin'],
						/**
						 * These meta items were added after the ability to store
						 * tokens, so may not be available for older tokens. Use
						 * sensible defaults.
						 */
						'device_locale'    => $meta['device_locale'] ?? PushToken::DEFAULT_DEVICE_LOCALE,
						'metadata'         => $meta['metadata'] ?? array(),
						'last_send_at_gmt' => $meta[ self::LAST_SEND_AT_META_KEY ] ?? null,
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
	 * Buffers the stamps and writes them once on shutdown rather than per call.
	 * A single request often processes several notifications — the loopback
	 * receives every notification a store event produced, and a bulk order
	 * update can produce dozens — and each one would otherwise repeat the same
	 * write against the same handful of tokens. Buffering makes the cost a
	 * function of the request rather than of the notification count, while each
	 * token still keeps the exact time of its own most recent send.
	 *
	 * @since 11.2.0
	 *
	 * @param PushToken[] $push_tokens The tokens WPCOM accepted.
	 * @return void
	 */
	public function record_last_send( array $push_tokens ): void {
		$timestamp = gmdate( 'Y-m-d H:i:s' );

		foreach ( $push_tokens as $push_token ) {
			$id = $push_token->get_id();

			if ( $id ) {
				$this->pending_last_send[ $id ] = $timestamp;
			}
		}

		if ( empty( $this->pending_last_send ) || $this->last_send_flush_registered ) {
			return;
		}

		add_action( 'shutdown', array( $this, 'flush_last_send' ) );

		// The safety net and retry jobs run under an Action Scheduler queue
		// runner, which is routinely killed on a time limit. Shutdown functions
		// do not run on a kill, so flush after each action as well.
		add_action( 'action_scheduler_after_execute', array( $this, 'flush_last_send' ) );

		$this->last_send_flush_registered = true;
	}

	/**
	 * Writes the buffered last-send stamps.
	 *
	 * Runs on shutdown and after each Action Scheduler action, and is safe to
	 * call directly to force the write early.
	 *
	 * Failure is swallowed: not knowing when a token was last used is a
	 * diagnostic gap, and must never turn a delivered notification into a
	 * failed one.
	 *
	 * @since 11.2.0
	 *
	 * @return void
	 */
	public function flush_last_send(): void {
		// Lets a later `record_last_send()` re-assert both hooks. They stay
		// registered either way, and `add_action()` is idempotent here.
		$this->last_send_flush_registered = false;

		if ( empty( $this->pending_last_send ) ) {
			return;
		}

		$pending                 = $this->pending_last_send;
		$this->pending_last_send = array();

		// Chunked so neither the `IN` list nor the number of placeholders handed
		// to `$wpdb->prepare()` grows with the number of registered devices. A
		// large `IN` list can also push the optimizer off the `post_id` index.
		foreach ( array_chunk( $pending, self::LAST_SEND_CHUNK_SIZE, true ) as $chunk ) {
			try {
				$this->write_last_send_chunk( $chunk );
			} catch ( Throwable $e ) {
				// Throwable, not Exception. `action_scheduler_after_execute`
				// fires between the action running and `mark_complete()`, inside
				// the runner's own Throwable catch, so an Error escaping here
				// would record a delivered notification's action as failed.
				wc_get_logger()->warning(
					'Failed to record last send time for push tokens.',
					array(
						'token_ids' => array_keys( $chunk ),
						'error'     => $e->getMessage(),
					)
				);
			}
		}
	}

	/**
	 * Writes one chunk of buffered last-send stamps.
	 *
	 * Existing rows are updated in place rather than deleted and reinserted.
	 * This is the busiest write path in the feature, and a delete/insert cycle
	 * on the same rows consumes `meta_id` values permanently and fragments the
	 * primary key, for no benefit — `wp_postmeta` has no unique key on
	 * `(post_id, meta_key)`, so the rows to update have to be identified first
	 * either way.
	 *
	 * @param array<int, string> $chunk Map of token post ID to GMT datetime.
	 * @return void
	 */
	private function write_last_send_chunk( array $chunk ): void {
		global $wpdb;

		$post_ids = array_keys( $chunk );

		/**
		 * The statements below interpolate a placeholder list whose length
		 * depends on the number of tokens. The interpolated strings are built
		 * from literals only — never from token data — and every value still
		 * travels through `$wpdb->prepare()`, which is why the sniffs are
		 * suppressed rather than the queries being restructured.
		 */
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$select = $wpdb->prepare(
			sprintf(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %%s AND post_id IN ( %s )",
				implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) )
			),
			array_merge( array( self::LAST_SEND_AT_META_KEY ), $post_ids )
		);

		// `prepare()` returns null on a placeholder mismatch, and `get_col()`
		// would then skip the query and read `last_result` from whatever ran
		// before, so the check cannot wait until after the read.
		if ( ! is_string( $select ) || '' === $select ) {
			$this->warn_last_send_not_recorded( $post_ids, 'Could not build the query to read existing stamps, skipping chunk.' );

			return;
		}

		// Run the statement rather than using `get_col()`, whose empty array
		// means both "nothing matched" and "the read failed". Taking a failed
		// read as "no rows exist" would insert duplicates that `wp_postmeta`
		// has no unique key to prevent. `last_error` alone cannot be trusted
		// either: `wpdb::query()` returns false before it clears the previous
		// error when the `query` filter empties the statement.
		$rows = $wpdb->query( $select );

		if ( false === $rows ) {
			$this->warn_last_send_not_recorded(
				$post_ids,
				sprintf(
					'Could not read existing stamps, skipping chunk. %s',
					'' !== $wpdb->last_error ? $wpdb->last_error : 'The query did not run.'
				)
			);

			return;
		}

		$existing = array_map( 'intval', wp_list_pluck( (array) $wpdb->last_result, 'post_id' ) );

		// Tokens sent at the same moment share an UPDATE, so this is usually
		// one query, without giving a token another's send time when a request
		// spans a second boundary.
		$by_timestamp = array();

		foreach ( $chunk as $post_id => $timestamp ) {
			$by_timestamp[ $timestamp ][] = $post_id;
		}

		foreach ( $by_timestamp as $timestamp => $ids ) {
			$update_ids = array_values( array_intersect( $ids, $existing ) );
			$insert_ids = array_values( array_diff( $ids, $existing ) );

			if ( ! empty( $update_ids ) ) {
				$this->query_or_warn(
					$wpdb->prepare(
						sprintf(
							"UPDATE {$wpdb->postmeta} SET meta_value = %%s WHERE meta_key = %%s AND post_id IN ( %s )",
							implode( ', ', array_fill( 0, count( $update_ids ), '%d' ) )
						),
						array_merge( array( $timestamp, self::LAST_SEND_AT_META_KEY ), $update_ids )
					),
					$update_ids
				);
			}

			if ( ! empty( $insert_ids ) ) {
				$insert_args = array();

				foreach ( $insert_ids as $post_id ) {
					$insert_args[] = $post_id;
					$insert_args[] = self::LAST_SEND_AT_META_KEY;
					$insert_args[] = $timestamp;
				}

				$this->query_or_warn(
					$wpdb->prepare(
						sprintf(
							"INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value ) VALUES %s",
							implode( ', ', array_fill( 0, count( $insert_ids ), '( %d, %s, %s )' ) )
						),
						$insert_args
					),
					$insert_ids
				);
			}
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// The rows were written behind the meta API's back, so the cached meta
		// for these posts is now stale and must be dropped.
		wp_cache_delete_multiple( $post_ids, 'post_meta' );
	}

	/**
	 * Logs that last-send stamps could not be recorded.
	 *
	 * @param int[]  $post_ids The token post IDs affected.
	 * @param string $error    What went wrong, and what was skipped as a result.
	 * @return void
	 */
	private function warn_last_send_not_recorded( array $post_ids, string $error ): void {
		wc_get_logger()->warning(
			'Could not record last send time for push tokens.',
			array(
				'token_ids' => $post_ids,
				'error'     => $error,
			)
		);
	}

	/**
	 * Runs a prepared statement, logging rather than throwing when it fails.
	 *
	 * @param string|null $query    The prepared SQL, or null if `prepare()` failed.
	 * @param int[]       $post_ids The token post IDs the statement covers, for the log context.
	 * @return void
	 */
	private function query_or_warn( ?string $query, array $post_ids ): void {
		global $wpdb;

		if ( ! is_string( $query ) || '' === $query ) {
			$this->warn_last_send_not_recorded( $post_ids, 'Could not build the query to write stamps.' );

			return;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( false !== $wpdb->query( $query ) ) {
			return;
		}

		wc_get_logger()->warning(
			'Failed to record last send time for push tokens.',
			array(
				'token_ids' => $post_ids,
				'error'     => $wpdb->last_error,
			)
		);
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
