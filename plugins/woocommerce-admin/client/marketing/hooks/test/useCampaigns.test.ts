/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks/dom';
import { waitFor } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import 'whatwg-fetch'; /* eslint-disable-line import/no-unresolved */ /* To make sure Response is available */

/**
 * Internal dependencies
 */
import { useCampaigns } from '../useCampaigns';
import { useRegisteredChannels } from '../useRegisteredChannels';
import { STORE_KEY } from '../../data-multichannel/constants';
import { RegisteredChannel } from '../../types';
import { Campaign as APICampaign } from '../../data-multichannel/types';
import '../../data-multichannel'; // To ensure the store is registered

type Channel = Pick< RegisteredChannel, 'slug' | 'title' | 'icon' >;

jest.mock( '@wordpress/api-fetch', () =>
	jest.fn( ( { path } ) => {
		const total = 9;

		const params = new URLSearchParams( path.replace( /^[^?]*/, '' ) );
		const page = Number( params.get( 'page' ) );
		const perPage = Number( params.get( 'per_page' ) );

		if ( ! Number.isInteger( page ) || ! Number.isInteger( perPage ) ) {
			return Promise.reject(
				new Response( '{"message": "invalid query"}', { status: 400 } )
			);
		}

		const length = Math.min( perPage, total - ( page - 1 ) * perPage );

		const campaigns: Array< APICampaign > = Array.from( { length } ).map(
			( _, index ) => {
				const id = `${ page }_${ index + 1 }`;
				return {
					id,
					channel: 'extension-foo',
					title: `Campaign ${ id }`,
					manage_url: `https://test/extension-foo?path=setup&id=${ id }`,
					cost: {
						value: ( ( page * perPage + index ) * 0.25 ).toString(),
						currency: 'USD',
					},
					sales: {
						value: ( ( page * perPage + index ) * 0.25 ).toString(),
						currency: 'USD',
					},
				};
			}
		);

		// For testing fallbacks when data fields are not available
		if ( campaigns[ 2 ] ) {
			campaigns[ 2 ].channel = 'intentional-mismatch-channel';
			campaigns[ 2 ].cost = null;
			campaigns[ 2 ].sales = null;
		}

		return Promise.resolve(
			new Response( JSON.stringify( campaigns ), {
				headers: new Headers( { 'x-wp-total': total.toString() } ),
			} )
		);
	} )
);

jest.mock( '../useRegisteredChannels', () => ( {
	useRegisteredChannels: jest.fn(),
} ) );

function mockRegisteredChannels( ...channels: Array< Channel > ) {
	(
		useRegisteredChannels as jest.MockedFunction<
			typeof useRegisteredChannels
		>
	 ).mockReturnValue( {
		loading: false,
		data: channels.map( ( channel ) => ( {
			...channel,
			// The following is not relevant to this test scope
			description: '',
			isSetupCompleted: true,
			setupUrl: '',
			manageUrl: '',
			syncStatus: 'synced',
			issueType: 'none',
			issueText: '',
		} ) ),
		refetch: () => {},
	} );
}

