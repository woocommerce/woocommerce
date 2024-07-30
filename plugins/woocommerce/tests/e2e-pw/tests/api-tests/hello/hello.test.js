const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );

test.describe( 'Test API connectivity', () => {
	test( 'can access a non-authenticated endpoint', async ( { request } ) => {
		const result = await request.get( '/wp-json/wc/v3/' );
		expect( result.status() ).toEqual( 200 );
	} );

	test( 'can access an authenticated endpoint', async ( { request } ) => {
		const result = await request.get( '/wp-json/wc/v3/system_status' );
		expect( result.status() ).toEqual( 200 );
	} );
} );
