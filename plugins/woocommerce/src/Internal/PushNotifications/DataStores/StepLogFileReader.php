<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\DataStores;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\File;
use Automattic\WooCommerce\Internal\Admin\Logging\Settings;
use WC_Log_Levels;

/**
 * Reads push notification step log lines back from the WooCommerce log files.
 *
 * Files are addressed by day, never by listing the directory: the file handler
 * names each file from its source and UTC date plus a hash of that name, so
 * the path for any day can be built without a glob. Each file is streamed one
 * line at a time, so memory is one line plus the matches whatever the file
 * size.
 *
 * @since 11.3.0
 */
class StepLogFileReader {
	/**
	 * The file handler keeps this many rotations of one day's file before
	 * overwriting the oldest, so this is the highest rotation suffix to probe.
	 */
	const MAX_ROTATIONS = 10;

	/**
	 * Returns every line written under a source between two times.
	 *
	 * @param string $source The log source, e.g. `push-token-4412`.
	 * @param int    $from   Earliest timestamp to include, inclusive.
	 * @param int    $to     Latest timestamp to include, inclusive.
	 * @return array<int, array{timestamp: int, level: string, message: string, context: array|null, raw: string|null}>
	 *
	 * @since 11.3.0
	 */
	public function read( string $source, int $from, int $to ): array {
		$rows = array();

		foreach ( $this->get_paths( $source, $from, $to ) as $path ) {
			$file = new File( $path );

			if ( ! $file->is_readable() ) {
				continue;
			}

			$stream = $file->get_stream();

			if ( ! is_resource( $stream ) ) {
				continue;
			}

			while ( false !== ( $line = fgets( $stream ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				$row = self::parse_line( $line );

				if ( null === $row || $row['timestamp'] < $from || $row['timestamp'] > $to ) {
					continue;
				}

				$rows[] = $row;
			}

			$file->close_stream();
		}

		usort( $rows, fn( array $a, array $b ) => $a['timestamp'] <=> $b['timestamp'] );

		return $rows;
	}

	/**
	 * Builds the path of every file that could hold lines for the source
	 * between two times: one per UTC day, plus that day's rotations.
	 *
	 * @param string $source The log source.
	 * @param int    $from   Earliest timestamp.
	 * @param int    $to     Latest timestamp.
	 * @return string[]
	 */
	private function get_paths( string $source, int $from, int $to ): array {
		$directory = Settings::get_log_directory( false );
		$paths     = array();
		$day       = (int) strtotime( gmdate( 'Y-m-d', $from ) . 'T00:00:00+00:00' );

		while ( $day <= $to ) {
			for ( $rotation = self::MAX_ROTATIONS - 1; $rotation >= 0; $rotation-- ) {
				$paths[] = $directory . self::build_filename( $source, $rotation, $day );
			}

			$paths[] = $directory . self::build_filename( $source, null, $day );

			$day += DAY_IN_SECONDS;
		}

		return $paths;
	}

	/**
	 * Builds a log filename the way the file handler does when writing.
	 *
	 * @param string   $source   The log source.
	 * @param int|null $rotation The rotation suffix, or null for the current file.
	 * @param int      $day      A timestamp within the UTC day.
	 * @return string
	 */
	private static function build_filename( string $source, ?int $rotation, int $day ): string {
		$file_id = File::generate_file_id( $source, $rotation, $day );

		return $file_id . '-' . File::generate_hash( $file_id ) . '.log';
	}

	/**
	 * Parses one line as the file handler wrote it: a timestamp, a level, the
	 * message, and an optional ` CONTEXT: ` JSON block.
	 *
	 * A line whose context does not decode is still returned, with the raw
	 * text in `raw` and no context, so a plugin reshaping the line through
	 * `woocommerce_format_log_entry` degrades the row rather than the request.
	 *
	 * @param string $line The line.
	 * @return array{timestamp: int, level: string, message: string, context: array|null, raw: string|null}|null Null for a line with no timestamp and level.
	 *
	 * @since 11.3.0
	 */
	public static function parse_line( string $line ): ?array {
		$line     = rtrim( $line, "\r\n" );
		$segments = explode( ' ', $line, 3 );

		if ( count( $segments ) < 3 ) {
			return null;
		}

		$timestamp = strtotime( $segments[0] );
		$level     = strtolower( $segments[1] );

		if ( false === $timestamp || ! WC_Log_Levels::is_valid_level( $level ) ) {
			return null;
		}

		$chunks  = explode( ' CONTEXT: ', $segments[2], 2 );
		$context = null;
		$raw     = null;

		if ( isset( $chunks[1] ) ) {
			$decoded = json_decode( $chunks[1], true );

			if ( is_array( $decoded ) ) {
				$context = $decoded;
			} else {
				$raw = $line;
			}
		}

		return array(
			'timestamp' => $timestamp,
			'level'     => $level,
			'message'   => $chunks[0],
			'context'   => $context,
			'raw'       => $raw,
		);
	}
}
