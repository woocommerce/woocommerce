<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use WP_Error;

/**
 * FileController class.
 */
class FileController {
	/**
	 * Default values for arguments for the get_files method.
	 *
	 * @const array
	 */
	public const DEFAULTS_GET_FILES = array(
		'offset'   => 0,
		'order'    => 'desc',
		'orderby'  => 'modified',
		'per_page' => 20,
		'source'   => '',
	);

	/**
	 * The absolute path to the log directory.
	 *
	 * @var string
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
		$args = wp_parse_args( $args, self::DEFAULTS_GET_FILES );

		$pattern = $args['source'] . '*.log';
		$paths   = glob( $this->log_directory . $pattern );

		if ( false === $paths ) {
			return new WP_Error(
				'wc_log_directory_error',
				__( 'Could not access the log file directory.', 'woocommerce' )
			);
		}

		if ( true === $count_only ) {
			return count( $paths );
		}

		$files = $this->convert_paths_to_objects( $paths );

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

				if ( 0 !== $comparison ) {
					break;
				}
			}

			return $comparison;
		};

		switch ( $args['orderby'] ) {
			case 'created':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets  = array(
						array( $a->get_created_timestamp(), $b->get_created_timestamp() ),
						array( $a->get_source(), $b->get_source() ),
						array( $a->get_rotation() || -1, $b->get_rotation() || -1 ),
					);
					$order_sets = array( $args['order'], 'asc', 'asc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
			case 'modified':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets  = array(
						array( $a->get_modified_timestamp(), $b->get_modified_timestamp() ),
						array( $a->get_source(), $b->get_source() ),
						array( $a->get_rotation() || -1, $b->get_rotation() || -1 ),
					);
					$order_sets = array( $args['order'], 'asc', 'asc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
			case 'source':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets  = array(
						array( $a->get_source(), $b->get_source() ),
						array( $a->get_created_timestamp(), $b->get_created_timestamp() ),
						array( $a->get_rotation() || -1, $b->get_rotation() || -1 ),
					);
					$order_sets = array( $args['order'], 'desc', 'asc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
			case 'size':
				$sort_callback = function( $a, $b ) use ( $args, $multi_sorter ) {
					$sort_sets  = array(
						array( $a->get_file_size(), $b->get_file_size() ),
						array( $a->get_source(), $b->get_source() ),
						array( $a->get_rotation() || -1, $b->get_rotation() || -1 ),
					);
					$order_sets = array( $args['order'], 'asc', 'asc' );
					return $multi_sorter( $sort_sets, $order_sets );
				};
				break;
		}

		usort( $files, $sort_callback );

		return array_slice( $files, $args['offset'], $args['per_page'] );
	}

	/**
	 * Get one or more File instances from an array of file IDs.
	 *
	 * @param array $file_ids An array of file IDs (file basename without the hash).
	 *
	 * @return File[]
	 */
	public function get_files_by_id( array $file_ids ): array {
		$paths = array();

		foreach ( $file_ids as $file_id ) {
			$glob = glob( $this->log_directory . $file_id . '*.log' );

			$paths = array_merge( $paths, $glob );
		}

		$files = $this->convert_paths_to_objects( $paths );

		return $files;
	}

	/**
	 * Get a File instance from a file ID.
	 *
	 * @param string $file_id A file ID (file basename without the hash).
	 *
	 * @return File|WP_Error
	 */
	public function get_file_by_id( string $file_id ) {
		$result = $this->get_files_by_id( array( $file_id ) );

		if ( count( $result ) < 1 ) {
			return new WP_Error(
				'wc_log_file_error',
				esc_html__( 'Could not read the specified file.', 'woocommerce' )
			);
		}

		return reset( $result );
	}

	/**
	 * Helper method to get an array of File instances.
	 *
	 * @param array $paths An array of absolute file paths.
	 *
	 * @return File[]
	 */
	private function convert_paths_to_objects( array $paths ): array {
		$files = array_map(
			function( $path ) {
				$file = new File( $path );
				return $file->is_readable() ? $file : null;
			},
			$paths
		);

		return array_filter( $files );
	}

	/**
	 * Get a list of sources for existing log files.
	 *
	 * @return array|WP_Error
	 */
	public function get_file_sources() {
		$paths = glob( $this->log_directory . '*.log' );
		if ( false === $paths ) {
			return new WP_Error(
				'wc_log_directory_error',
				__( 'Could not access the log file directory.', 'woocommerce' )
			);
		}

		$all_sources = array_map(
			function( $path ) {
				$file = new File( $path );
				return $file->is_readable() ? $file->get_source() : null;
			},
			$paths
		);

		return array_unique( array_filter( $all_sources ) );
	}

	/**
	 * Delete one or more files from the filesystem.
	 *
	 * @param array $file_ids An array of file IDs (file basename without the hash).
	 *
	 * @return int
	 */
	public function delete_files( array $file_ids ): int {
		$deleted = 0;

		$files = $this->get_files_by_id( $file_ids );
		foreach ( $files as $file ) {
			$result = false;
			if ( $file->is_readable() ) {
				$result = $file->delete();
			}

			if ( true === $result ) {
				$deleted ++;
			}
		}

		return $deleted;
	}

	/**
	 * Sanitize the source property of a log file.
	 *
	 * @param string $source The source property of a log file.
	 *
	 * @return string
	 */
	public function sanitize_source( string $source ): string {
		return sanitize_file_name( $source );
	}
}
