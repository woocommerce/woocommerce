/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { EmailEditor } from '@woocommerce/email-editor';

export function initializeEditor() {
	const container = document.getElementById( 'woocommerce-email-editor' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <EmailEditor /> );
	}
}
