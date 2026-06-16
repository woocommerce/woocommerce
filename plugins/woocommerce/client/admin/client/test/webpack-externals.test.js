const {
	requestToExternal,
	requestToHandle,
} = require( '../../webpack-externals' );

describe( 'admin webpack externals', () => {
	it.each( [ '@wordpress/dataviews', '@wordpress/dataviews/wp' ] )(
		'bundles %s because WordPress Core does not register wp-dataviews in supported environments',
		( request ) => {
			expect( requestToExternal( request ) ).toBeNull();
			expect( requestToHandle( request ) ).toBeUndefined();
		}
	);
} );
