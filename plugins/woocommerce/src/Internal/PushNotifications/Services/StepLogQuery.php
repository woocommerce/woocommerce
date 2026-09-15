<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2;
use Automattic\WooCommerce\Internal\Admin\Logging\Settings;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogFileReader;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogTableReader;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Utilities\LoggingUtil;
use WC_Log_Handler_DB;

/**
 * Answers the questions Mission Control asks of the push notification step
 * log: what happened to one notification, and what has been sent to one
 * token or one user.
 *
 * Reads from the log files or the log table depending on which handler the
 * store uses, and returns the store's logging state with every answer so a
 * screen with no rows can say why.
 *
 * @since 11.3.0
 */
class StepLogQuery {
	/**
	 * The file reader.
	 *
	 * @var StepLogFileReader
	 */
	private StepLogFileReader $file_reader;

	/**
	 * The table reader.
	 *
	 * @var StepLogTableReader
	 */
	private StepLogTableReader $table_reader;

	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $data_store;

	/**
	 * The step logger, consulted for whether logging is active on this store.
	 *
	 * @var NotificationStepLogger
	 */
	private NotificationStepLogger $step_logger;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param StepLogFileReader      $file_reader  The file reader.
	 * @param StepLogTableReader     $table_reader The table reader.
	 * @param PushTokensDataStore    $data_store   The push tokens data store.
	 * @param NotificationStepLogger $step_logger  The step logger.
	 *
	 * @since 11.3.0
	 */
	final public function init(
		StepLogFileReader $file_reader,
		StepLogTableReader $table_reader,
		PushTokensDataStore $data_store,
		NotificationStepLogger $step_logger
	): void {
		$this->file_reader  = $file_reader;
		$this->table_reader = $table_reader;
		$this->data_store   = $data_store;
		$this->step_logger  = $step_logger;
	}

	/**
	 * The order the steps run in, used only to break ties inside one second.
	 *
	 * @var array<string, int>
	 */
	private const STEP_ORDER = array(
		'triggered'          => 1,
		'loopback_requested' => 2,
		'loopback_started'   => 3,
		'fallback'           => 4,
		'processing'         => 5,
		'skipped'            => 6,
		'recipients'         => 7,
		'token_included'     => 8,
		'token_excluded'     => 9,
		'dispatched'         => 10,
		'retry'              => 11,
	);

	/**
	 * Returns every step recorded on the store.
	 *
	 * Reads every notification type's source, the store-wide suppressed source
	 * and the module's error source. No source is per token, so the number of
	 * files read does not grow with the number of devices the store holds.
	 *
	 * @param int $from Earliest timestamp, inclusive.
	 * @param int $to    Latest timestamp, inclusive.
	 * @param int $limit Rows to return, newest first.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, next_to: int|null, logging: array}
	 *
	 * @since 11.3.0
	 */
	public function for_site( int $from, int $to, int $limit ): array {
		$sources = array_map(
			array( NotificationStepLogger::class, 'get_notification_source_for_type' ),
			array_keys( Notification::NOTIFICATION_CLASSES )
		);

		$sources[] = NotificationStepLogger::SUPPRESSED_SOURCE;
		$sources[] = PushNotifications::FEATURE_NAME;

		return $this->query( $sources, $from, $to, $limit );
	}

