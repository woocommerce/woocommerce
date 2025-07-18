/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { EmailEditor } from '@woocommerce/email-editor';

export function initializeEditor() {
	const container = document.getElementById( 'woocommerce-email-editor' );
	const { current_post_id, current_post_type } = window.WooCommerceEmailEditor;

	if ( current_post_id === undefined || current_post_id === null ) {
		throw new Error( 'current_post_id is required but not provided.' );
	}

	if ( ! current_post_type ) {
		throw new Error( 'current_post_type is required but not provided.' );
	}
	if ( container ) {
		const root = createRoot( container );
		root.render( <EmailEditor postId={ current_post_id } postType={ current_post_type } /> );
	}
}
