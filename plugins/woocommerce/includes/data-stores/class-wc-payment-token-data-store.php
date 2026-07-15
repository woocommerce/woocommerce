<?php
/**
 * Class WC_Payment_Token_Data_Store file.
 *
 * @package WooCommerce\DataStores
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Payment Token Data Store: Custom Table.
 *
 * @version  3.0.0
 */
class WC_Payment_Token_Data_Store extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface, WC_Payment_Token_Data_Store_Interface {

	/**
	 * Meta type. Payment tokens are a new object type.
	 *
	 * @var string
	 */
	protected $meta_type = 'payment_token';

	/**
	 * Fallback maximum number of rows returned by `get_tokens()` for queries that are neither
	 * scoped to a `user_id`/`token_id` nor given an explicit `limit` or `page`.
	 *
	 * Such a query matches on `gateway_id`/`type` only, and neither column is indexed, so it is a
	 * full table scan across every user's tokens. This ceiling keeps an accidental store-wide
	 * query bounded; scoped queries ride the primary key or the `user_id` index and stay unlimited.
	 *
	 * @since 11.1.0
	 * @var int
	 */
	const DEFAULT_UNSCOPED_TOKENS_LIMIT = 500;

	/**
	 * Page size applied by `get_tokens()` when a `page` arg is passed without an explicit `limit`.
	 *
	 * A `page` arg signals pagination intent, and paginating consumers typically loop until a
	 * short or empty page: leaving such a query unlimited would return the full set on every
	 * page and never terminate the loop. Pass an explicit `limit` to control the page size, or
	 * filter it via `woocommerce_get_payment_tokens_page_size`.
	 *
	 * @since 11.1.0
	 * @var int
	 */
	const DEFAULT_PAGE_SIZE = 100;

	/**
	 * If we have already saved our extra data, don't do automatic / default handling.
	 *
	 * @var bool
	 */
	protected $extra_data_saved = false;

	/**
	 * Create a new payment token in the database.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Payment_Token $token Payment token object.
	 *
	 * @throws Exception Throw exception if invalid or missing payment token fields.
	 */
	public function create( &$token ) {
		if ( false === $token->validate() ) {
			throw new Exception( __( 'Invalid or missing payment token fields.', 'woocommerce' ) );
		}

		global $wpdb;
		if ( ! $token->is_default() && $token->get_user_id() > 0 ) {
			$default_token = WC_Payment_Tokens::get_customer_default_token( $token->get_user_id() );
			if ( is_null( $default_token ) ) {
				$token->set_default( true );
			}
		}

		$payment_token_data = array(
			'gateway_id' => $token->get_gateway_id( 'edit' ),
			'token'      => $token->get_token( 'edit' ),
			'user_id'    => $token->get_user_id( 'edit' ),
			'type'       => $token->get_type( 'edit' ),
		);

		$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_tokens', $payment_token_data );
		$token_id = $wpdb->insert_id;
		$token->set_id( $token_id );
		$this->save_extra_data( $token, true );
		$token->save_meta_data();
		$token->apply_changes();

		// Make sure all other tokens are not set to default.
		if ( $token->is_default() && $token->get_user_id() > 0 ) {
			WC_Payment_Tokens::set_users_default( $token->get_user_id(), $token_id );
		}

		do_action( 'woocommerce_new_payment_token', $token_id, $token );
	}

	/**
	 * Update a payment token.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Payment_Token $token Payment token object.
	 *
	 * @throws Exception Throw exception if invalid or missing payment token fields.
	 */
	public function update( &$token ) {
		if ( false === $token->validate() ) {
			throw new Exception( __( 'Invalid or missing payment token fields.', 'woocommerce' ) );
		}

		global $wpdb;

		$updated_props = array();
		$core_props    = array( 'gateway_id', 'token', 'user_id', 'type' );
		$changed_props = array_keys( $token->get_changes() );

		foreach ( $changed_props as $prop ) {
			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}
			$updated_props[]             = $prop;
			$payment_token_data[ $prop ] = $token->{'get_' . $prop}( 'edit' );
		}

