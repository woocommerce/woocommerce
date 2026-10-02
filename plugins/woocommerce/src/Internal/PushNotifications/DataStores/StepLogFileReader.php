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
	 * Returns the newest lines written under a source between two times.
	 *
	 * Reads one UTC day at a time, newest first, and stops once it holds enough
	 * rows, so a short page over a long range does not open every day's files.
	 * Rows are turned into what the caller wants as each line is read, and the
	 * set is trimmed after every file, so neither the range nor one large file
	 * decides how much is held at once.
	 *
	 * @param string        $source The log source, e.g. `push-notifications-store-order`.
	 * @param int           $from   Earliest timestamp to include, inclusive.
	 * @param int           $to     Latest timestamp to include, inclusive.
	 * @param int           $limit  The fewest rows to collect before stopping.
	 * @param callable|null $expand Called with each row, returning a row per result it should produce.
	 * @return array{rows: array<int, array{timestamp: int, level: string, message: string, context: array|null, raw: string|null}>, covered_from: int}
	 *
	 * @since 11.3.0
	 */
	public function read( string $source, int $from, int $to, int $limit = PHP_INT_MAX, ?callable $expand = null ): array {
		$directory    = Settings::get_log_directory( false );
		$rows         = array();
		$covered_from = $to;

		foreach ( self::get_days( $from, $to ) as $day ) {
			$covered_from = max( $from, $day );

			foreach ( self::get_filenames( $source, $day ) as $filename ) {
				$file = new File( $directory . $filename );

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

					if ( null === $expand ) {
						$rows[] = $row;
						continue;
					}

					foreach ( $expand( $row ) as $expanded ) {
						$rows[] = $expanded;
					}
				}

				$file->close_stream();

				$rows = self::keep_newest( $rows, $limit );
			}

			/*
			 * Every remaining day is older than everything held, so stopping at
			 * a day boundary cannot skip a row that belonged on this page. The
			 * boundary is also a second boundary, which the page's whole-second
			 * rule needs.
			 */
			if ( count( $rows ) >= $limit ) {
				break;
			}
		}

		usort( $rows, fn( array $a, array $b ) => $a['timestamp'] <=> $b['timestamp'] );

		return array(
			'rows'         => $rows,
			'covered_from' => $covered_from,
		);
	}

	/**
	 * The UTC days a range covers, newest first.
	 *
	 * @param int $from Earliest timestamp.
	 * @param int $to   Latest timestamp.
	 * @return int[] The start of each day.
	 */
	private static function get_days( int $from, int $to ): array {
		$days  = array();
		$day   = (int) strtotime( gmdate( 'Y-m-d', $from ) . 'T00:00:00+00:00' );
		$until = (int) strtotime( gmdate( 'Y-m-d', $to ) . 'T00:00:00+00:00' );

		while ( $day <= $until ) {
			$days[] = $day;
			$day   += DAY_IN_SECONDS;
		}

		return array_reverse( $days );
	}

	/**
	 * Keeps the newest rows and whatever shares the oldest kept second, so a
	 * trim can never cut a second in half.
	 *
	 * @param array $rows  The rows held so far.
	 * @param int   $limit The fewest rows to keep.
	 * @return array
	 */
	private static function keep_newest( array $rows, int $limit ): array {
		if ( PHP_INT_MAX === $limit || count( $rows ) <= $limit ) {
			return $rows;
		}

		usort( $rows, fn( array $a, array $b ) => $b['timestamp'] <=> $a['timestamp'] );

		$boundary = $rows[ $limit - 1 ]['timestamp'];

		return array_values( array_filter( $rows, fn( array $row ) => $row['timestamp'] >= $boundary ) );
	}

	/**
	 * The filenames that could hold one day's lines for a source: the current
	 * file and every rotation of it.
	 *
	 * @param string $source The log source.
	 * @param int    $day    A timestamp within the UTC day.
	 * @return string[]
	 */
	private static function get_filenames( string $source, int $day ): array {
		$filenames = array();

		for ( $rotation = self::MAX_ROTATIONS - 1; $rotation >= 0; $rotation-- ) {
			$filenames[] = self::build_filename( $source, $rotation, $day );
		}

		$filenames[] = self::build_filename( $source, null, $day );

		return $filenames;
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
