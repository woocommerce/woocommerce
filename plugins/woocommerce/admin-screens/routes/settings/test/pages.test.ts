/**
 * External dependencies
 */
import warning from '@wordpress/warning';

/**
 * Internal dependencies
 */
import { getPageCrumbs, getPageName } from '../pages';

jest.mock( '@wordpress/warning', () => jest.fn() );

const labels: Record< string, string > = {
	cheque: 'Check payments',
	'cheque/messages': 'Customer messages',
	'cheque/messages/more': 'More options',
};
const getLabel = ( name: string ) => labels[ name ];

describe( 'getPageName', () => {
	it.each( [
		[ 'cheque', undefined, 'cheque' ],
		[ 'cheque', '', 'cheque' ],
		[ 'cheque', 'messages/more', 'cheque/messages/more' ],
		[ 'cheque', 'messages/', 'cheque/messages' ],
	] )( 'names %s with sub-path %s as %s', ( page, subPath, name ) => {
		expect( getPageName( page, subPath ) ).toBe( name );
	} );
} );

describe( 'getPageCrumbs', () => {
	beforeEach( () => {
		jest.mocked( warning ).mockClear();
	} );

	it( 'shows a page without links when it is the top level', () => {
		expect( getPageCrumbs( 'payments', 'cheque', getLabel ) ).toEqual( [
			{ label: 'Check payments' },
		] );
	} );

	it( 'links each page above a nested sub-page', () => {
		expect(
			getPageCrumbs( 'payments', 'cheque/messages/more', getLabel )
		).toEqual( [
			{ label: 'Check payments', to: '/settings/payments/cheque' },
			{
				label: 'Customer messages',
				to: '/settings/payments/cheque/messages',
			},
			{ label: 'More options' },
		] );
		expect( warning ).not.toHaveBeenCalled();
	} );

	it( 'warns and shows the URL segment for an unregistered page above', () => {
		const crumbs = getPageCrumbs(
			'payments',
			'cheque/missing/more',
			( name ) => ( name === 'cheque/missing' ? undefined : 'Label' )
		);

		expect( crumbs[ 1 ] ).toEqual( {
			label: 'missing',
			to: '/settings/payments/cheque/missing',
		} );
		expect( warning ).toHaveBeenCalledWith(
			expect.stringContaining( '"cheque/missing"' )
		);
	} );
} );
