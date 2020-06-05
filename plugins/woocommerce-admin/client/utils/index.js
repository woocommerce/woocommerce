export function getUrlParams( locationSearch ) {
	if ( locationSearch ) {
		return locationSearch
			.substr( 1 )
			.split( '&' )
			.reduce( ( params, query ) => {
				const chunks = query.split( '=' );
				const key = chunks[ 0 ];
				let value = decodeURIComponent( chunks[ 1 ] );
				value = isNaN( Number( value ) ) ? value : Number( value );
				return ( params[ key ] = value ), params;
			}, {} );
	}
	return {};
}
