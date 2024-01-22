export default function parseStringsArray( message: string ): string[] {
	if ( ! message?.length ) {
		return [];
	}

	let correction = message;

	// Close any open string
	const numQuotes = ( correction.match( /"/g ) || [] ).length;
	if ( numQuotes % 2 !== 0 ) {
		correction += '"';
	}

	// Remove a trailing comma before closing the array
	correction = correction.replace( /,\s*$/, '' );

	// Close the array
	if ( ! correction.trim().endsWith( ']' ) ) {
		correction += ']';
	}

	try {
		return JSON.parse( correction );
	} catch ( e ) {
		// eslint-disable-next-line no-console
		console.error( e );
		return [];
	}
}