	/**
	 * Returns every step recorded for one resource's notifications of one
	 * type, from the journey file and from the module's error log.
	 *
	 * @param string $type        The notification type, e.g. `store_order`.
	 * @param int    $resource_id The order, comment or product ID.
	 * @param int    $from        Earliest timestamp, inclusive.
	 * @param int    $to          Latest timestamp, inclusive.
	 * @param int    $limit       Rows to return, newest first.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, next_to: int|null, logging: array}
	 *
	 * @since 11.3.0
	 */
	public function for_notification( string $type, int $resource_id, int $from, int $to, int $limit ): array {
		$sources = array(
			NotificationStepLogger::get_notification_source_for_type( $type ),
			NotificationStepLogger::SUPPRESSED_SOURCE,
			PushNotifications::FEATURE_NAME,
		);

		return $this->query(
			$sources,
			$from,
			$to,
			$limit,
			function ( array $row ) use ( $type, $resource_id ): array {
				$context = $row['context'] ?? array();

				/*
				 * A batch line covers many notifications, so it names each one
				 * by type and resource ID rather than carrying one of its own.
				 */
				if ( isset( $context['notifications'] ) && is_array( $context['notifications'] ) ) {
					foreach ( $context['notifications'] as $covered ) {
						if ( isset( $covered['type'], $covered['resource_id'] )
							&& $type === $covered['type']
							&& $resource_id === (int) $covered['resource_id'] ) {
							return array( $row );
						}
					}

					return array();
				}

				return isset( $context['type'], $context['resource_id'] )
					&& $type === $context['type']
					&& $resource_id === (int) $context['resource_id']
						? array( $row )
						: array();
			}
		);
	}

	/**
	 * Returns every step recorded against one token.
	 *
	 * No line is stored per token, so this finds the token named inside the
	 * notification-level lines and returns a row for it.
	 *
	 * @param int $token_id The push token post ID.
	 * @param int $from     Earliest timestamp, inclusive.
	 * @param int $to       Latest timestamp, inclusive.
	 * @param int $limit    Rows to return, newest first.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, next_to: int|null, logging: array}
	 *
	 * @since 11.3.0
	 */
	public function for_token( int $token_id, int $from, int $to, int $limit ): array {
		return $this->query(
			self::get_token_sources(),
			$from,
			$to,
			$limit,
			fn( array $row ) => self::split_line_into_token_rows( $row, array( $token_id ) )
		);
	}

	/**
	 * Returns every step recorded against every token one user owns.
	 *
	 * @param int $user_id The user.
	 * @param int $from    Earliest timestamp, inclusive.
	 * @param int $to      Latest timestamp, inclusive.
	 * @param int $limit   Rows to return, newest first.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, next_to: int|null, logging: array}
	 *
	 * @since 11.3.0
	 */
	public function for_user( int $user_id, int $from, int $to, int $limit ): array {
		$token_ids = $this->data_store->get_token_ids_for_user( $user_id );

		return $this->query(
			self::get_token_sources(),
			$from,
			$to,
			$limit,
			fn( array $row ) => self::split_line_into_token_rows( $row, $token_ids )
		);
	}

	/**
	 * The sources that can name a token: every notification type's own source
	 * and the store-wide suppressed source.
	 *
	 * @return string[]
	 */
	private static function get_token_sources(): array {
		$sources = array_map(
			array( NotificationStepLogger::class, 'get_notification_source_for_type' ),
			array_keys( Notification::NOTIFICATION_CLASSES )
		);

		$sources[] = NotificationStepLogger::SUPPRESSED_SOURCE;

		return $sources;
	}

	/**
	 * Splits one stored line into a row per token it names, so a per-token read
	 * sees the same shape it would have seen when every token had a line of its
	 * own.
	 *
	 * @param array $row       The stored row.
	 * @param int[] $token_ids The tokens being asked about.
	 * @return array[] A row per token named, which may be none.
	 */
	private static function split_line_into_token_rows( array $row, array $token_ids ): array {
		$context = $row['context'] ?? array();
		$rows    = array();

		foreach ( $token_ids as $token_id ) {
			$token_id = (int) $token_id;

			foreach ( (array) ( $context['excluded_tokens'] ?? array() ) as $reason => $excluded ) {
				if ( in_array( $token_id, array_map( 'intval', (array) $excluded ), true ) ) {
					$rows[] = self::strip_unrelated_tokens( $row, $token_id, 'token_excluded', (string) $reason );
					continue 2;
				}
			}

			if ( in_array( $token_id, array_map( 'intval', (array) ( $context['invalid_token_ids'] ?? array() ) ), true ) ) {
				$rows[] = self::strip_unrelated_tokens( $row, $token_id, 'dispatched', 'invalid_token' );
				continue;
			}

			if ( in_array( $token_id, array_map( 'intval', (array) ( $context['token_ids'] ?? array() ) ), true ) ) {
				$rows[] = self::strip_unrelated_tokens( $row, $token_id, 'token_included', 'ok' );
			}
		}

		return $rows;
	}

