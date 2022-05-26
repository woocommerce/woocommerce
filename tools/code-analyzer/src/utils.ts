/**
 * Format version string for regex.
 *
 * @param {string} rawVersion Raw version number.
 * @return {string} version regex.
 */
export const getVersionRegex = ( rawVersion: string ): string => {
	const version = rawVersion.replace( /\./g, '\\.' );

	if ( rawVersion.endsWith( '.0' ) ) {
		return version + '|' + version.slice( 0, -3 ) + '\\n';
	}

	return version;
};

/**
 * Get filename from patch
 *
 * @param {string} str String to extract filename from.
 * @return {string} formatted filename.
 */
export const getFilename = ( str: string ): string => {
	return str.replace( /^a(.*)\s.*/, '$1' );
};

/**
 * Get patches
 *
 * @param {string} content Patch content.
 * @param {RegExp} regex   Regex to find specific patches.
 * @return {string[]} Array of patches.
 */
export const getPatches = ( content: string, regex: RegExp ): string[] => {
	const patches = content.split( 'diff --git ' );
	const changes: string[] = [];

	for ( const p in patches ) {
		const patch = patches[ p ];
		const id = patch.match( regex );

		if ( id ) {
			changes.push( patch );
		}
	}

	return changes;
};

/**
 * Get hook name.
 *
 * @param {string} name Raw hook name.
 * @return {string} Formatted hook name.
 */
export const getHookName = ( name: string ): string => {
	if ( name.indexOf( ',' ) > -1 ) {
		name = name.substring( 0, name.indexOf( ',' ) );
	}

	return name.replace( /(\'|\")/g, '' ).trim();
};

/**
 * Determine if schema diff object contains schemas that are equal.
 *
 * @param {Object} schemaDiff
 * @return {boolean|void} If the schema diff describes schemas that are equal.
 */
export const areSchemasEqual = (
	schemaDiff: {
		[ key: string ]: {
			description: string;
			base: string;
			compare: string;
			areEqual: boolean;
		};
	} | void
): boolean => {
	if ( ! schemaDiff ) {
		return false;
	}
	return ! Object.keys( schemaDiff ).some(
		( d: string ) => schemaDiff[ d ].areEqual === false
	);
};
