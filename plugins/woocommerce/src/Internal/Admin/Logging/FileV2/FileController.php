<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use WP_Error;

/**
 * FileController class.
 */
class FileController {
	/**
	 * @var string The absolute path to the log directory.
	 */
	private $log_directory;

	/**
	 * Class FileController
	 */
	public function __construct() {
		$this->log_directory = trailingslashit( realpath( Constants::get_constant( 'WC_LOG_DIR' ) ) );
	}

	/**
	 * Get an array of log files.
	 *
	 * @param array $args      {
	 *     Optional. Arguments to filter and sort the files that are returned.
	 *
	 *     @type int    $offset   Omit this number of files from the beginning of the list. Works with $per_page to do pagination.
	 *     @type string $order    The sort direction. 'asc' or 'desc'. Defaults to 'desc'.
	 *     @type string $orderby  The property to sort the list by. 'created', 'modified', 'source', 'size'. Defaults to 'modified'.
	 *     @type int    $per_page The number of files to include in the list. Works with $offset to do pagination.
	 *     @type string $source   Only include files from this source.
	 * }
	 * @param bool  $count_only Optional. True to return a total count of the files.
	 *
	 * @return File[]|int|WP_Error
	 */
	public function get_files( array $args = array(), bool $count_only = false ) {
		$defaults = array(
			'offset'   => 0,
			'order'    => 'desc',
			'orderby'  => 'modified',
			'per_page' => 10,
			'source'   => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$pattern = $args['source'] . '*' . '.log';
		$files   = glob( $this->log_directory . $pattern );

		if ( false === $files ) {
			return new WP_Error(
				'wc_log_directory_error',
				__( 'Could not access the log file directory.', 'woocommerce' )
			);
		}

		if ( true === $count_only ) {
			return count( $files );
		}

		$files = array_map(
			function( $file_path ) {
				if ( ! is_readable( $file_path ) ) {
					return null;
				}

				return new File( $file_path );
			},
			$files
		);
		$files = array_filter( $files );

		$multi_sorter = function( $sort_sets, $order_sets ) {
			$comparison = 0;

			while ( ! empty( $sort_sets ) ) {
				$set   = array_shift( $sort_sets );
				$order = array_shift( $order_sets );

				if ( 'desc' === $order ) {
					$comparison = $set[1] <=> $set[0];
				} else {
					$comparison = $set[0] <=> $set[1];
				}

				if ( $comparison !== 0 ) {
					break;
				}
			}

			return $comparison;
		};

		switch ( $args['orderby'] ) {
			case 'created':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets = array(
						array( $a->get_created_timestamp(), $b->get_created_timestamp() ),
						array( $a->get_source(), $b->get_source() ),
					);
					$order_sets = array( $args['order'], 'asc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
			case 'modified':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets = array(
						array( $a->get_modified_timestamp(), $b->get_modified_timestamp() ),
						array( $a->get_source(), $b->get_source() ),
					);
					$order_sets = array( $args['order'], 'asc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
			case 'source':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets = array(
						array( $a->get_source(), $b->get_source() ),
						array( $a->get_created_timestamp(), $b->get_created_timestamp() ),
					);
					$order_sets = array( $args['order'], 'desc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
			case 'size':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets = array(
						array( $a->get_file_size(), $b->get_file_size() ),
						array( $a->get_source(), $b->get_source() ),
					);
					$order_sets = array( $args['order'], 'asc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
		}

		usort( $files, $sort_callback );

		return array_slice( $files, $args['offset'], $args['per_page'] );
	}
}
