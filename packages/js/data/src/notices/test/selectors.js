/**
 * Internal dependencies
 */
import { getNotices } from '../selectors';

describe( 'getNotices', () => {
	it( 'should return an empty object by default', () => {
		const state = {
			notices: {},
		};

		expect( getNotices( state, {} ) ).toEqual( {} );
	} );

	it( 'should return all store notices', () => {
		const state = {
			notices: {
				testNotice: 'test',
				testNotice2: 'test2',
			},
		};
		expect( getNotices( state, {} ) ).toEqual( {
			testNotice: 'test',
			testNotice2: 'test2',
		} );
	} );
} );
