<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Database\UsermetaLookup;

/**
 * This service handles keeping `wc_user_meta_lookup` table up-to-date with `users` and `usermeta` tables changes.
 *
 * @package Automattic\WooCommerce\Database\UsermetaLookup
 */
class LookupTableSyncService {
	/**
	 * The list of metas we track under lookup table.
	 *
	 * @var string[]
	 */
	private const META_KEYS = [
		'billing_email',
		'first_name',
		'last_name',
		'paying_customer',
		'wc_last_active',
	];

	/**
	 * The lookup table name.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 *
	 * @since 10.3.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'wc_user_meta_lookup';

		$this->init_hooks();
	}

	/**
	 * Adds handlers for WordPress user and meta hooks, which require the lookup table update.
	 *
	 * @since 10.3.0
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		add_action( 'edit_user_created_user', array( $this, 'create_entry_for_user' ), 10, 1 );
		add_action( 'deleted_user', array( $this, 'delete_entry_for_user' ), 10, 1 );

		add_action( 'updated_user_meta', array( $this, 'update_entry_for_user' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'update_entry_for_user' ), 10, 4 );
		add_action( 'deleted_user_meta', array( $this, 'update_entry_for_user' ), 10, 3 );
	}

	/**
	 * Returns the lookup table name.
	 *
	 * @since 10.3.0
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		return $this->table_name;
	}

	/**
	 * Handles `edit_user_created_user` hook.
	 *
	 * @since 10.3.0
	 *
	 * @param int|\WP_Error $user_id User ID or user creation error object.
	 * @return void
	 */
	public function create_entry_for_user( $user_id ): void {
		if ( ! is_wp_error( $user_id ) ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( 'INSERT INTO %i (user_id) VALUES (%d)', $this->table_name, $user_id ) );
		}
	}

	/**
	 * Handles `deleted_user` hook.
	 *
	 * @since 10.3.0
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function delete_entry_for_user( $user_id ): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE user_id = %d', $this->table_name, $user_id ) );
	}

	/**
	 * Handles `added_user_meta`, `updated_user_meta`, `deleted_user_meta` hooks.
	 *
	 * @since 10.3.0
	 *
	 * @param int|string[] $meta_ids   Meta ID(s).
	 * @param int          $user_id    User ID.
	 * @param string       $meta_key   Meta key.
	 * @param mixed        $meta_value Meta value.
	 * @return void
	 */
	public function update_entry_for_user( $meta_ids, $user_id, $meta_key, $meta_value = null ): void {
		if ( in_array( $meta_key, self::META_KEYS, true ) ) {
			global $wpdb;
			if ( null === $meta_value) {
				$wpdb->query( $wpdb->prepare( 'UPDATE %i SET %i = NULL WHERE user_id = %d', $this->table_name, $meta_key, $user_id ) );
			} else {
				$wpdb->query( $wpdb->prepare( 'UPDATE %i SET %i = %s WHERE user_id = %d', $this->table_name, $meta_key, $meta_value, $user_id ) );
			}
		}
	}

	// TODO: relocate here initial population routines
}
