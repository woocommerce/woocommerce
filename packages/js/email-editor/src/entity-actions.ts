/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import getTrashEmailPostAction from './components/header/trash-email-post';

/**
 * From WordPress 7.2 post actions are registered on the server. The email editor's
 * script module (`@woocommerce/email-editor/entity-actions`) reads the JavaScript
 * parts of its actions from this filter when the editor loads them.
 */
export function initEntityActions() {
	addFilter(
		'woocommerce_email_editor_entity_actions',
		'woocommerce-email-editor/trash-email-post',
		( actions: Record< string, unknown > ) => ( {
			...actions,
			'trash-email-post': getTrashEmailPostAction(),
		} )
	);
}
