const normalizeHexColor = ( color?: string ) => {
	if ( ! color ) {
		return undefined;
	}

	const shortMatch = /^#([0-9a-f])([0-9a-f])([0-9a-f])$/i.exec( color );
	if ( shortMatch ) {
		return `#${ shortMatch[ 1 ] }${ shortMatch[ 1 ] }${ shortMatch[ 2 ] }${ shortMatch[ 2 ] }${ shortMatch[ 3 ] }${ shortMatch[ 3 ] }`;
	}

	return /^#[0-9a-f]{6}$/i.test( color ) ? color : undefined;
};

const shiftColor = ( color: string, percentage: number, lighten: boolean ) => {
	const hex = color.replace( '#', '' );
	const shift = Math.max( 0, Math.min( 100, percentage ) ) / 100;
	const channel = ( value: string ) => {
		const parsed = parseInt( value, 16 );
		const shifted = lighten
			? parsed + ( 255 - parsed ) * shift
			: parsed * ( 1 - shift );

		return Math.round( shifted ).toString( 16 ).padStart( 2, '0' );
	};

	return `#${ channel( hex.substring( 0, 2 ) ) }${ channel(
		hex.substring( 2, 4 )
	) }${ channel( hex.substring( 4, 6 ) ) }`;
};

const isDarkColor = ( color: string ) => {
	const hex = color.replace( '#', '' );
	const red = parseInt( hex.substring( 0, 2 ), 16 );
	const green = parseInt( hex.substring( 2, 4 ), 16 );
	const blue = parseInt( hex.substring( 4, 6 ), 16 );

	return ( red * 299 + green * 587 + blue * 114 ) / 1000 < 128;
};

export const getCardBorderColor = ( color?: string ) => {
	const hexColor = normalizeHexColor( color );

	if ( ! hexColor ) {
		return undefined;
	}

	return isDarkColor( hexColor )
		? shiftColor( hexColor, 6, true )
		: shiftColor( hexColor, 6, false );
};
