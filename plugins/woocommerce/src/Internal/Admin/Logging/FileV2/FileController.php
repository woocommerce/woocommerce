<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\Settings;
use PclZip;
use WC_Cache_Helper;
use WP_Error;

/**
 * FileController class.
 */
class FileController {
	/**
	 * The maximum number of rotations for a file before they start getting overwritten.
	 *
	 * This number should not go above 10, or it will cause issues with the glob patterns.
	 *
	 * const int
	 */
	private const MAX_FILE_ROTATIONS = 10;

	/**
	 * Default values for arguments for the get_files method.
	 *
	 * @const array
	 */
	public const DEFAULTS_GET_FILES = array(
		'date_end'    => 0,
		'date_filter' => '',
		'date_start'  => 0,
		'offset'      => 0,
		'order'       => 'desc',
		'orderby'     => 'modified',
		'per_page'    => 20,
		'source'      => '',
	);

	/**
	 * Default values for arguments for the search_within_files method.
	 *
	 * @const array
	 */
	public const DEFAULTS_SEARCH_WITHIN_FILES = array(
		'offset'   => 0,
		'per_page' => 50,
	);

	/**
	 * The maximum number of files that can be searched at one time.
	 *
	 * @const int
	 */
	public const SEARCH_MAX_FILES = 100;

	/**
	 * The maximum number of search results that can be returned at one time.
	 *
	 * @const int
	 */
	public const SEARCH_MAX_RESULTS = 200;

	/**
	 * The cache group name to use for caching operations.
	 *
	 * @const string
	 */
	private const CACHE_GROUP = 'log-files';

	/**
	 * A cache key for storing and retrieving the results of the last logs search.
	 *
	 * @const string
	 */
	private const SEARCH_CACHE_KEY = 'logs_previous_search';

	/**
	 * Get the file size limit that determines when to rotate a file.
	 *
	 * @return int
	 */
	private function get_file_size_limit(): int {
		$default = 5 * MB_IN_BYTES;

		/**
		 * Filter the threshold size of a log file at which point it will get rotated.
		 *
		 * @since 3.4.0
		 *
		 * @param int $file_size_limit The file size limit in bytes.
		 */
		$file_size_limit = apply_filters( 'woocommerce_log_file_size_limit', $default );

		if ( ! is_int( $file_size_limit ) || $file_size_limit < 1 ) {
			return $default;
		}

		return $file_size_limit;
	}

	/**
	 * Write a log entry to the appropriate file, after rotating the file if necessary.
	 *
	 * @param string   $source The source property of the log entry, which determines which file to write to.
	 * @param string   $text   The contents of the log entry to add to a file.
	 * @param int|null $time   Optional. The time of the log entry as a Unix timestamp. Defaults to the current time.
	 *
	 * @return bool True if the contents were successfully written to the file.
	 */
	public function write_to_file( string $source, string $text, ?int $time = null ): bool {
		if ( is_null( $time ) ) {
			$time = time();
		}

		$file_id = File::generate_file_id( $source, null, $time );
		$file    = $this->get_file_by_id( $file_id );

		if ( $file instanceof File && $file->get_file_size() >= $this->get_file_size_limit() ) {
			$rotated = $this->rotate_file( $file->get_file_id() );

			if ( $rotated ) {
				$file = null;
			} else {
				return false;
			}
		}

		if ( ! $file instanceof File ) {
			$new_path = Settings::get_log_directory() . $this->generate_filename( $source, $time );
			$file     = new File( $new_path );
		}

		return $file->write( $text );
	}

	/**
	 * Generate the full name of a file based on source and date values.
	 *
	 * @param string $source The source property of a log entry, which determines the filename.
	 * @param int    $time   The time of the log entry as a Unix timestamp.
	 *
	 * @return string
	 */
	private function generate_filename( string $source, int $time ): string {
		$file_id = File::generate_file_id( $source, null, $time );
		$hash    = File::generate_hash( $file_id );

		return "$file_id-$hash.log";
	}