	/**
	 * Returns the row for one token, with every other token's data removed.
	 *
	 * The stored line names every token the notification touched, so the three
	 * token lists are dropped and the step and outcome are replaced with what
	 * happened to this one. Two rows built from the same line can therefore
	 * carry different outcomes.
	 *
	 * @param array  $row      The stored row.
	 * @param int    $token_id The token to keep.
	 * @param string $step     The step to report for that token.
	 * @param string $outcome  The outcome to report for that token.
	 * @return array
	 */
	private static function strip_unrelated_tokens( array $row, int $token_id, string $step, string $outcome ): array {
		$context = $row['context'] ?? array();

		unset( $context['excluded_tokens'], $context['invalid_token_ids'], $context['token_ids'] );

		$context['token_id'] = $token_id;
		$context['step']     = $step;
		$context['outcome']  = $outcome;

		$row['context'] = $context;
		$row['message'] = ucfirst( str_replace( '_', ' ', $step ) ) . ': ' . str_replace( '_', ' ', $outcome );

		return $row;
	}

	/**
	 * Returns the store's logging state: the settings that decide whether
	 * step lines are written and kept, the store's token count, and the caps a
	 * line's token lists are cut to, so a reader can tell a short list from a
	 * truncated one.
	 *
	 * @return array
	 *
	 * @since 11.3.0
	 */
	public function get_logging_state(): array {
		$directory = Settings::get_log_directory( false );

		return array(
			'logging_enabled'        => LoggingUtil::logging_is_enabled(),
			'push_logging_enabled'   => $this->step_logger->is_active(),
			'default_handler'        => LoggingUtil::get_default_handler(),
			'level_threshold'        => LoggingUtil::get_level_threshold(),
			'retention_period_days'  => LoggingUtil::get_retention_period(),
			'log_directory_writable' => is_dir( $directory ) && wp_is_writable( $directory ),
			'token_count'            => $this->data_store->count_tokens(),
			'recipient_id_cap'       => NotificationProcessor::RECIPIENT_ID_CAP,
			'max_array_items'        => NotificationStepLogger::MAX_ARRAY_ITEMS,
		);
	}

	/**
	 * Reads the sources, keeps the rows the filter accepts, and assembles the
	 * answer.
	 *
	 * @param string[]      $sources The log sources to read.
	 * @param int           $from    Earliest timestamp, inclusive.
	 * @param int           $to      Latest timestamp, inclusive.
	 * @param int           $limit   Rows to return, before the whole-second rule.
	 * @param callable|null $expand  Called with each row, returning a row per result it should produce.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, next_to: int|null, logging: array}
	 */
	private function query( array $sources, int $from, int $to, int $limit, ?callable $expand = null ): array {
		$rows         = array();
		$covered_from = $from;

		foreach ( $sources as $source ) {
			/*
			 * The readers apply the expansion as each line is read and stop
			 * once they hold enough, so neither the range nor one large file
			 * decides how much is held here.
			 */
			$result       = $this->read( $source, $from, $to, $limit, $expand );
			$covered_from = max( $covered_from, $result['covered_from'] );
			$rows         = array_merge( $rows, $result['rows'] );
		}

		usort(
			$rows,
			function ( array $a, array $b ): int {
				if ( $a['timestamp'] !== $b['timestamp'] ) {
					return $b['timestamp'] <=> $a['timestamp'];
				}

				return self::get_order_within_a_second( $b ) <=> self::get_order_within_a_second( $a );
			}
		);

		list( $rows, $next_to ) = self::take_page( $rows, $limit );

		return array(
			'rows'         => array_map( array( self::class, 'format_row' ), $rows ),
			'counts'       => self::count_outcomes( $rows ),
			'covered_from' => $covered_from,
			'next_to'      => $next_to,
			'logging'      => $this->get_logging_state(),
		);
	}

