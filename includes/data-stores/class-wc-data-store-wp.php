<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared logic for WP based data.
 * Contains functions like meta handling for all default data stores.
 * Your own data store doesn't need to use WC_Data_Store_WP -- you can write
 * your own meta handling functions.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Data_Store_WP {

	/**
	 * Meta type. This should match up with
	 * the types available at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $meta_type = 'post';

	/**
	 * This only needs set if you are using a custom metadata type (for example payment tokens.
	 * This should be the name of the field your table uses for associating meta with objects.
	 * For example, in payment_tokenmeta, this would be payment_token_id.
	 * @var string
	 */
	protected $object_id_field_for_meta = '';

	/**
	 * Data stored in meta keys, but not considered "meta" for an object.
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array();

	/**
	 * Get and store terms from a taxonomy.
	 *
	 * @since  3.0.0
	 * @param  WC_Data|integer $object
	 * @param  string $taxonomy Taxonomy name e.g. product_cat
	 * @return array of terms
	 */
	protected function get_term_ids( $object, $taxonomy ) {
		if ( is_numeric( $object ) ) {
			$object_id = $object;
		} else {
			$object_id = $object->get_id();
		}
		$terms = get_the_terms( $object_id, $taxonomy );
		if ( false === $terms || is_wp_error( $terms ) ) {
			return array();
		}
		return wp_list_pluck( $terms, 'term_id' );
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @since  3.0.0
	 * @param  WC_Data
	 * @return array
	 */
	public function read_meta( &$object ) {
		global $wpdb;
		$db_info       = $this->get_db_info();
		$raw_meta_data = $wpdb->get_results( $wpdb->prepare( "
			SELECT {$db_info['meta_id_field']} as meta_id, meta_key, meta_value
			FROM {$db_info['table']}
			WHERE {$db_info['object_id_field']} = %d
			ORDER BY {$db_info['meta_id_field']}
		", $object->get_id() ) );

		$this->internal_meta_keys = array_merge( array_map( array( $this, 'prefix_key' ), $object->get_data_keys() ), $this->internal_meta_keys );
		return array_filter( $raw_meta_data, array( $this, 'exclude_internal_meta_keys' ) );
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @since  3.0.0
	 * @param  WC_Data
	 * @param  stdClass (containing at least ->id)
	 * @return array
	 */
	public function delete_meta( &$object, $meta ) {
		delete_metadata_by_mid( $this->meta_type, $meta->id );
	}

	/**
	 * Add new piece of meta.
	 *
	 * @since  3.0.0
	 * @param  WC_Data
	 * @param  stdClass (containing ->key and ->value)
	 * @return int meta ID
	 */
	public function add_meta( &$object, $meta ) {
		return add_metadata( $this->meta_type, $object->get_id(), $meta->key, $meta->value, false );
	}

	/**
	 * Update meta.
	 *
	 * @since  3.0.0
	 * @param  WC_Data
	 * @param  stdClass (containing ->id, ->key and ->value)
	 */
	public function update_meta( &$object, $meta ) {
		update_metadata_by_mid( $this->meta_type, $meta->id, $meta->value, $meta->key );
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  3.0.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->prefix;

		// If we are dealing with a type of metadata that is not a core type, the table should be prefixed.
		if ( ! in_array( $this->meta_type, array( 'post', 'user', 'comment', 'term' ) ) ) {
			$table .= 'woocommerce_';
		}

		$table          .= $this->meta_type . 'meta';
		$object_id_field = $this->meta_type . '_id';

		// Figure out our field names.
		if ( 'user' === $this->meta_type ) {
			$meta_id_field = 'umeta_id';
			$table         = $wpdb->usermeta;
		}

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data. This is in
	 * addition to all data props with _ prefix.
	 * @since 2.6.0
	 * @return array
	 */
	protected function prefix_key( $key ) {
		return '_' === substr( $key, 0, 1 ) ? $key : '_' . $key;
	}

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		return ! in_array( $meta->meta_key, $this->internal_meta_keys ) && 0 !== stripos( $meta->meta_key, 'wp_' );
	}

	/**
	 * Gets a list of props and meta keys that need updated based on change state
	 * or if they are present in the database or not.
	 *
	 * @param  WC_Data $object              The WP_Data object (WC_Coupon for coupons, etc).
	 * @param  array   $meta_key_to_props   A mapping of meta keys => prop names.
	 * @param  string  $meta_type           The internal WP meta type (post, user, etc).
	 * @return array                        A mapping of meta keys => prop names, filtered by ones that should be updated.
	 */
	protected function get_props_to_update( $object, $meta_key_to_props, $meta_type = 'post' ) {
		$props_to_update = array();
		$changed_props   = $object->get_changes();

		// Props should be updated if they are a part of the $changed array or don't exist yet.
		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( array_key_exists( $prop, $changed_props ) || ! metadata_exists( $meta_type, $object->get_id(), $meta_key ) ) {
				$props_to_update[ $meta_key ] = $prop;
			}
		}

		return $props_to_update;
	}

	/**
	 * Get valid WP_Query args from a WC_Object_Query's query variables.
	 *
	 * @since 3.1.0
	 * @param array $query_vars query vars from a WC_Object_Query
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {

		$skipped_values = array( '', array(), null );
		$wp_query_args = array(
			'meta_query'    => array(),
		);

		foreach( $query_vars as $key => $value ) {
			if ( in_array( $value, $skipped_values, true ) || 'meta_query' === $key ) {
				continue;
			}

			// Build meta queries out of vars that are stored in internal meta keys.
			if ( in_array( '_' . $key, $this->internal_meta_keys ) ) {
				$wp_query_args['meta_query'][] = array(
					'key'     => '_' . $key,
					'value'   => $value,
					'compare' => '=',
				);
			// Other vars get mapped to wp_query args or just left alone.
			} else {
				$key_mapping = array (
					'parent'         => 'post_parent',
					'parent_exclude' => 'post_parent__not_in',
					'exclude'        => 'post__not_in',
					'limit'          => 'posts_per_page',
					'type'           => 'post_type',
					'return'         => 'fields',
				);

				if ( isset( $key_mapping[ $key ] ) ) {
					$wp_query_args[ $key_mapping[ $key ] ] = $value;
				} else {
					$wp_query_args[ $key ] = $value;
				}
			}
		}

		return apply_filters( 'woocommerce_get_wp_query_args', $wp_query_args, $query_vars );
	}

	/**
	 *
	 * Valid date formats: YYYY-MM-DD or timestamp, possibly combined with an operator in $valid_operators.
	 * Also accepts a WC_DateTime object.
	 *
	 */
	protected function parse_date_for_wp_query( $query_var, $key, $wp_query_args = array() ) {
		$query_parse_regex = '/([^.<>]*)(<|>|\.\.\.=?)([^.<>]+)/';
		$valid_operators = array( '>', '>=', '=', '<=', '<', '...' );
		$precision = 'second';

		$start_date = null;
		$operator = '=';
		$end_date = null;

		// Specific time query with a WC_DateTime.
		if ( is_a( $query_var, 'WC_DateTime' ) ) {
			$end_date = $query_var;

		// Specific time query with a timestamp.
		} elseif ( is_numeric( $query_var ) ) {
			$end_date = new WC_DateTime( "@{$query_var}", new DateTimeZone( 'UTC' ) );

		// Query with operators and possible range of dates.
		} elseif ( preg_match( $query_parse_regex, $query_var, $sections ) ) {
			if ( ! empty( $sections[1] ) ) {
				$start_date = is_numeric( $sections[1] ) ? new WC_DateTime( "@{$query_var}", new DateTimeZone( 'UTC' ) ) : wc_string_to_datetime( $sections[1] );
			}

			$operator = in_array( $sections[2], $valid_operators ) ? $sections[2] : '';
			$end_date = is_numeric( $sections[3] ) ? new WC_DateTime( "@{$query_var}", new DateTimeZone( 'UTC' ) ) : wc_string_to_datetime( $sections[3] );

			// YYYY-MM-DD strings shouldn't match down to the second.
			if ( ! is_numeric( $sections[1] ) && ! is_numeric( $sections[3] ) ) {
				$precision = 'day';
			}

		// Specific time query with a string.
		} else {
			$end_date = wc_string_to_datetime( $query_var );
			$precision = 'day';
		}

		// Check for valid inputs.
		if ( ! $operator || ! $end_date || ( '...' === $operator && ! $start_date ) ) {
			return $wp_query_args;
		}

		// Build date query for 'post_date' or 'post_modified' keys.
		if ( 'post_date' == $key || 'post_modified' == $key ) {
			if ( ! isset( $wp_query_args['date_query'] ) ) {
				$wp_query_args['date_query'] = array();
			}

			$query_arg = array(
				'column' => $key . '_gmt',
				'inclusive' => '>' !== $operator && '<' !== $operator,
			);

			if ( '>' == $operator || '>=' == $operator ) {
				$query_arg['after'] = array(
					'year' => $end_date->date( 'Y' ),
					'month' => $end_date->date( 'n' ),
					'day' => $end_date->date( 'j' ),
				);
				if ( 'second' === $precision ) {

				}
			} elseif( '<' == $operator || '<=' == $operator ) {
				$query_arg['before'] = array(
					'year' => $end_date->date( 'Y' ),
					'month' => $end_date->date( 'n' ),
					'day' => $end_date->date( 'j' ),
				);
				if ( 'second' === $precision ) {

				}
			} elseif( '...' == $operator ) {
				$query_arg['before'] = array(
					'year' => $start_date->date( 'Y' ),
					'month' => $start_date->date( 'n' ),
					'day' => $start_date->date( 'j' ),
				);
				$query_arg['after'] = array(
					'year' => $end_date->date( 'Y' ),
					'month' => $end_date->date( 'n' ),
					'day' => $end_date->date( 'j' ),
				);
				if ( 'second' === $precision ) {

				}
			} else {
				$query_arg['year'] = $end_date->date( 'Y' );
				$query_arg['month'] = $end_date->date( 'n' );
				$query_arg['day'] = $end_date->date( 'j' );
				if ( 'second' === $precision ) {

				}
			}

			$wp_query_args['date_query'][] = $query_arg;
			return $wp_query_args;
		}

		// Build meta query for unrecognized keys.
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array();
		}

		if ( '...' !== $operator ) {
			$wp_query_args['meta_query'][] = array(
				'key'     => $key,
				'value'   => $end_date->getTimestamp(),
				'compare' => $operator,
			);
		} else {
			$wp_query_args['meta_query'][] = array(
				'key' => $key,
				'value' => $start_date->getTimestamp(),
				'compare' => '>=',
			);
			$wp_query_args['meta_query'][] = array(
				'key' => '$key',
				'value' => $end_date->getTimestamp(),
				'compare' => '<=',
			);
		}

		return $wp_query_args;
	}

	protected function build_wp_date_query( $start, $end, $operator, $precision ) {

	}

	/**
	 * Get a valid date for use in WP_Query date queries.
	 *
	 * @since 3.1.0
	 * @param mixed $query_var Value from a WC_Object_Query's query variable.
	 */
	protected function get_date_for_wp_query( $query_var, $as_timestamp = false ) {
		if ( ! $query_var ) {
			return '';
		}

		if ( is_a( $query_var, 'WC_DateTime' ) ) {
			$datetime = $query_var;
		} elseif ( is_numeric( $query_var ) ) {
			// Timestamps are handled as UTC timestamps in all cases.
			$datetime = new WC_DateTime( "@{$value}", new DateTimeZone( 'UTC' ) );
		} else {
			// Strings are defined in local WP timezone. Convert to UTC.
			if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $query_var, $date_bits ) ) {
				$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
				$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
			} else {
				$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $query_var ) ) ) );
			}
			$datetime  = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );
		}

		// Set local timezone or offset.
		if ( get_option( 'timezone_string' ) ) {
			$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
		} else {
			$datetime->set_utc_offset( wc_timezone_offset() );
		}

		return $as_timestamp ? $datetime->getTimestamp() : (string) $datetime;
	}
}
