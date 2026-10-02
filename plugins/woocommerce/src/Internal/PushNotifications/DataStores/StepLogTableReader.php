<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\DataStores;

defined( 'ABSPATH' ) || exit;

use WC_Log_Levels;

/**
 * Reads push notification step log lines back from the `wc_log` table, for
 * stores that chose the database log handler.
 *
 * The table indexes only `log_id` and `level`, so a query by source would scan
 * every row. Instead the reader walks back from the newest row in windows of
 * primary key IDs, which bounds the rows examined whatever the table size, and
 * stops once it has passed the start of the requested range or examined its
 * limit. Coverage can therefore fall short of the range on a store whose other
 * plugins log heavily; `covered_from` says where the read stopped.
 *
 * @since 11.3.0
 */
class StepLogTableReader {
	/**
	 * Rows examined per window.
	 */
	const WINDOW_SIZE = 5000;

	/**
	 * Most rows examined in one read.
	 */
	const MAX_ROWS_EXAMINED = 50000;

	/**
	 * Returns every row written under a source between two times, as far back
	 * as the bounded scan reached.
	 *
	 * Rows are turned into what the caller wants as each one is read, and the
	 * scan stops once it holds enough, so a short page does not walk the whole
	 * row budget.
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
		global $wpdb;

		$table  = $wpdb->prefix . 'woocommerce_log';
		$max_id = (int) $wpdb->get_var( "SELECT MAX(log_id) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows   = array();

		$upper        = $max_id;
		$examined     = 0;
		$covered_from = $from;

		while ( $upper > 0 && $examined < self::MAX_ROWS_EXAMINED ) {
			$lower = max( 0, $upper - self::WINDOW_SIZE );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT log_id, timestamp, level, message, context FROM {$table} WHERE log_id > %d AND log_id <= %d AND source = %s ORDER BY log_id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$lower,
					$upper,
					$source
				),
				ARRAY_A
			);

			foreach ( (array) $results as $result ) {
				$row = self::build_row( $result );

				if ( $row['timestamp'] < $from || $row['timestamp'] > $to ) {
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

			$examined += $upper - $lower;
			$oldest    = $this->get_window_start_timestamp( $table, $lower );
			$upper     = $lower;

			/*
			 * Windows are walked newest first, so everything left is older than
			 * what is held and cannot belong on this page.
			 */
			if ( count( $rows ) >= $limit ) {
				$covered_from = null === $oldest ? $from : $oldest;
				break;
			}

			if ( null === $oldest ) {
				continue;
			}

			if ( $oldest <= $from ) {
				$covered_from = $from;
				break;
			}

			$covered_from = $oldest;
		}

		if ( 0 === $upper ) {
			$covered_from = $from;
		}

		usort( $rows, fn( array $a, array $b ) => $a['timestamp'] <=> $b['timestamp'] );

		return array(
			'rows'         => $rows,
			'covered_from' => $covered_from,
		);
	}

	/**
	 * Returns the timestamp of the oldest row in the window just read, to
	 * decide whether the scan has passed the start of the range.
	 *
	 * @param string $table The log table name.
	 * @param int    $lower The exclusive lower ID bound of the window just read.
	 * @return int|null Null when the window held no rows at all.
	 */
	private function get_window_start_timestamp( string $table, int $lower ): ?int {
		global $wpdb;

		$timestamp = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT timestamp FROM {$table} WHERE log_id > %d ORDER BY log_id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$lower
			)
		);

		return null === $timestamp ? null : (int) strtotime( $timestamp . ' UTC' );
	}

	/**
	 * Converts one table row to the shape the file reader produces.
	 *
	 * @param array $result The row as `wpdb` returned it.
	 * @return array{timestamp: int, level: string, message: string, context: array|null, raw: string|null}
	 */
	private static function build_row( array $result ): array {
		$context = null;
		$raw     = null;

		if ( ! empty( $result['context'] ) ) {
			$decoded = json_decode( (string) $result['context'], true );

			if ( is_array( $decoded ) ) {
				$context = $decoded;
			} else {
				$raw = (string) $result['context'];
			}
		}

		return array(
			'timestamp' => (int) strtotime( $result['timestamp'] . ' UTC' ),
			'level'     => (string) WC_Log_Levels::get_severity_level( (int) $result['level'] ),
			'message'   => (string) $result['message'],
			'context'   => $context,
			'raw'       => $raw,
		);
	}
}
