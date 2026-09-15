<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2;
use Automattic\WooCommerce\Internal\Admin\Logging\Settings;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogFileReader;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogTableReader;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Utilities\LoggingUtil;
use WC_Log_Handler_DB;

/**
 * Answers the questions Mission Control asks of the push notification step
 * log: what happened to one notification, and what has been sent to one
 * device or one user.
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
	 * Returns every step recorded for one resource's notifications of one
	 * type, from the journey file and from the module's error log.
	 *
	 * @param string $type        The notification type, e.g. `store_order`.
	 * @param int    $resource_id The order, comment or product ID.
	 * @param int    $from        Earliest timestamp, inclusive.
	 * @param int    $to          Latest timestamp, inclusive.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, logging: array}
	 *
	 * @since 11.3.0
	 */
	public function for_notification( string $type, int $resource_id, int $from, int $to ): array {
		$sources = array(
			NotificationStepLogger::get_notification_source_for_type( $type ),
			PushNotifications::FEATURE_NAME,
		);

		return $this->query(
			$sources,
			$from,
			$to,
			fn( array $row ) => isset( $row['context']['type'], $row['context']['resource_id'] )
				&& $type === $row['context']['type']
				&& $resource_id === (int) $row['context']['resource_id']
		);
	}

	/**
	 * Returns every step recorded against one device.
	 *
	 * @param int $token_id The push token post ID.
	 * @param int $from     Earliest timestamp, inclusive.
	 * @param int $to       Latest timestamp, inclusive.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, logging: array}
	 *
	 * @since 11.3.0
	 */
	public function for_token( int $token_id, int $from, int $to ): array {
		return $this->query( array( NotificationStepLogger::get_token_source( $token_id ) ), $from, $to );
	}

	/**
	 * Returns every step recorded against every device one user owns.
	 *
	 * @param int $user_id The user.
	 * @param int $from    Earliest timestamp, inclusive.
	 * @param int $to      Latest timestamp, inclusive.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, logging: array}
	 *
	 * @since 11.3.0
	 */
	public function for_user( int $user_id, int $from, int $to ): array {
		$sources = array_map(
			array( NotificationStepLogger::class, 'get_token_source' ),
			$this->data_store->get_token_ids_for_user( $user_id )
		);

		return $this->query( $sources, $from, $to );
	}

	/**
	 * Returns the store's logging state: the settings that decide whether
	 * step lines are written and kept, and the token counts that decide
	 * whether per-device lines are written.
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
			'token_line_cap'         => NotificationProcessor::TOKEN_LINE_CAP,
		);
	}

	/**
	 * Reads the sources, keeps the rows the filter accepts, and assembles the
	 * answer.
	 *
	 * @param string[]      $sources The log sources to read.
	 * @param int           $from    Earliest timestamp, inclusive.
	 * @param int           $to      Latest timestamp, inclusive.
	 * @param callable|null $filter  Optional row filter.
	 * @return array{rows: array, counts: array<string, int>, covered_from: int, logging: array}
	 */
	private function query( array $sources, int $from, int $to, ?callable $filter = null ): array {
		$rows         = array();
		$covered_from = $from;

		foreach ( $sources as $source ) {
			$result       = $this->read( $source, $from, $to );
			$covered_from = max( $covered_from, $result['covered_from'] );

			foreach ( $result['rows'] as $row ) {
				if ( null === $filter || $filter( $row ) ) {
					$rows[] = $row;
				}
			}
		}

		usort( $rows, fn( array $a, array $b ) => $a['timestamp'] <=> $b['timestamp'] );

		return array(
			'rows'         => array_map( array( self::class, 'format_row' ), $rows ),
			'counts'       => self::count_outcomes( $rows ),
			'covered_from' => $covered_from,
			'logging'      => $this->get_logging_state(),
		);
	}

	/**
	 * Reads one source through the reader that matches the store's handler.
	 *
	 * @param string $source The log source.
	 * @param int    $from   Earliest timestamp, inclusive.
	 * @param int    $to     Latest timestamp, inclusive.
	 * @return array{rows: array, covered_from: int}
	 */
	private function read( string $source, int $from, int $to ): array {
		$handler = LoggingUtil::get_default_handler();

		if ( is_a( $handler, WC_Log_Handler_DB::class, true ) ) {
			return $this->table_reader->read( $source, $from, $to );
		}

		if ( is_a( $handler, LogHandlerFileV2::class, true ) ) {
			return array(
				'rows'         => $this->file_reader->read( $source, $from, $to ),
				'covered_from' => $from,
			);
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
		return array(
			'timestamp' => gmdate( 'c', $row['timestamp'] ),
			'level'     => $row['level'],
			'message'   => $row['message'],
			'context'   => $row['context'],
			'raw'       => $row['raw'],
		);
	}
}