	/**
	 * Where a line sits among the lines sharing its second.
	 *
	 * Timestamps are whole seconds, and the request that dispatches a
	 * notification and the loopback request that processes it run at the same
	 * time in separate processes, so neither the timestamp nor the order the
	 * lines were written tells a reader which step came first. Ordering ties by
	 * the lifecycle the steps run in does.
	 *
	 * A step this does not name sorts to the end of its second.
	 *
	 * @param array $row The row.
	 * @return int
	 */
	private static function get_order_within_a_second( array $row ): int {
		return self::STEP_ORDER[ $row['context']['step'] ?? '' ] ?? 0;
	}

	/**
	 * Takes one page off the front of the rows, with the cursor for the next.
	 *
	 * Timestamps are whole seconds and several steps of one notification share
	 * a second, so a page that stopped mid-second would skip or repeat rows on
	 * the next read. Where the limit falls inside a second the page carries
	 * every row of that second and may exceed the limit.
	 *
	 * @param array $rows  Rows, newest first.
	 * @param int   $limit Rows to return, before the whole-second rule.
	 * @return array{0: list<array>, 1: int|null} The page, and the `to` for the next page or null.
	 */
	private static function take_page( array $rows, int $limit ): array {
		if ( count( $rows ) <= $limit ) {
			return array( array_values( $rows ), null );
		}

		$rows     = array_values( $rows );
		$boundary = (int) $rows[ $limit - 1 ]['timestamp'];
		$page     = array_slice( $rows, 0, $limit );
		$next     = $limit;

		while ( isset( $rows[ $next ] ) && $boundary === (int) $rows[ $next ]['timestamp'] ) {
			$page[] = $rows[ $next ];
			++$next;
		}

		return array( $page, isset( $rows[ $next ] ) ? $boundary - 1 : null );
	}

	/**
	 * Reads one source through the reader that matches the store's handler.
	 *
	 * @param string        $source The log source.
	 * @param int           $from   Earliest timestamp, inclusive.
	 * @param int           $to     Latest timestamp, inclusive.
	 * @param int           $limit  The fewest rows the reader collects before stopping.
	 * @param callable|null $expand Called with each row, returning a row per result it should produce.
	 * @return array{rows: array, covered_from: int}
	 */
	private function read( string $source, int $from, int $to, int $limit, ?callable $expand ): array {
		$handler = LoggingUtil::get_default_handler();

		if ( is_a( $handler, WC_Log_Handler_DB::class, true ) ) {
			return $this->table_reader->read( $source, $from, $to, $limit, $expand );
		}

		if ( is_a( $handler, LogHandlerFileV2::class, true ) ) {
			return $this->file_reader->read( $source, $from, $to, $limit, $expand );
		}

		return array(
			'rows'         => array(),
			'covered_from' => $to,
		);
	}

	/**
	 * Counts rows per step and outcome, as `step:outcome` keys.
	 *
	 * @param array $rows The rows.
	 * @return array<string, int>
	 */
	private static function count_outcomes( array $rows ): array {
		$counts = array();

		foreach ( $rows as $row ) {
			if ( ! isset( $row['context']['step'], $row['context']['outcome'] ) ) {
				continue;
			}

			$key            = $row['context']['step'] . ':' . $row['context']['outcome'];
			$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
		}

		return $counts;
	}

	/**
	 * Shapes one row for the REST response.
	 *
	 * @param array $row The row as a reader produced it.
	 * @return array
	 */
	private static function format_row( array $row ): array {
		$formatted = array(
			'timestamp' => gmdate( 'c', $row['timestamp'] ),
			'level'     => $row['level'],
			'message'   => $row['message'],
			'context'   => $row['context'],
		);

		// Only a line whose context did not parse has raw text to show, and for
		// those it is all there is, since the context is null.
		if ( null !== $row['raw'] ) {
			$formatted['raw'] = $row['raw'];
		}

		return $formatted;
	}
}
