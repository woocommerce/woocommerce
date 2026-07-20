/**
 * External dependencies
 */
import { sanitizeHTML } from '@woocommerce/sanitize';
import { createElement } from '@wordpress/element';

export const sanitizeSettingsHtml = ( html?: string ) => sanitizeHTML( html );

export const toSanitizedHtmlNode = ( html: string ) =>
	createElement( 'span', {
		dangerouslySetInnerHTML: { __html: sanitizeSettingsHtml( html ) },
	} );

export const toSanitizedText = ( html?: string ) => {
	if ( ! html ) {
		return undefined;
	}

	const container = document.createElement( 'div' );
	container.innerHTML = sanitizeSettingsHtml( html );
	return container.textContent || '';
};
