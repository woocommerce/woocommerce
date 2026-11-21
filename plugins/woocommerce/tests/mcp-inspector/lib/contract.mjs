/**
 * Every property of a schema, keyed by its path from `prefix` (array items as `[]`).
 */
export function collectProperties( prefix, schema, fields = {} ) {
	if ( ! schema || typeof schema !== 'object' ) {
		return fields;
	}

	const children = {
		...( schema.properties ?? {} ),
		...( schema.items && typeof schema.items === 'object'
			? { '[]': schema.items }
			: {} ),
	};

	for ( const [ property, definition ] of Object.entries( children ) ) {
		if ( ! definition || typeof definition !== 'object' ) {
			continue;
		}
		const path = `${ prefix }.${ property }`;
		fields[ path ] = definition;
		collectProperties( path, definition, fields );
	}

	return fields;
}

const escapeRegExp = ( value ) =>
	value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );

/**
 * Only a run of the values joined by list punctuation counts: "draft, publish, or private" restates
 * the enum, while '"replace" replaces all categories, "append" adds to them' explains it.
 */
export function restatesEnum( field ) {
	if (
		! Array.isArray( field.enum ) ||
		field.enum.length < 2 ||
		! field.description
	) {
		return false;
	}
	const run = field.enum
		.map( ( value ) => escapeRegExp( String( value ) ) )
		.join( `["']?(?:,|,? or|/)\\s+["']?` );
	return new RegExp( run, 'i' ).test( field.description );
}

export function namesDefault( field ) {
	return /\(?\bdefault:\s/i.test( field.description ?? '' );
}

/**
 * Approximation (four characters per token) until a tokenizer and the budget are agreed.
 */
export function estimateTokens( value ) {
	return Math.ceil( JSON.stringify( value ).length / 4 );
}
