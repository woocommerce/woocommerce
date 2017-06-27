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
		return add_metadata( $this->meta_type, $object->get_id(), $meta->key, wp_slash( $meta->value ), false );
	}

	/**
	 * Update meta.
	 *
	 * @since  3.0.0
	 * @param  WC_Data
	 * @param  stdClass (containing ->id, ->key and ->value)
	 */
	public function update_meta( &$object, $meta ) {
		update_metadata_by_mid( $this->meta_type, $meta->id, wp_slash( $meta->value ), $meta->key );
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
	 *
	 * @param string $key
	 *
	 * @return string
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
			'errors'     => array(),
			'meta_query' => array(),
		);

		foreach ( $query_vars as $key => $value ) {
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
				$key_mapping = array(
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
	 * Map a valid date query var to WP_Query arguments.
	 * Valid date formats: YYYY-MM-DD or timestamp, possibly combined with an operator from $valid_operators.
	 * Also accepts a WC_DateTime object.
	 * @param mixed $query_var A valid date format
	 * @param string $key meta or db column key
	 * @param array $wp_query_args WP_Query args
	 * @return array Modified $wp_query_args
	 */
	protected function parse_date_for_wp_query( $query_var, $key, $wp_query_args = array() ) {
		$query_parse_regex = '/([^.<>]*)(>=|<=|>|<|\.\.\.)([^.<>]+)/';
		$valid_operators   = array( '>', '>=', '=', '<=', '<', '...' );

		// YYYY-MM-DD queries have 'day' precision. Timestamp/WC_DateTime queries have 'second' precision.
		$precision = 'second';

		$dates    = array();
		$operator = '=';

		try {
			// Specific time query with a WC_DateTime.
			if ( is_a( $query_var, 'WC_DateTime' ) ) {
				$dates[] = $query_var;

			// Specific time query with a timestamp.
			} elseif ( is_numeric( $query_var ) ) {
				$dates[] = new WC_DateTime( "@{$query_var}", new DateTimeZone( 'UTC' ) );

			// Query with operators and possible range of dates.
			} elseif ( preg_match( $query_parse_regex, $query_var, $sections ) ) {
				if ( ! empty( $sections[1] ) ) {
					$dates[] = is_numeric( $sections[1] ) ? new WC_DateTime( "@{$sections[1]}", new DateTimeZone( 'UTC' ) ) : wc_string_to_datetime( $sections[1] );
				}

				$operator = in_array( $sections[2], $valid_operators ) ? $sections[2] : '';
				$dates[] = is_numeric( $sections[3] ) ? new WC_DateTime( "@{$sections[3]}", new DateTimeZone( 'UTC' ) ) : wc_string_to_datetime( $sections[3] );

				if ( ! is_numeric( $sections[1] ) && ! is_numeric( $sections[3] ) ) {
					$precision = 'day';
				}

			// Specific time query with a string.
			} else {
				$dates[] = wc_string_to_datetime( $query_var );
				$precision = 'day';
			}
		} catch ( Exception $e ) {
			return $wp_query_args;
		}

		// Check for valid inputs.
		if ( ! $operator || empty( $dates ) || ( '...' === $operator && count( $dates ) < 2 ) ) {
			return $wp_query_args;
		}

		// Build date query for 'post_date' or 'post_modified' keys.
		if ( 'post_date' === $key || 'post_modified' === $key ) {
			if ( ! isset( $wp_query_args['date_query'] ) ) {
				$wp_query_args['date_query'] = array();
			}

			$query_arg = array(
				'column'    => 'day' === $precision ? $key : $key . '_gmt',
				'inclusive' => '>' !== $operator && '<' !== $operator,
			);

			// Add 'before'/'after' query args.
			$comparisons = array();
			if ( '>' === $operator || '>=' === $operator || '...' === $operator ) {
				$comparisons[] = 'after';
			}
			if ( '<' === $operator || '<=' === $operator || '...' === $operator ) {
				$comparisons[] = 'before';
			}

			foreach ( $comparisons as $index => $comparison ) {
				/**
				 * WordPress doesn't generate the correct SQL for inclusive day queries with both a 'before' and
				 * 'after' string query, so we have to use the array format in 'day' precision.
				 * @see https://core.trac.wordpress.org/ticket/29908
				 */
				if ( 'day' === $precision ) {
					$query_arg[ $comparison ]['year']  = $dates[ $index ]->date( 'Y' );
					$query_arg[ $comparison ]['month'] = $dates[ $index ]->date( 'n' );
					$query_arg[ $comparison ]['day']   = $dates[ $index ]->date( 'j' );
				/**
				 * WordPress doesn't support 'hour'/'second'/'minute' in array format 'before'/'after' queries,
				 * so we have to use a string query.
				 */
				} else {
					$query_arg[ $comparison ] = gmdate( 'm/d/Y H:i:s', $dates[ $index ]->getTimestamp() );
				}
			}

			if ( empty( $comparisons ) ) {
				$query_arg['year']  = $dates[0]->date( 'Y' );
				$query_arg['month'] = $dates[0]->date( 'n' );
				$query_arg['day']   = $dates[0]->date( 'j' );
				if ( 'second' === $precision ) {
					$query_arg['hour']   = $dates[0]->date( 'H' );
					$query_arg['minute'] = $dates[0]->date( 'i' );
					$query_arg['second'] = $dates[0]->date( 's' );
				}
			}
			$wp_query_args['date_query'][] = $query_arg;
			return $wp_query_args;
		}

		// Build meta query for unrecognized keys.
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array();
		}

		// Meta dates are stored as timestamps in the db.
		// Check against begining/end-of-day timestamps when using 'day' precision.
		if ( 'day' === $precision ) {
			$start_timestamp = strtotime( gmdate( 'm/d/Y 00:00:00', $dates[0]->getTimestamp() ) );
			$end_timestamp = '...' !== $operator ? ( $start_timestamp + DAY_IN_SECONDS ) : strtotime( gmdate( 'm/d/Y 00:00:00', $dates[1]->getTimestamp() ) );
			switch ( $operator ) {
				case '>':
				case '<=':
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $end_timestamp,
						'compare' => $operator,
					);
				break;

				case '<':
				case '>=':
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $start_timestamp,
						'compare' => $operator,
					);
				break;

				default:
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $start_timestamp,
						'compare' => '>=',
					);
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $end_timestamp,
						'compare' => '<=',
					);
			}
		} else {
			if ( '...' !== $operator ) {
				$wp_query_args['meta_query'][] = array(
					'key'     => $key,
					'value'   => $dates[0]->getTimestamp(),
					'compare' => $operator,
				);
			} else {
				$wp_query_args['meta_query'][] = array(
					'key'     => $key,
					'value'   => $dates[0]->getTimestamp(),
					'compare' => '>=',
				);
				$wp_query_args['meta_query'][] = array(
					'key'     => $key,
					'value'   => $dates[1]->getTimestamp(),
					'compare' => '<=',
				);
			}
		}

		return $wp_query_args;
	}
}
