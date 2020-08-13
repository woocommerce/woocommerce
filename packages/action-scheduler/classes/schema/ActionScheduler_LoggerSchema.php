<?php

/**
 * Class ActionScheduler_LoggerSchema
 *
 * @codeCoverageIgnore
 *
 * Creates a custom table for storing action logs
 */
class ActionScheduler_LoggerSchema extends ActionScheduler_Abstract_Schema {
	const LOG_TABLE = 'actionscheduler_logs';

	/**
	 * @var int Increment this value to trigger a schema update.
	 */
	protected $schema_version = 2;

	public function __construct() {
		$this->tables = [
			self::LOG_TABLE,
		];
	}

	protected function get_table_definition( $table ) {
		global $wpdb;
		$table_name       = $wpdb->$table;
		$charset_collate  = $wpdb->get_charset_collate();
		switch ( $table ) {

			case self::LOG_TABLE:

				return "CREATE TABLE {$table_name} (
				        log_id bigint(20) unsigned NOT NULL auto_increment,
				        action_id bigint(20) unsigned NOT NULL,
				        message text NOT NULL,
				        log_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        log_date_local datetime NOT NULL default '0000-00-00 00:00:00',
				        PRIMARY KEY  (log_id),
				        KEY action_id (action_id),
				        KEY log_date_gmt (log_date_gmt)
				        ) $charset_collate";

			default:
				return '';
		}
	}
}
