const {
	requestToExternal,
	requestToHandle,
} = require( '../../webpack-externals' );

describe( 'admin webpack externals', () => {
	it.each( [ '@wordpress/dataviews', '@wordpress/dataviews/wp' ] )(
		'externalizes %s to the WordPress DataViews script',
		( request ) => {
			expect( requestToExternal( request ) ).toEqual( [
				'wp',
				'dataviews',
			] );
			expect( requestToHandle( request ) ).toBe( 'wp-dataviews' );
		}
	);
} );
