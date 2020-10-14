import { buildURL } from '../utils';

describe( 'buildURL', () => {
	it( 'should use base when given no url', () => {
		const url = buildURL( { baseURL: 'http://test.test' } );
		expect( url ).toBe( 'http://test.test' );
	} );

	it( 'should use url when given absolute', () => {
		const url = buildURL( { baseURL: 'http://test.test', url: 'http://override.test' } );
		expect( url ).toBe( 'http://override.test' );
	} );

	it( 'should combine base and url', () => {
		const url = buildURL( { baseURL: 'http://test.test', url: 'yes/test' } );
		expect( url ).toBe( 'http://test.test/yes/test' );
	} );

	it( 'should combine base and url with trailing/leading slashes', () => {
		const url = buildURL( { baseURL: 'http://test.test/////', url: '////yes/test' } );
		expect( url ).toBe( 'http://test.test/yes/test' );
	} );
} );
