<?php
/**
 * Plugin Name: WooCommerce Admin Simple Inbox Note Example
 *
 * @package WooCommerce\Admin
 */

/**
 * Add note.
 */
function add_note() {
	require_once dirname( __FILE__ ) . '/includes/class-simpleinboxnote.php';
	SimpleInboxNote::possibly_add_note();
}

/**
 * Delete note.
 */
function delete_note() {
	require_once dirname( __FILE__ ) . '/includes/class-simpleinboxnote.php';
	SimpleInboxNote::possibly_delete_note();
}

// Attempt to add the note on plugin activation.
add_action( 'activated_plugin', 'add_note' );

// Delete the note when plugin is deactivated.
add_action( 'deactivated_plugin', 'delete_note' );
