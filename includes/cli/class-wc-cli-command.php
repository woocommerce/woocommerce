<?php

/**
 * WooCommerce CLI Command.
 *
 * Base class that must be extended by any WooCommerce sub commands.
 *
 * @class    WC_CLI_Command
 * @version  2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Command extends WP_CLI_Command {

	/**
	 * Add common cli arguments to argument list before WP_Query is run.
	 *
	 * @since  2.5.0
	 * @param  array $base_args  Required arguments for the query (e.g. `post_type`, etc)
	 * @param  array $assoc_args Arguments provided in when invoking the command
	 * @return array
	 */
	protected function merge_wp_query_args( $base_args, $assoc_args ) {
		$args = array();

		// date
		if ( ! empty( $assoc_args['created_at_min'] ) || ! empty( $assoc_args['created_at_max'] ) || ! empty( $assoc_args['updated_at_min'] ) || ! empty( $assoc_args['updated_at_max'] ) ) {

			$args['date_query'] = array();

			// resources created after specified date
			if ( ! empty( $assoc_args['created_at_min'] ) ) {
				$args['date_query'][] = array( 'column' => 'post_date_gmt', 'after' => $this->parse_datetime( $assoc_args['created_at_min'] ), 'inclusive' => true );
			}

			// resources created before specified date
			if ( ! empty( $assoc_args['created_at_max'] ) ) {
				$args['date_query'][] = array( 'column' => 'post_date_gmt', 'before' => $this->parse_datetime( $assoc_args['created_at_max'] ), 'inclusive' => true );
			}

			// resources updated after specified date
			if ( ! empty( $assoc_args['updated_at_min'] ) ) {
				$args['date_query'][] = array( 'column' => 'post_modified_gmt', 'after' => $this->parse_datetime( $assoc_args['updated_at_min'] ), 'inclusive' => true );
			}

			// resources updated before specified date
			if ( ! empty( $assoc_args['updated_at_max'] ) ) {
				$args['date_query'][] = array( 'column' => 'post_modified_gmt', 'before' => $this->parse_datetime( $assoc_args['updated_at_max'] ), 'inclusive' => true );
			}
		}

		// Search.
		if ( ! empty( $assoc_args['q'] ) ) {
			$args['s'] = $assoc_args['q'];
		}

		// Number of post to show per page.
		if ( ! empty( $assoc_args['limit'] ) ) {
			$args['posts_per_page'] = $assoc_args['limit'];
		}

		// Number of post to displace or pass over.
		if ( ! empty( $assoc_args['offset'] ) ) {
			$args['offset'] = $assoc_args['offset'];
		}

		// order (ASC or DESC, DESC by default).
		if ( ! empty( $assoc_args['order'] ) ) {
			$args['order'] = $assoc_args['order'];
		}

		// orderby.
		if ( ! empty( $assoc_args['orderby'] ) ) {
			$args['orderby'] = $assoc_args['orderby'];

			// allow sorting by meta value
			if ( ! empty( $assoc_args['orderby_meta_key'] ) ) {
				$args['meta_key'] = $assoc_args['orderby_meta_key'];
			}
		}

		// allow post status change
		if ( ! empty( $assoc_args['post_status'] ) ) {
			$args['post_status'] = $assoc_args['post_status'];
			unset( $assoc_args['post_status'] );
		}

		// filter by a list of post ids
		if ( ! empty( $assoc_args['in'] ) ) {
			$args['post__in'] = explode( ',', $assoc_args['in'] );
			unset( $assoc_args['in'] );
		}

		// exclude by a list of post ids
		if ( ! empty( $assoc_args['not_in'] ) ) {
			$args['post__not_in'] = explode( ',', $assoc_args['not_in'] );
			unset( $assoc_args['not_in'] );
		}

		// posts page.
		$args['paged'] = ( isset( $assoc_args['page'] ) ) ? absint( $assoc_args['page'] ) : 1;

		$args = apply_filters( 'woocommerce_cli_query_args', $args, $assoc_args );

		return array_merge( $base_args, $args );
	}

	/**
	 * Add common cli arguments to argument list before WP_User_Query is run.
	 *
	 * @since  2.5.0
	 * @param  array $base_args required arguments for the query (e.g. `post_type`, etc)
	 * @param  array $assoc_args arguments provided in when invoking the command
	 * @return array
	 */
	protected function merge_wp_user_query_args( $base_args, $assoc_args ) {
		$args = array();

		// Custom Role
		if ( ! empty( $assoc_args['role'] ) ) {
			$args['role'] = $assoc_args['role'];
		}

		// Search
		if ( ! empty( $assoc_args['q'] ) ) {
			$args['search'] = $assoc_args['q'];
		}

		// Limit number of users returned.
		if ( ! empty( $assoc_args['limit'] ) ) {
			$args['number'] = absint( $assoc_args['limit'] );
		}

		// Offset
		if ( ! empty( $assoc_args['offset'] ) ) {
			$args['offset'] = absint( $assoc_args['offset'] );
		}

		// date
		if ( ! empty( $assoc_args['created_at_min'] ) || ! empty( $assoc_args['created_at_max'] ) ) {

			$args['date_query'] = array();

			// resources created after specified date
			if ( ! empty( $assoc_args['created_at_min'] ) ) {
				$args['date_query'][] = array( 'after' => $this->parse_datetime( $assoc_args['created_at_min'] ), 'inclusive' => true );
			}

			// resources created before specified date
			if ( ! empty( $assoc_args['created_at_max'] ) ) {
				$args['date_query'][] = array( 'before' => $this->parse_datetime( $assoc_args['created_at_max'] ), 'inclusive' => true );
			}
		}

		// Order (ASC or DESC, ASC by default).
		if ( ! empty( $assoc_args['order'] ) ) {
			$args['order'] = $assoc_args['order'];
		}

		// Orderby.
		if ( ! empty( $assoc_args['orderby'] ) ) {
			$args['orderby'] = $assoc_args['orderby'];
		}

		$args = apply_filters( 'woocommerce_cli_user_query_args', $args, $assoc_args );

		return array_merge( $base_args, $args );
	}

	/**
	 * Parse an RFC3339 datetime into a MySQl datetime.
	 *
	 * Invalid dates default to unix epoch.
	 *
	 * @since  2.5.0
	 * @param  string $datetime RFC3339 datetime
	 * @return string MySQl datetime (YYYY-MM-DD HH:MM:SS)
	 */
	protected function parse_datetime( $datetime ) {
		// Strip millisecond precision (a full stop followed by one or more digits)
		if ( strpos( $datetime, '.' ) !== false ) {
			$datetime = preg_replace( '/\.\d+/', '', $datetime );
		}

		// default timezone to UTC
		$datetime = preg_replace( '/[+-]\d+:+\d+$/', '+00:00', $datetime );

		try {

			$datetime = new DateTime( $datetime, new DateTimeZone( 'UTC' ) );

		} catch ( Exception $e ) {

			$datetime = new DateTime( '@0' );

		}

		return $datetime->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Format a unix timestamp or MySQL datetime into an RFC3339 datetime.
	 *
	 * @since  2.5.0
	 * @param  int|string $timestamp unix timestamp or MySQL datetime
	 * @param  bool $convert_to_utc
	 * @return string RFC3339 datetime
	 */
	protected function format_datetime( $timestamp, $convert_to_utc = false ) {
		if ( $convert_to_utc ) {
			$timezone = new DateTimeZone( wc_timezone_string() );
		} else {
			$timezone = new DateTimeZone( 'UTC' );
		}

		try {
			if ( is_numeric( $timestamp ) ) {
				$date = new DateTime( "@{$timestamp}" );
			} else {
				$date = new DateTime( $timestamp, $timezone );
			}

			// convert to UTC by adjusting the time based on the offset of the site's timezone
			if ( $convert_to_utc ) {
				$date->modify( -1 * $date->getOffset() . ' seconds' );
			}

		} catch ( Exception $e ) {
			$date = new DateTime( '@0' );
		}

		return $date->format( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * Get formatter object based on supplied arguments.
	 *
	 * @since  2.5.0
	 * @param  array $assoc_args Associative args from CLI to determine formatting
	 * @return \WP_CLI\Formatter
	 */
	protected function get_formatter( $assoc_args ) {
		$args = $this->get_format_args( $assoc_args );
		return new \WP_CLI\Formatter( $args );
	}

	/**
	 * Get default fields for formatter.
	 *
	 * Class that extends WC_CLI_Command should override this method.
	 *
	 * @since  2.5.0
	 * @return null|string|array
	 */
	protected function get_default_format_fields() {
		return null;
	}

	/**
	 * Get format args that will be passed into CLI Formatter.
	 *
	 * @since  2.5.0
	 * @param  array $assoc_args Associative args from CLI
	 * @return array Formatter args
	 */
	protected function get_format_args( $assoc_args ) {
		$format_args = array(
			'fields' => $this->get_default_format_fields(),
			'field'  => null,
			'format' => 'table',
		);

		if ( isset( $assoc_args['fields'] ) ) {
			$format_args['fields'] = $assoc_args['fields'];
		}

		if ( isset( $assoc_args['field'] ) ) {
			$format_args['field'] = $assoc_args['field'];
		}

		if ( ! empty( $assoc_args['format'] ) && in_array( $assoc_args['format'], array( 'count', 'ids', 'table', 'csv', 'json' ) ) ) {
			$format_args['format'] = $assoc_args['format'];
		}

		return $format_args;
	}

	/**
	 * Flatten multidimensional array in which nested array will be prefixed with
	 * parent keys separated with dot char, e.g. given an array:
	 *
	 *     array(
	 *         'a' => array(
	 *             'b' => array(
	 *                 'c' => ...
	 *             )
	 *         )
	 *     )
	 *
	 * a flatten array would contain key 'a.b.c' => ...
	 *
	 * @since 2.5.0
	 * @param array  $arr    Array that may contains nested array
	 * @param string $prefix Prefix
	 *
	 * @return array Flattened array
	 */
	protected function flatten_array( $arr, $prefix = '' ) {
		$flattened = array();
		foreach ( $arr as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( sizeof( $value ) > 0 ) {

					// Full access to whole elements if indices are numerical.
					$flattened[ $prefix . $key ] = $value;

					// This is naive assumption that if element with index zero
					// exists then array indices are numberical.
					if ( ! empty( $value[0] ) ) {

						// Allow size of array to be accessed, i.e., a.b.arr.size
						$flattened[ $prefix . $key . '.size' ] = sizeof( $value );
					}

					$flattened = array_merge( $flattened, $this->flatten_array( $value, $prefix . $key . '.' ) );
				} else {
					$flattened[ $prefix . $key ] = '';

					// Tells user that size of this array is zero.
					$flattened[ $prefix . $key . '.size' ] = 0;
				}
			} else {
				$flattened[ $prefix . $key ] = $value;
			}
		}

		return $flattened;
	}

	/**
	 * Unflatten array will make key 'a.b.c' becomes nested array:
	 *
	 *     array(
	 *         'a' => array(
	 *             'b' => array(
	 *                 'c' => ...
	 *             )
	 *         )
	 *     )
	 *
	 * @since  2.5.0
	 * @param  array $arr Flattened array
	 * @return array
	 */
	protected function unflatten_array( $arr ) {
		$unflatten = array();

		foreach ( $arr as $key => $value ) {
			$key_list  = explode( '.', $key );
			$first_key = array_shift( $key_list );
			$first_key = $this->get_normalized_array_key( $first_key );
			if ( sizeof( $key_list ) > 0 ) {
				$remaining_keys = implode( '.', $key_list );
				$subarray       = $this->unflatten_array( array( $remaining_keys => $value ) );

				foreach ( $subarray as $sub_key => $sub_value ) {
					$sub_key = $this->get_normalized_array_key( $sub_key );
					if ( ! empty( $unflatten[ $first_key ][ $sub_key ] ) ) {
						$unflatten[ $first_key ][ $sub_key ] = array_merge_recursive( $unflatten[ $first_key ][ $sub_key ], $sub_value );
					} else {
						$unflatten[ $first_key ][ $sub_key ] = $sub_value;
					}
				}
			} else {
				$unflatten[ $first_key ] = $value;
			}
		}

		return $unflatten;
	}

	/**
	 * Get normalized array key. If key is a numeric one it will be converted
	 * as absolute integer.
	 *
	 * @since  2.5.0
	 * @param  string $key Array key
	 * @return string|int
	 */
	protected function get_normalized_array_key( $key ) {
		if ( is_numeric( $key ) ) {
			$key = absint( $key );
		}
		return $key;
	}

	/**
	 * Check if the value is equal to 'yes', 'true' or '1'
	 *
	 * @since 2.5.4
	 * @param  string $value
	 * @return boolean
	 */
	protected function is_true( $value ) {
		return ( 'yes' === $value || 'true' === $value || '1' === $value ) ? true : false;
	}
}
