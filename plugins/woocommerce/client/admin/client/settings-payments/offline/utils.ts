/**
 * Internal dependencies
 */

/**
 * Decode HTML entities in a string.
 *
 * @param text
 */
export function decodeHtmlEntities( text: string ) {
	const textArea = document.createElement( 'textarea' );
	textArea.innerHTML = text;
	return textArea.value;
}

/**
 * Map shipping methods options to a format suitable for a grouped select control.
 *
 * @param options
 */
export function mapShippingMethodsOptions(
	options: Record< string, Record< string, string > >
) {
	return Object.entries( options ).map( ( [ groupLabel, methods ] ) => {
		const decodedGroupLabel = decodeHtmlEntities( groupLabel );
		const children = Object.entries( methods ).map(
			( [ value, label ] ) => ( {
				value,
				label: decodeHtmlEntities( label ),
			} )
		);
		return {
			label: decodedGroupLabel,
			value: groupLabel,
			children,
		};
	} );
}
