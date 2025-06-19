/**
 * Decodes HTML entities in a string using a textarea element.
 * 
 * This approach is preferred over @wordpress/html-entities to avoid
 * issues with ESM modules and WordPress package dependencies.
 * 
 * The textarea method is safe for HTML entity decoding because:
 * - It doesn't execute JavaScript
 * - It only decodes HTML entities, doesn't render HTML elements
 * - The textarea element doesn't have a script execution context
 * 
 * @param text The text containing HTML entities to decode
 * @return The text with HTML entities decoded
 */
export const decodeHtmlEntities = ( text: string ): string => {
	if ( typeof text !== 'string' ) {
		return '';
	}
	
	// Return early for empty strings to avoid unnecessary DOM manipulation
	if ( ! text.trim() ) {
		return text;
	}
	
	// Only process strings that contain HTML entities
	if ( ! text.includes( '&' ) ) {
		return text;
	}
	
	try {
		const textarea = document.createElement( 'textarea' );
		textarea.innerHTML = text;
		return textarea.value;
	} catch ( error ) {
		// Fallback to original text if decoding fails
		// eslint-disable-next-line no-console
		console.warn( 'Failed to decode HTML entities:', error );
		return text;
	}
};