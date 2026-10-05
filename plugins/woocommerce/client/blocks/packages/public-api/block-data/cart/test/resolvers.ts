/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import { getCartData } from '../resolvers';
import { store as cartStore } from '..';

jest.mock( '@wordpress/data-controls' );
jest.mock( '@wordpress/api-fetch' );
jest.unmock( '@woocommerce/block-data/utils/is-editor' );

describe( 'getCartData', () => {
	it.each( [ 'post.php?post=1&action=edit', 'site-editor.php' ] )(
		'uses the preview cart on %s without fetching the shopper cart',
		async ( path ) => {
			const originalUrl = window.location.href;
			const mockDispatch = {
				...dispatch( cartStore ),
				receiveCart: jest.fn(),
				receiveError: jest.fn(),
			};
			jest.clearAllMocks();
			window.history.replaceState( {}, '', `/wp-admin/${ path }` );
			try {
				await getCartData()( { dispatch: mockDispatch } );
				expect( mockDispatch.receiveCart ).toHaveBeenCalledWith(
					previewCart
				);
				expect( apiFetch ).not.toHaveBeenCalled();
			} finally {
				window.history.replaceState( {}, '', originalUrl );
			}
		}
	);

	it( 'receives the real cart on the frontend', async () => {
		const mockDispatch = {
			...dispatch( cartStore ),
			receiveCart: jest.fn(),
			receiveError: jest.fn(),
		};
		apiFetch.mockReturnValue(
			Promise.resolve( {
				status: 200,
				json: () =>
					Promise.resolve( {
						coupons: [],
						items: [],
						fees: [],
						itemsCount: 0,
						itemsWeight: 0,
						needsShipping: true,
						totals: {},
					} ),
			} )
		);
		await getCartData()( { dispatch: mockDispatch } );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/wc/store/v1/cart',
			method: 'GET',
			cache: 'no-store',
			parse: false,
		} );
		expect( mockDispatch.receiveCart ).toHaveBeenCalledWith( {
			coupons: [],
			items: [],
			fees: [],
			itemsCount: 0,
			itemsWeight: 0,
			needsShipping: true,
			totals: {},
		} );
		expect( mockDispatch.receiveError ).not.toHaveBeenCalled();
	} );
	it( 'when apiFetch returns an invalid response, dispatches the correct error action', async () => {
		const mockDispatch = {
			...dispatch( cartStore ),
			receiveCart: jest.fn(),
			receiveError: jest.fn(),
		};
		apiFetch.mockReturnValue(
			Promise.resolve( {
				status: 200,
				json: () => Promise.resolve( undefined ),
			} )
		);
		await getCartData()( { dispatch: mockDispatch } );
		expect( mockDispatch.receiveCart ).not.toHaveBeenCalled();
		expect( mockDispatch.receiveError ).toHaveBeenCalled();
	} );
} );