describe( 'useCampaigns', () => {
	beforeEach( () => {
		dispatch( STORE_KEY ).invalidateResolutionForStoreSelector(
			'getCampaigns'
		);

		mockRegisteredChannels( {
			slug: 'extension-foo',
			title: 'Extension Foo',
			icon: 'https://test/foo.png',
		} );
	} );

	it( 'should return correct data', async () => {
		const { result } = renderHook( () => useCampaigns() );

		await waitFor( () => expect( result.current.loading ).toBe( false ) );
		expect( result.current.error ).toBeUndefined();

		// Campaign matched with a channel
		expect( result.current.data?.[ 0 ] ).toEqual( {
			id: 'extension-foo|1_1',
			title: 'Campaign 1_1',
			description: '',
			cost: 'USD 1.25',
			sales: 'USD 1.25',
			manageUrl: 'https://test/extension-foo?path=setup&id=1_1',
			icon: 'https://test/foo.png',
			channelName: 'Extension Foo',
			channelSlug: 'extension-foo',
		} );

		// Campaign didn't match any channel
		expect( result.current.data?.[ 2 ] ).toEqual( {
			id: 'intentional-mismatch-channel|1_3',
			title: 'Campaign 1_3',
			description: '',
			cost: '-',
			sales: '-',
			manageUrl: 'https://test/extension-foo?path=setup&id=1_3',
			icon: '',
			channelName: '',
			channelSlug: 'intentional-mismatch-channel',
		} );
	} );

	it( 'should handle error', async () => {
		const { result } = renderHook( () => useCampaigns( 1.5 ) );

		await waitFor( () => expect( result.current.loading ).toBe( false ) );
		expect( result.current.data ).toBeUndefined();
		expect( result.current.error ).toEqual( { message: 'invalid query' } );
	} );

	it( 'should handle pagination according to the page and perPage arguments', async () => {
		// Initial page
		const { result, rerender } = renderHook<
			{ page: number; perPage: number },
			ReturnType< typeof useCampaigns >
		>( ( { page, perPage } ) => useCampaigns( page, perPage ), {
			initialProps: { page: 1, perPage: 5 },
		} );

		await waitFor( () => expect( result.current.loading ).toBe( false ) );
		expect( result.current.meta ).toEqual( { total: 9 } );
		expect( result.current.data ).toHaveLength( 5 );
		expect( result.current.data?.[ 0 ].id ).toEqual( 'extension-foo|1_1' );
		expect( result.current.data?.[ 4 ].id ).toEqual( 'extension-foo|1_5' );

		// Change page
		rerender( { page: 2, perPage: 5 } );

		await waitFor( () => expect( result.current.loading ).toBe( false ) );
		expect( result.current.data ).toHaveLength( 4 );
		expect( result.current.data?.[ 0 ].id ).toEqual( 'extension-foo|2_1' );
		expect( result.current.data?.[ 1 ].id ).toEqual( 'extension-foo|2_2' );

		// Change page to a page that doesn't exist
		rerender( { page: 3, perPage: 5 } );

		await waitFor( () => expect( result.current.loading ).toBe( false ) );
		expect( result.current.data ).toEqual( [] );

		// Change perPage
		rerender( { page: 3, perPage: 4 } );

		await waitFor( () => expect( result.current.loading ).toBe( false ) );
		expect( result.current.meta ).toEqual( { total: 9 } );
		expect( result.current.data ).toHaveLength( 1 );
		expect( result.current.data?.[ 0 ].id ).toEqual( 'extension-foo|3_1' );
	} );

	it( 'should update data accordingly once the registered channels are updated', async () => {
		const { result, rerender } = renderHook( () => useCampaigns() );

		await waitFor( () => expect( result.current.loading ).toBe( false ) );
		expect( result.current.data?.[ 0 ] ).toMatchObject( {
			id: 'extension-foo|1_1',
			channelName: 'Extension Foo',
			icon: 'https://test/foo.png',
		} );

		// Update registered channels
		mockRegisteredChannels( {
			slug: 'extension-foo',
			title: 'Extension Bar',
			icon: 'https://test/bar.png',
		} );

		rerender();

		expect( result.current.data?.[ 0 ] ).toMatchObject( {
			id: 'extension-foo|1_1',
			channelName: 'Extension Bar',
			icon: 'https://test/bar.png',
		} );
	} );

	it( 'should be able to use different arguments for different instances at the same time', async () => {
		const { result: resultA } = renderHook( () => useCampaigns( 1, 2 ) );
		const { result: resultB } = renderHook( () => useCampaigns( 2, 2 ) );
		const { result: resultC } = renderHook( () => useCampaigns( 2, 4 ) );

		await waitFor( () => expect( resultA.current.loading ).toBe( false ) );
		await waitFor( () => expect( resultB.current.loading ).toBe( false ) );
		await waitFor( () => expect( resultC.current.loading ).toBe( false ) );

		expect( resultA.current.data ).toHaveLength( 2 );
		expect( resultA.current.data?.[ 0 ].id ).toEqual( 'extension-foo|1_1' );
		expect( resultA.current.data?.[ 1 ].id ).toEqual( 'extension-foo|1_2' );

		expect( resultB.current.data ).toHaveLength( 2 );
		expect( resultB.current.data?.[ 0 ].id ).toEqual( 'extension-foo|2_1' );
		expect( resultB.current.data?.[ 1 ].id ).toEqual( 'extension-foo|2_2' );

		expect( resultC.current.data ).toHaveLength( 4 );
		expect( resultC.current.data?.[ 0 ].id ).toEqual( 'extension-foo|2_1' );
		expect( resultC.current.data?.[ 3 ].id ).toEqual( 'extension-foo|2_4' );
	} );
} );
