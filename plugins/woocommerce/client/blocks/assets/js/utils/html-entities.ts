/**
 * Utility function to decode HTML entities in a string. Inspired by Gutenberg's stripHtml function (currently unstable).
 *
 * @param text
 */
export const decodeHtmlEntities = ( text: unknown ): string => {
	if ( typeof text !== 'string' ) {
		return '';
	}

	const doc = document.implementation.createHTMLDocument( '' );
	doc.body.innerHTML = text;
	return doc.body.textContent || '';
};
