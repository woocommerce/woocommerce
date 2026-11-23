/**
 * JavaScript parts of the email editor's entity actions, keyed by action id (WordPress 7.2+).
 * The editor imports this module when it loads the actions, after the email editor scripts
 * have added their parts with the `woocommerce_email_editor_entity_actions` filter.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

const hooks = window.wp && window.wp.hooks;

const actions = hooks
	? hooks.applyFilters( 'woocommerce_email_editor_entity_actions', {} )
	: {};

export default actions &&
typeof actions === 'object' &&
! Array.isArray( actions )
	? actions
	: {};
