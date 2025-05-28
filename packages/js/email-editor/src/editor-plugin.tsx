/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const EmailEditorPlugin = () => {
	console.log( 'EmailEditorPlugin' );
	return null;
};

export function initializeEmailEditorPlugin() {
	registerPlugin( 'email-editor', {
		render: EmailEditorPlugin,
	} );
}
