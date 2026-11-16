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

const preloadedCart = {
	body: cartBody,
	headers: { 'Cart-Hash': 'hash-from-preload' },
};

describe( 'getPreloadedCartResponse', () => {
	it( 'returns the preloaded cart with its headers', () => {
		mockGetSetting.mockReturnValueOnce( preloadedCart );

		expect( getPreloadedCartResponse() ).toEqual( preloadedCart );
		expect( mockGetSetting ).toHaveBeenCalledWith( 'preloadedCart', null );
	} );

	it( 'returns null when the page did not preload the cart', () => {
		mockGetSetting.mockReturnValueOnce( null );

		expect( getPreloadedCartResponse() ).toBeNull();
	} );

	it( 'returns null when the preloaded cart has no body', () => {
		mockGetSetting.mockReturnValueOnce( { headers: {} } );

		expect( getPreloadedCartResponse() ).toBeNull();
	} );
} );

describe( 'Cart store with a preloaded cart', () => {
	const loadStore = ( {
		preloaded,
		editor = false,
		cartHashThrows = false,
	}: {
		preloaded: unknown;
		editor?: boolean;
		cartHashThrows?: boolean;
	} ) => {
		const startResolution = jest.fn();
		const receiveCart = jest.fn();
		const finishResolution = jest.fn();
		const setCartHash = jest.fn( () => {
			if ( cartHashThrows ) {
				throw new Error( 'QuotaExceededError' );
			}
		} );

		jest.isolateModules( () => {
			const { getSetting: actualGetSetting } = jest.requireActual< {
				getSetting: ( name: string, fallback: unknown ) => unknown;
			} >( '@woocommerce/settings' );
			jest.requireMock< { getSetting: jest.Mock } >(
				'@woocommerce/settings'
			).getSetting.mockImplementation(
				( name: string, fallback: unknown ) =>
					name === 'preloadedCart'
						? preloaded
						: actualGetSetting( name, fallback )
			);
			jest.requireMock< { dispatch: jest.Mock } >(
				'@wordpress/data'
			).dispatch.mockReturnValue( {
				startResolution,
				receiveCart,
				finishResolution,
			} );
			jest.requireMock< { isEditor: jest.Mock } >(
				'../../utils'
			).isEditor.mockReturnValue( editor );
			jest.requireActual< {
				default: { setCartHash?: jest.Mock };
			} >( '@wordpress/api-fetch' ).default.setCartHash = setCartHash;

			jest.requireActual( '../index' );
		} );

		return { startResolution, receiveCart, finishResolution, setCartHash };
	};

	it( 'starts getCartData, receives the preloaded cart, then finishes getCartData, before any block renders', () => {
		const { startResolution, receiveCart, finishResolution, setCartHash } =
			loadStore( { preloaded: preloadedCart } );

		expect( startResolution ).toHaveBeenCalledWith( 'getCartData', [] );
		expect( receiveCart ).toHaveBeenCalledWith( cartBody );
		expect( finishResolution ).toHaveBeenCalledWith( 'getCartData', [] );
		expect( setCartHash ).toHaveBeenCalledWith( {
			'Cart-Hash': 'hash-from-preload',
		} );
		expect( startResolution.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			receiveCart.mock.invocationCallOrder[ 0 ]
		);
		expect( receiveCart.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			finishResolution.mock.invocationCallOrder[ 0 ]
		);
	} );

	it( 'receives the preloaded cart when storing the cart hash fails', () => {
		const { receiveCart, finishResolution } = loadStore( {
			preloaded: preloadedCart,
			cartHashThrows: true,
		} );

		expect( receiveCart ).toHaveBeenCalledWith( cartBody );
		expect( finishResolution ).toHaveBeenCalledWith( 'getCartData', [] );
	} );

	it( 'leaves the cart to the resolver when the page did not preload it', () => {
		const { startResolution, receiveCart, finishResolution } = loadStore( {
			preloaded: null,
		} );

		expect( startResolution ).not.toHaveBeenCalled();
		expect( receiveCart ).not.toHaveBeenCalled();
		expect( finishResolution ).not.toHaveBeenCalled();
	} );

	it( 'does not receive a preloaded cart in the editor', () => {
		const { startResolution, receiveCart, finishResolution } = loadStore( {
			preloaded: preloadedCart,
			editor: true,
		} );

		expect( startResolution ).not.toHaveBeenCalled();
		expect( receiveCart ).not.toHaveBeenCalled();
		expect( finishResolution ).not.toHaveBeenCalled();
	} );
} );
