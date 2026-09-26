/**
 * External dependencies
 */
import { sanitizeHTML } from '@woocommerce/sanitize';
import { createElement } from '@wordpress/element';

export const sanitizeSettingsHtml = ( html?: string ) => sanitizeHTML( html );

// Settings descriptions carry HTML, so help text has to reach controls as a
// sanitized element; a plain string would be escaped and shown verbatim.
export const createSettingsHelpElement = ( html?: string ) =>
	html
		? createElement( 'span', {
				dangerouslySetInnerHTML: {
					__html: sanitizeSettingsHtml( html ),
				},
		  } )
		: undefined;
