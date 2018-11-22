/*
* @format
*/

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/utils';
import selectors from '../selectors';

const { getReportItems, isGetReportItemsRequesting, isGetReportItemsError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const query = { orderby: 'date' };
const queryKey = getJsonString( query );
const endpoint = 'coupons';

describe( 'getReportItems()', () => {
	it( 'returns an empty array when no items are available', () => {
		const state = deepFreeze( {} );
		expect( getReportItems( state, endpoint, query ) ).toEqual( [] );
	} );

	it( 'returns stored items for current query', () => {
		const items = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];
		const state = deepFreeze( {
			reports: {
				items: {
					[ endpoint ]: {
						[ queryKey ]: items,
					},
				},
			},
		} );
		expect( getReportItems( state, endpoint, query ) ).toEqual( items );
	} );
} );

describe( 'isGetReportItemsRequesting()', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getReportItems'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isGetReportItemsRequesting( endpoint );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isGetReportItemsRequesting( endpoint );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isGetReportItemsRequesting( endpoint );
		expect( result ).toBe( true );
	} );
} );

describe( 'isGetReportItemsError()', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isGetReportItemsError( state, endpoint, query ) ).toEqual( false );
	} );

	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			reports: {
				items: {
					[ endpoint ]: {
						[ queryKey ]: ERROR,
					},
				},
			},
		} );
		expect( isGetReportItemsError( state, endpoint, query ) ).toEqual( true );
	} );
} );
