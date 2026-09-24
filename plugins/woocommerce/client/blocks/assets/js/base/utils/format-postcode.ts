/**
 * Insert text at an offset, like PHP's substr_replace( $value, $text, $offset, 0 ).
 * A negative offset counts from the end.
 */
const insertAt = ( value: string, offset: number, text: string ): string => {
	const index =
		offset < 0
			? Math.max( value.length + offset, 0 )
			: Math.min( offset, value.length );
	return value.slice( 0, index ) + text + value.slice( index );
};

/**
 * Format a postcode the way wc_format_postcode() does on the server.
 *
 * The Store API formats a postcode before validating it, so the checkout form
 * must validate the formatted value too. Otherwise it rejects values the server
 * accepts, like "2630166" for Portugal. The `woocommerce_format_postcode` PHP
 * filter cannot run here, so only the core rules are applied.
 */
export const formatPostcode = ( postcode: string, country: string ): string => {
	// Mirror wc_normalize_postcode(). PHP's \s without the u flag matches ASCII
	// whitespace only, so a no-break space is kept and still fails validation.
	let formatted = postcode.toUpperCase().replace( /[ \t\n\v\f\r-]/g, '' );

	switch ( country ) {
		case 'SE':
			formatted = insertAt( formatted, -2, ' ' );
			break;
		case 'CA':
		case 'GB':
			formatted = insertAt( formatted, -3, ' ' );
			break;
		case 'IE':
			formatted = insertAt( formatted, 3, ' ' );
			break;
		case 'BR':
		case 'PL':
			formatted = insertAt( formatted, -3, '-' );
			break;
		case 'JP':
			formatted = insertAt( formatted, 3, '-' );
			break;
		case 'PT':
			formatted = insertAt( formatted, 4, '-' );
			break;
		case 'PR':
		case 'US':
		case 'MN':
			formatted = insertAt( formatted, 5, '-' ).replace( /-+$/, '' );
			break;
		case 'NL':
			formatted = insertAt( formatted, 4, ' ' );
			break;
		case 'LV':
			formatted = formatted.replace( /^(LV)?-?(\d+)$/, 'LV-$2' );
			break;
		case 'CZ':
		case 'SK':
			formatted = formatted.replace(
				new RegExp( `^(${ country })-?(\\d+)$` ),
				'$1-$2'
			);
			formatted = insertAt( formatted, -2, ' ' );
			break;
		case 'DK':
			formatted = formatted.replace( /^(DK)(.+)$/, '$1-$2' );
			break;
	}

	return formatted.trim();
};
