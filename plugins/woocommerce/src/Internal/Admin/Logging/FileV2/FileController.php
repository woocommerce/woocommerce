<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use WP_Error;

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
	 *     @type string $order    The sort direction. 'asc' or 'desc'.
	 *     @type string $orderby  The property to sort the list by. 'filename', 'modified', 'size'. Defaults to 'filename'.
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
			'order'    => 'asc',
			'orderby'  => 'filename',
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

		$sort_callback = function( $a, $b ) use ( $args ) {
			if ( $a === $b ) {
				return 0;
			}

			$compare = $a < $b;

			if ( 'desc' === $args['order'] ) {
				return $compare ? 1 : -1;
			}

			return $compare ? -1 : 1;
		};

		switch ( $args['orderby'] ) {
			case 'filename':
				usort(
					$files,
					function( $a, $b ) use ( $sort_callback ) {
						return $sort_callback( $a->get_filename(), $b->get_filename() );
					}
				);
				break;
			case 'modified':
				usort(
					$files,
					function( $a, $b ) use ( $sort_callback ) {
						return $sort_callback( $a->get_modified_timestamp(), $b->get_modified_timestamp() );
					}
				);
				break;
			case 'size':
				usort(
					$files,
					function( $a, $b ) use ( $sort_callback ) {
						return $sort_callback( $a->get_file_size(), $b->get_file_size() );
					}
				);
				break;
		}

		return array_slice( $files, $args['offset'], $args['per_page'] );
	}
}
