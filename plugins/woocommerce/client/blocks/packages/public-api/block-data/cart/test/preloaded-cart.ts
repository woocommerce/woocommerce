/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import type { CartResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { getPreloadedCartResponse } from '../preloaded-cart';

jest.mock( '@wordpress/data' );
jest.mock( '@woocommerce/settings', () => {
	const actual = jest.requireActual( '@woocommerce/settings' );
	return {
		...actual,
		getSetting: jest.fn( actual.getSetting ),
	};
} );
jest.mock( '../../utils', () => ( {
	...jest.requireActual( '../../utils' ),
	isEditor: jest.fn().mockReturnValue( false ),
} ) );

const mockGetSetting = jest.mocked( getSetting );

const cartBody = {
	items: [ { key: 'abc', quantity: 1 } ],
	items_count: 1,
} as unknown as CartResponse;

const preloadedApiRequests = {
	'/wc/store/v1/cart': {
		body: cartBody,
		headers: { 'Cart-Hash': 'hash-from-preload' },
	},
};

describe( 'getPreloadedCartResponse', () => {
	it( 'returns the preloaded cart with its headers', () => {
		mockGetSetting.mockReturnValueOnce( preloadedApiRequests );

		expect( getPreloadedCartResponse() ).toEqual( {
			body: cartBody,
			headers: { 'Cart-Hash': 'hash-from-preload' },
		} );
		expect( mockGetSetting ).toHaveBeenCalledWith(
			'preloadedApiRequests',
			{}
		);
	} );

	it( 'returns null when the page did not preload the cart', () => {
		mockGetSetting.mockReturnValueOnce( {
			'/wc/store/v1/products': { body: [], headers: {} },
		} );

		expect( getPreloadedCartResponse() ).toBeNull();
	} );

	it( 'returns null when nothing was preloaded', () => {
		mockGetSetting.mockReturnValueOnce( {} );

		expect( getPreloadedCartResponse() ).toBeNull();
	} );

	it( 'returns null when the preloaded cart has no body', () => {
		mockGetSetting.mockReturnValueOnce( {
			'/wc/store/v1/cart': { headers: {} },
		} );

		expect( getPreloadedCartResponse() ).toBeNull();
	} );
} );

describe( 'Cart store with a preloaded cart', () => {
	const loadStore = ( {
		preloaded,
		editor = false,
	}: {
		preloaded: Record< string, unknown >;
		editor?: boolean;
	} ) => {
		const receiveCart = jest.fn();
		const finishResolution = jest.fn();
		const setCartHash = jest.fn();

		jest.isolateModules( () => {
			const { getSetting: actualGetSetting } = jest.requireActual< {
				getSetting: ( name: string, fallback: unknown ) => unknown;
			} >( '@woocommerce/settings' );
			jest.requireMock< { getSetting: jest.Mock } >(
				'@woocommerce/settings'
			).getSetting.mockImplementation(
				( name: string, fallback: unknown ) =>
					name === 'preloadedApiRequests'
						? preloaded
						: actualGetSetting( name, fallback )
			);
			jest.requireMock< { dispatch: jest.Mock } >(
				'@wordpress/data'
			).dispatch.mockReturnValue( { receiveCart, finishResolution } );
			jest.requireMock< { isEditor: jest.Mock } >(
				'../../utils'
			).isEditor.mockReturnValue( editor );
			jest.requireActual< {
				default: { setCartHash?: jest.Mock };
			} >( '@wordpress/api-fetch' ).default.setCartHash = setCartHash;

			jest.requireActual( '../index' );
		} );

		return { receiveCart, finishResolution, setCartHash };
	};

	it( 'receives the preloaded cart and resolves getCartData before any block renders', () => {
		const { receiveCart, finishResolution, setCartHash } = loadStore( {
			preloaded: preloadedApiRequests,
		} );

		expect( receiveCart ).toHaveBeenCalledWith( cartBody );
		expect( finishResolution ).toHaveBeenCalledWith( 'getCartData' );
		expect( setCartHash ).toHaveBeenCalledWith( {
			'Cart-Hash': 'hash-from-preload',
		} );
		expect( receiveCart.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			finishResolution.mock.invocationCallOrder[ 0 ]
		);
	} );

	it( 'leaves the cart to the resolver when the page did not preload it', () => {
		const { receiveCart, finishResolution } = loadStore( {
			preloaded: {},
		} );

		expect( receiveCart ).not.toHaveBeenCalled();
		expect( finishResolution ).not.toHaveBeenCalled();
	} );

	it( 'does not receive a preloaded cart in the editor', () => {
		const { receiveCart, finishResolution } = loadStore( {
			preloaded: preloadedApiRequests,
			editor: true,
		} );

		expect( receiveCart ).not.toHaveBeenCalled();
		expect( finishResolution ).not.toHaveBeenCalled();
	} );
} );