	/**
	 * Get all the rotations of a file and increment them, so that they overwrite the previous file with that rotation.
	 *
	 * @param string $file_id A file ID (file basename without the hash).
	 *
	 * @return bool True if the file and all its rotations were successfully rotated.
	 */
	private function rotate_file( $file_id ): bool {
		$rotations = $this->get_file_rotations( $file_id );

		if ( is_wp_error( $rotations ) || ! isset( $rotations['current'] ) ) {
			return false;
		}

		$max_rotation_marker = self::MAX_FILE_ROTATIONS - 1;

		// Don't rotate a file with the maximum rotation.
		unset( $rotations[ $max_rotation_marker ] );

		$results = array();
		// Rotate starting with oldest first and working backwards.
		for ( $i = $max_rotation_marker; $i >= 0; $i -- ) {
			if ( isset( $rotations[ $i ] ) ) {
				$results[] = $rotations[ $i ]->rotate();
			}
		}
		$results[] = $rotations['current']->rotate();

		return ! in_array( false, $results, true );
	}

	/**
	 * Get an array of log files.
	 *
	 * @param array $args      {
	 *     Optional. Arguments to filter and sort the files that are returned.
	 *
	 *     @type int    $date_end    The end of the date range to filter by, as a Unix timestamp.
	 *     @type string $date_filter Filter files by one of the date props. 'created' or 'modified'.
	 *     @type int    $date_start  The beginning of the date range to filter by, as a Unix timestamp.
	 *     @type int    $offset      Omit this number of files from the beginning of the list. Works with $per_page to do pagination.
	 *     @type string $order       The sort direction. 'asc' or 'desc'. Defaults to 'desc'.
	 *     @type string $orderby     The property to sort the list by. 'created', 'modified', 'source', 'size'. Defaults to 'modified'.
	 *     @type int    $per_page    The number of files to include in the list. Works with $offset to do pagination.
	 *     @type string $source      Only include files from this source.
	 * }
	 * @param bool  $count_only Optional. True to return a total count of the files.
	 *
	 * @return File[]|int|WP_Error
	 */
	public function get_files( array $args = array(), bool $count_only = false ) {
		$args = wp_parse_args( $args, self::DEFAULTS_GET_FILES );

		$pattern = $args['source'] . '*.log';
		$paths   = glob( Settings::get_log_directory() . $pattern );

		if ( false === $paths ) {
			return new WP_Error(
				'wc_log_directory_error',
				__( 'Could not access the log file directory.', 'woocommerce' )
			);
		}

		$files = $this->convert_paths_to_objects( $paths );

		if ( $args['date_filter'] && $args['date_start'] && $args['date_end'] ) {
			switch ( $args['date_filter'] ) {
				case 'created':
					$files = array_filter(
						$files,
						fn( $file ) => $file->get_created_timestamp() >= $args['date_start']
							&& $file->get_created_timestamp() <= $args['date_end']
					);
					break;
				case 'modified':
					$files = array_filter(
						$files,
						fn( $file ) => $file->get_modified_timestamp() >= $args['date_start']
							&& $file->get_modified_timestamp() <= $args['date_end']
					);
					break;
			}
		}

		if ( true === $count_only ) {
			return count( $files );
		}

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
		$log_directory = Settings::get_log_directory();
		$paths         = array();

		foreach ( $file_ids as $file_id ) {
			// Look for the standard filename format first, which includes a hash.
			$glob = glob( $log_directory . $file_id . '-*.log' );

			if ( ! $glob ) {
				$glob = glob( $log_directory . $file_id . '.log' );
			}

			if ( is_array( $glob ) ) {
				$paths = array_merge( $paths, $glob );
			}
		}

		$files = $this->convert_paths_to_objects( array_unique( $paths ) );

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
				esc_html__( 'This file does not exist.', 'woocommerce' )
			);
		}

		if ( count( $result ) > 1 ) {
			return new WP_Error(
				'wc_log_file_error',
				esc_html__( 'Multiple files match this ID.', 'woocommerce' )
			);
		}

		return reset( $result );
	}

	/**
	 * Get File instances for a given file ID and all of its related rotations.
	 *
	 * @param string $file_id A file ID (file basename without the hash).
	 *
	 * @return File[]|WP_Error An associative array where the rotation integer of the file is the key, and a "current"
	 *                         key for the iteration of the file that hasn't been rotated (if it exists).
	 */
	public function get_file_rotations( string $file_id ) {
		$file = $this->get_file_by_id( $file_id );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$current   = array();
		$rotations = array();

		$source  = $file->get_source();
		$created = 0;
		if ( $file->has_standard_filename() ) {
			$created = $file->get_created_timestamp();
		}

		if ( is_null( $file->get_rotation() ) ) {
			$current['current'] = $file;
		} else {
			$current_file_id = File::generate_file_id( $source, null, $created );
			$result          = $this->get_file_by_id( $current_file_id );
			if ( ! is_wp_error( $result ) ) {
				$current['current'] = $result;
			}
		}

		$rotations_pattern = sprintf(
			'.[%s]',
			implode(
				'',
				range( 0, self::MAX_FILE_ROTATIONS - 1 )
			)
		);

		$created_pattern = $created ? '-' . gmdate( 'Y-m-d', $created ) . '-' : '';

		$rotation_pattern = Settings::get_log_directory() . $source . $rotations_pattern . $created_pattern . '*.log';
		$rotation_paths   = glob( $rotation_pattern );
		$rotation_files   = $this->convert_paths_to_objects( $rotation_paths );
		foreach ( $rotation_files as $rotation_file ) {
			if ( $rotation_file->is_readable() ) {
				$rotations[ $rotation_file->get_rotation() ] = $rotation_file;
			}
		}

		ksort( $rotations );

		return array_merge( $current, $rotations );
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
		$paths = glob( Settings::get_log_directory() . '*.log' );
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
	 * @return int The number of files that were deleted.
	 */
	public function delete_files( array $file_ids ): int {
		$deleted = 0;

		$files = $this->get_files_by_id( $file_ids );
		foreach ( $files as $file ) {
			$result = $file->delete();

			if ( true === $result ) {
				$deleted ++;
			}
		}

		if ( $deleted > 0 ) {
			$this->invalidate_cache();
		}

		return $deleted;
	}

	/**
	 * Stream a single file to the browser without zipping it first.
	 *
	 * @param string $file_id A file ID (file basename without the hash).
	 *
	 * @return WP_Error|void Only returns something if there is an error.
	 */
	public function export_single_file( $file_id ) {
		$file = $this->get_file_by_id( $file_id );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$file_name = $file->get_file_id() . '.log';
		$exporter  = new FileExporter( $file->get_path(), $file_name );

		return $exporter->emit_file();
	}

	/**
	 * Create a zip archive of log files and stream it to the browser.
	 *
	 * @param array $file_ids An array of file IDs (file basename without the hash).
	 *
	 * @return WP_Error|void Only returns something if there is an error.
	 */
	public function export_multiple_files( array $file_ids ) {
		$files = $this->get_files_by_id( $file_ids );

		if ( count( $files ) < 1 ) {
			return new WP_Error(
				'wc_logs_invalid_file',
				__( 'Could not access the specified files.', 'woocommerce' )
			);
		}

		$temp_dir = get_temp_dir();

		if ( ! is_dir( $temp_dir ) || ! wp_is_writable( $temp_dir ) ) {
			return new WP_Error(
				'wc_logs_invalid_directory',
				__( 'Could not write to the temp directory. Try downloading files one at a time instead.', 'woocommerce' )
			);
		}

		require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

		$path       = trailingslashit( $temp_dir ) . 'woocommerce_logs_' . gmdate( 'Y-m-d_H-i-s' ) . '.zip';
		$file_paths = array_map(
			fn( $file ) => $file->get_path(),
			$files
		);
		$archive    = new PclZip( $path );

		$archive->create( $file_paths, PCLZIP_OPT_REMOVE_ALL_PATH );

		$exporter = new FileExporter( $path );

		return $exporter->emit_file();
	}

	/**
	 * Search within a set of log files for a particular string.
	 *
	 * @param string $search     The string to search for.
	 * @param array  $args       Optional. Arguments for pagination of search results.
	 * @param array  $file_args  Optional. Arguments to filter and sort the files that are returned. See get_files().
	 * @param bool   $count_only Optional. True to return a total count of the matches.
	 *
	 * @return array|int|WP_Error When matches are found, each array item is an associative array that includes the
	 *                            file ID, line number, and the matched string with HTML markup around the matched parts.
	 */
	public function search_within_files( string $search, array $args = array(), array $file_args = array(), bool $count_only = false ) {
		if ( '' === $search ) {
			return $count_only ? 0 : array();
		}

		$search = esc_html( $search );

		$args = wp_parse_args( $args, self::DEFAULTS_SEARCH_WITHIN_FILES );

		$file_args = array_merge(
			$file_args,
			array(
				'offset'   => 0,
				'per_page' => self::SEARCH_MAX_FILES,
			)
		);

		$cache_key = WC_Cache_Helper::get_prefixed_key( self::SEARCH_CACHE_KEY, self::CACHE_GROUP );
		$query     = wp_json_encode( array( $search, $args, $file_args ) );
		$cache     = wp_cache_get( $cache_key );
		$is_cached = isset( $cache['query'], $cache['results'] ) && $query === $cache['query'];

		if ( true === $is_cached ) {
			$matched_lines = $cache['results'];
		} else {
			$files = $this->get_files( $file_args );
			if ( is_wp_error( $files ) ) {
				return $files;
			}

			// Max string size * SEARCH_MAX_RESULTS = ~1MB largest possible cache entry.
			$max_string_size = 5 * KB_IN_BYTES;

			$matched_lines = array();

			foreach ( $files as $file ) {
				$stream      = $file->get_stream();
				$line_number = 1;

				while ( ! feof( $stream ) ) {
					$line = fgets( $stream, $max_string_size );
					if ( ! is_string( $line ) ) {
						continue;
					}

					$sanitized_line = esc_html( trim( $line ) );
					if ( false !== stripos( $sanitized_line, $search ) ) {
						$matched_lines[] = array(
							'file_id'     => $file->get_file_id(),
							'line_number' => $line_number,
							'line'        => $sanitized_line,
						);
					}

					if ( count( $matched_lines ) >= self::SEARCH_MAX_RESULTS ) {
						$file->close_stream();
						break 2;
					}

					if ( false !== strstr( $line, PHP_EOL ) ) {
						$line_number ++;
					}
				}

				$file->close_stream();
			}

			$to_cache = array(
				'query'   => $query,
				'results' => $matched_lines,
			);
			wp_cache_set( $cache_key, $to_cache, self::CACHE_GROUP, DAY_IN_SECONDS );
		}

		if ( true === $count_only ) {
			return count( $matched_lines );
		}

		return array_slice( $matched_lines, $args['offset'], $args['per_page'] );
	}

	/**
	 * Calculate the size, in bytes, of the log directory.
	 *
	 * @return int
	 */
	public function get_log_directory_size(): int {
		$bytes = 0;
		$path  = realpath( Settings::get_log_directory( false ) );

		if ( wp_is_writable( $path ) ) {
			$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CATCH_GET_CHILD );

			foreach ( $iterator as $file ) {
				$bytes += $file->getSize();
			}
		}

		return $bytes;
	}

	/**
	 * Invalidate the cache group related to log file data.
	 *
	 * @return bool True on successfully invalidating the cache.
	 */
	public function invalidate_cache(): bool {
		return WC_Cache_Helper::invalidate_cache_group( self::CACHE_GROUP );
	}
}
