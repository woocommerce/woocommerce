/**
 * For an array of icons, normalize into objects and remove duplicates.
 *
 * @param {Array} icons Array of icon objects or string based ids.
 */
export const normalizeIconConfig = ( icons ) => {
	const normalizedIcons = {};

	icons.forEach( ( raw ) => {
		let icon = {};

		if ( typeof raw === 'string' ) {
			icon = {
				id: raw,
				alt: raw,
				src: null,
			};
		}

		if ( typeof raw === 'object' ) {
			icon = {
				id: raw.id || '',
				alt: raw.alt || '',
				src: raw.src || null,
			};
		}

		if ( icon.id && ! normalizedIcons[ icon.id ] ) {
			normalizedIcons[ icon.id ] = icon;
		}
	} );

	return Object.values( normalizedIcons );
};
