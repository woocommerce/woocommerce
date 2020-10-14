<?php

/**
 * Class ActionScheduler_StoreSchema
 *
 * @codeCoverageIgnore
 *
 * Creates custom tables for storing scheduled actions
 */
class ActionScheduler_StoreSchema extends ActionScheduler_Abstract_Schema {
	const ACTIONS_TABLE = 'actionscheduler_actions';
	const CLAIMS_TABLE  = 'actionscheduler_claims';
	const GROUPS_TABLE  = 'actionscheduler_groups';

	/**
	 * @var int Increment this value to trigger a schema update.
	 */
	protected $schema_version = 3;

	public function __construct() {
		$this->tables = [
			self::ACTIONS_TABLE,
			self::CLAIMS_TABLE,
			self::GROUPS_TABLE,
		];
	}

	protected function get_table_definition( $table ) {
		global $wpdb;
		$table_name       = $wpdb->$table;
		$charset_collate  = $wpdb->get_charset_collate();
		$max_index_length = 191; // @see wp_get_db_schema()
		switch ( $table ) {

			case self::ACTIONS_TABLE:

				return "CREATE TABLE {$table_name} (
				        action_id bigint(20) unsigned NOT NULL auto_increment,
				        hook varchar(191) NOT NULL,
				        status varchar(20) NOT NULL,
				        scheduled_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        scheduled_date_local datetime NOT NULL default '0000-00-00 00:00:00',
				        args varchar($max_index_length),
				        schedule longtext,
				        group_id bigint(20) unsigned NOT NULL default '0',
				        attempts int(11) NOT NULL default '0',
				        last_attempt_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        last_attempt_local datetime NOT NULL default '0000-00-00 00:00:00',
				        claim_id bigint(20) unsigned NOT NULL default '0',
				        extended_args varchar(8000) DEFAULT NULL,
				        PRIMARY KEY  (action_id),
				        KEY hook (hook($max_index_length)),
				        KEY status (status),
				        KEY scheduled_date_gmt (scheduled_date_gmt),
				        KEY args (args($max_index_length)),
				        KEY group_id (group_id),
				        KEY last_attempt_gmt (last_attempt_gmt),
				        KEY claim_id (claim_id)
				        ) $charset_collate";

			case self::CLAIMS_TABLE:

				return "CREATE TABLE {$table_name} (
				        claim_id bigint(20) unsigned NOT NULL auto_increment,
				        date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        PRIMARY KEY  (claim_id),
				        KEY date_created_gmt (date_created_gmt)
				        ) $charset_collate";

			case self::GROUPS_TABLE:

				return "CREATE TABLE {$table_name} (
				        group_id bigint(20) unsigned NOT NULL auto_increment,
				        slug varchar(255) NOT NULL,
				        PRIMARY KEY  (group_id),
				        KEY slug (slug($max_index_length))
				        ) $charset_collate";

			default:
				return '';
		}
	}
}
