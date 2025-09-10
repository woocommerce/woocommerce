<?php

namespace Automattic\WooCommerce\Database\UsermetaLookup;

class LookupTableSyncService {
	private const META_KEYS = [
		'billing_email',
		'first_name',
		'last_name',
		'paying_customer',
		'wc_last_active',
	];

	private string $table_name;

	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'wc_user_meta_lookup';
	}

	public function init_hooks(): void {
		// user: create/delete; meta: added/updated/removed
		// do_action( 'edit_user_created_user', $user_id, $notify );
		// do_action( 'deleted_user', $id, $reassign, $user );

		// $meta_type = user
		// do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );
		// do_action( "added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value );
		// do_action( "deleted_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );

	}

	public function get_table_name(): string {
		return $this->table_name;
	}

	public function create_entry_for_user()
	{
		// create a placeholder record
	}

	public function drop_entry_for_user()
	{
		// drop the user-specific record
	}

	public function update_entry_for_user()
	{
		// set the column value: provided value or null in case of deleted meta
	}
}
