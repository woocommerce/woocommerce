/**
 * External dependencies
 */
import { initializeEditor } from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import { initializeEmailEditorPlugin } from './editor-plugin';

initializeEmailEditorPlugin();
window.addEventListener( 'DOMContentLoaded', () => {
	initializeEditor(
		'woocommerce-email-editor',
		window.WooCommerceEmailEditor.current_post_type,
		window.WooCommerceEmailEditor.current_post_id,
		window.WooCommerceEmailEditor.editor_settings,
		[]
	);
} );
