const { getRequest } = require('../../utils/request');

/**
 * Tests to verify connection to the API.
 *
 * @group hello
 *
 */
describe('Test API connectivity', () => {

	it('can access a non-authenticated endpoint', async () => {
		const result = await getRequest( '' );
		expect( result.statusCode ).toEqual( 200 );
	});

	it('can access an authenticated endpoint', async () => {
		const result = await getRequest( 'system_status' );
		expect( result.statusCode ).toEqual( 200 );
	});

});