		if ( ! empty( $payment_token_data ) ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_payment_tokens',
				$payment_token_data,
				array( 'token_id' => $token->get_id() )
			);
		}

		$updated_extra_props = $this->save_extra_data( $token );
		$updated_props       = array_merge( $updated_props, $updated_extra_props );
		$token->save_meta_data();
		$token->apply_changes();

		// Make sure all other tokens are not set to default.
		if ( $token->is_default() && $token->get_user_id() > 0 ) {
			WC_Payment_Tokens::set_users_default( $token->get_user_id(), $token->get_id() );
		}

		do_action( 'woocommerce_payment_token_object_updated_props', $token, $updated_props );
		do_action( 'woocommerce_payment_token_updated', $token->get_id() );
	}

	/**
	 * Remove a payment token from the database.
	 *
	 * @since 3.0.0
	 * @param WC_Payment_Token $token Payment token object.
	 * @param bool             $force_delete Unused param.
	 */
	public function delete( &$token, $force_delete = false ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokens', array( 'token_id' => $token->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokenmeta', array( 'payment_token_id' => $token->get_id() ), array( '%d' ) );
		do_action( 'woocommerce_payment_token_deleted', $token->get_id(), $token );
	}

	/**
	 * Read a token from the database.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Payment_Token $token Payment token object.
	 *
	 * @throws Exception Throw exception if invalid payment token.
	 */
	public function read( &$token ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT token, user_id, gateway_id, is_default FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d LIMIT 1",
				$token->get_id()
			)
		);

		if ( $data ) {
			$token->set_props(
				array(
					'token'      => $data->token,
					'user_id'    => $data->user_id,
					'gateway_id' => $data->gateway_id,
					'default'    => $data->is_default,
				)
			);
			$this->read_extra_data( $token );
			$token->read_meta_data();
			$token->set_object_read( true );
			do_action( 'woocommerce_payment_token_loaded', $token );
		} else {
			throw new Exception( __( 'Invalid payment token.', 'woocommerce' ) );
		}
	}

	/**
	 * Read extra data associated with the token (like last4 digits of a card for expiry dates).
	 *
	 * @param WC_Payment_Token $token Payment token object.
	 * @since 3.0.0
	 */
	protected function read_extra_data( &$token ) {
		foreach ( $token->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $token, $function ) ) ) {
				$token->{$function}( get_metadata( 'payment_token', $token->get_id(), $key, true ) );
			}
		}
	}

	/**
	 * Saves extra token data as meta.
	 *
	 * @since 3.0.0
	 * @param WC_Payment_Token $token Payment token object.
	 * @param bool             $force By default, only changed props are updated. When this param is true all props are updated.
	 * @return array List of updated props.
	 */
	protected function save_extra_data( &$token, $force = false ) {
		if ( $this->extra_data_saved ) {
			return array();
		}

		$updated_props     = array();
		$extra_data_keys   = $token->get_extra_data_keys();
		$meta_key_to_props = ! empty( $extra_data_keys ) ? array_combine( $extra_data_keys, $extra_data_keys ) : array();
		$props_to_update   = $force ? $meta_key_to_props : $this->get_props_to_update( $token, $meta_key_to_props );

		foreach ( $extra_data_keys as $key ) {
			if ( ! array_key_exists( $key, $props_to_update ) ) {
				continue;
			}
			$function = 'get_' . $key;
			if ( is_callable( array( $token, $function ) ) ) {
				if ( update_metadata( 'payment_token', $token->get_id(), $key, $token->{$function}( 'edit' ) ) ) {
					$updated_props[] = $key;
				}
			}
		}

		return $updated_props;
	}

	/**
	 * Returns an array of objects (stdObject) matching specific token criteria.
	 * Accepts token_id, user_id, gateway_id, and type.
	 * Each object should contain the fields token_id, gateway_id, token, user_id, type, is_default.
	 *
	 * An explicit `limit` arg is always honored, including 0 (returns no rows). Without one, a
	 * `page` arg paginates by DEFAULT_PAGE_SIZE rows; otherwise queries scoped to a `token_id` or
	 * `user_id` are unlimited, and an unscoped query (which reads every token in the store) falls
	 * back to DEFAULT_UNSCOPED_TOKENS_LIMIT rows, filterable via
	 * `woocommerce_get_payment_tokens_unscoped_limit`.
	 *
	 * @since 3.0.0
	 * @since 11.1.0 Results are no longer implicitly limited by the `posts_per_page` option; without
	 *               an explicit `limit`, scoped queries not passing `page` are unlimited and unscoped
	 *               queries fall back to a ceiling.
	 * @param array $args List of accepted args: token_id, gateway_id, user_id, type, limit, page.
	 * @return array
	 */
	public function get_tokens( $args ) {
		global $wpdb;
		$args = wp_parse_args(
			$args,
			array(
				'token_id'   => '',
				'user_id'    => '',
				'gateway_id' => '',
				'type'       => '',
			)
		);

		$sql   = "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens";
		$where = array( '1=1' );

		if ( $args['token_id'] ) {
			$token_ids = array_map( 'absint', is_array( $args['token_id'] ) ? $args['token_id'] : array( $args['token_id'] ) );
			$where[]   = "token_id IN ('" . implode( "','", array_map( 'esc_sql', $token_ids ) ) . "')";
		}

		if ( $args['user_id'] ) {
			$where[] = $wpdb->prepare( 'user_id = %d', absint( $args['user_id'] ) );
		}

		if ( $args['gateway_id'] ) {
			$gateway_ids = array( $args['gateway_id'] );
		} else {
			$gateways    = WC_Payment_Gateways::instance();
			$gateway_ids = $gateways->get_payment_gateway_ids();
		}

		$page  = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
		$limit = null;

		if ( isset( $args['limit'] ) ) {
			// An explicit limit is authoritative, including 0, which deliberately yields no rows:
			// extensions return 0 from the woocommerce_get_customer_payment_tokens_limit filter to
			// hide a customer's saved methods.
			$limit = absint( $args['limit'] );
		} elseif ( isset( $args['page'] ) ) {
			/**
			 * Controls the page size applied to a query that passes `page` without an explicit
			 * `limit`. Such a query signals pagination intent, and paginating consumers typically
			 * loop until a short or empty page, so it is sized rather than left unlimited. Pass an
			 * explicit `limit` to opt out.
			 *
			 * @since 11.1.0
			 *
			 * @param int   $page_size Maximum number of tokens per page. Defaults to 100. A value
			 *                         below 1 is ignored, as an empty page would stop a paginating
			 *                         consumer before it read anything, and `-1` does not mean
			 *                         unlimited here: pass an explicit `limit` to opt out.
			 * @param array $args      The arguments passed to `get_tokens()`.
			 */
			$page_size = apply_filters( 'woocommerce_get_payment_tokens_page_size', self::DEFAULT_PAGE_SIZE, $args );
			$limit     = self::sanitize_row_count( $page_size, self::DEFAULT_PAGE_SIZE );
		} elseif ( ! $args['token_id'] && ! $args['user_id'] ) {
			/**
			 * Controls the fallback maximum number of tokens returned by an unscoped query, i.e. one
			 * passing neither `user_id`/`token_id` nor an explicit `limit` or `page`. Such a query
			 * matches on the unindexed `gateway_id`/`type` columns and would otherwise read every
			 * token in the store. Pass an explicit `limit`, or a `page` (sized by
			 * `woocommerce_get_payment_tokens_page_size`), to opt out of this ceiling.
			 *
			 * Queries scoped to a `user_id` or `token_id` are unaffected and remain unlimited.
			 *
			 * @since 11.1.0
			 *
			 * @param int   $limit Maximum number of tokens to return. Defaults to 500. A value below
			 *                     1 is ignored, as it would empty the result set rather than cap it,
			 *                     and `-1` does not mean unlimited here: pass an explicit `limit` to
			 *                     opt out.
			 * @param array $args  The arguments passed to `get_tokens()`.
			 */
			$ceiling = apply_filters( 'woocommerce_get_payment_tokens_unscoped_limit', self::DEFAULT_UNSCOPED_TOKENS_LIMIT, $args );
			$limit   = self::sanitize_row_count( $ceiling, self::DEFAULT_UNSCOPED_TOKENS_LIMIT );
		}

		// Without an explicit limit or page, a query scoped to a token_id or user_id stays
		// unlimited: consumers like the personal data eraser and user deletion cleanup rely on
		// retrieving every matching token, reaching this method via WC_Payment_Tokens::get_tokens().
		$limits = '';
		if ( null !== $limit ) {
			$limits = 'LIMIT ' . absint( ( $page - 1 ) * $limit ) . ', ' . $limit;
		}

		$gateway_ids[] = '';
		$where[]       = "gateway_id IN ('" . implode( "','", array_map( 'esc_sql', $gateway_ids ) ) . "')";

		if ( $args['type'] ) {
			$where[] = $wpdb->prepare( 'type = %s', $args['type'] );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$token_results = $wpdb->get_results( $sql . ' WHERE ' . implode( ' AND ', $where ) . ' ' . $limits );

		return $token_results;
	}

	/**
	 * Reads a filtered row count, falling back to a default unless it is a positive number.
	 *
	 * The sign has to be read before any `absint()` coercion: `absint( -1 )` is 1, so a callback
	 * reaching for WordPress's `-1` means unlimited idiom would otherwise cap the query at a
	 * single row instead of falling back.
	 *
	 * @since 11.1.0
	 * @param mixed $value   The filtered value.
	 * @param int   $default_value Row count to use when `$value` is not a positive number.
	 * @return int
	 */
	private static function sanitize_row_count( $value, int $default_value ): int {
		return is_numeric( $value ) && $value > 0 ? absint( $value ) : $default_value;
	}

	/**
	 * Returns an stdObject of a token for a user's default token.
	 * Should contain the fields token_id, gateway_id, token, user_id, type, is_default.
	 *
	 * @since 3.0.0
	 * @param int $user_id User ID.
	 * @return object
	 */
	public function get_users_default_token( $user_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE user_id = %d AND is_default = 1",
				$user_id
			)
		);
	}

	/**
	 * Returns an stdObject of a token.
	 * Should contain the fields token_id, gateway_id, token, user_id, type, is_default.
	 *
	 * @since 3.0.0
	 * @param int $token_id Token ID.
	 * @return object
	 */
	public function get_token_by_id( $token_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d",
				$token_id
			)
		);
	}

	/**
	 * Returns metadata for a specific payment token.
	 *
	 * @since 3.0.0
	 * @param int $token_id Token ID.
	 * @return array
	 */
	public function get_metadata( $token_id ) {
		return get_metadata( 'payment_token', $token_id );
	}

	/**
	 * Get a token's type by ID.
	 *
	 * @since 3.0.0
	 * @param int $token_id Token ID.
	 * @return string
	 */
	public function get_token_type_by_id( $token_id ) {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT type FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d",
				$token_id
			)
		);
	}

	/**
	 * Update's a tokens default status in the database. Used for quickly
	 * looping through tokens and setting their statuses instead of creating a bunch
	 * of objects.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $token_id Token ID.
	 * @param bool $status Whether given payment token is the default payment token or not.
	 *
	 * @return void
	 */
	public function set_default_status( $token_id, $status = true ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocommerce_payment_tokens',
			array( 'is_default' => (int) $status ),
			array(
				'token_id' => $token_id,
			)
		);
	}

}
