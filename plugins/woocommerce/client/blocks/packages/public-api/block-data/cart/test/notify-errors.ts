/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { createNotice } from '@woocommerce/base-utils';
import { ApiErrorResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { updateCartErrorNotices } from '../notify-errors';

jest.mock( '@wordpress/data' );
jest.mock( '@woocommerce/base-utils', () => ( {
	...jest.requireActual( '@woocommerce/base-utils' ),
	createNotice: jest.fn(),
} ) );

const mockedRemoveNotice = jest.fn();
( dispatch as jest.Mock ).mockImplementation( ( store ) => {
	if ( store === 'core/notices' ) {
		return { removeNotice: mockedRemoveNotice };
	}
} );

const cartError = ( code: string, message: string ): ApiErrorResponse => ( {
	code,
	message,
	data: { status: 400, params: {}, details: {} },
} );

describe( 'updateCartErrorNotices', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'creates a notice for each error that shares an error code', () => {
		updateCartErrorNotices( [
			cartError(
				'woocommerce_rest_cart_item_error',
				'First error message'
			),
			cartError(
				'woocommerce_rest_cart_item_error',
				'Second error message'
			),
			cartError( 'woocommerce_rest_cart_coupon_error', 'Coupon error' ),
		] );

		expect( createNotice ).toHaveBeenCalledTimes( 3 );
		expect( createNotice ).toHaveBeenNthCalledWith(
			1,
			'error',
			'First error message',
			{ id: 'woocommerce_rest_cart_item_error', context: 'wc/cart' }
		);
		expect( createNotice ).toHaveBeenNthCalledWith(
			2,
			'error',
			'Second error message',
			{ id: 'woocommerce_rest_cart_item_error__2', context: 'wc/cart' }
		);
		expect( createNotice ).toHaveBeenNthCalledWith(
			3,
			'error',
			'Coupon error',
			{ id: 'woocommerce_rest_cart_coupon_error', context: 'wc/cart' }
		);
	} );

	it( 'reuses the notice ID for an exact repeat of an error', () => {
		updateCartErrorNotices( [
			cartError( 'woocommerce_rest_cart_item_error', 'Same message' ),
			cartError( 'woocommerce_rest_cart_item_error', 'Same message' ),
		] );

		const ids = ( createNotice as jest.Mock ).mock.calls.map(
			( call ) => call[ 2 ].id
		);
		expect( ids ).toEqual( [
			'woocommerce_rest_cart_item_error',
			'woocommerce_rest_cart_item_error',
		] );
	} );

	it( 'dismisses every notice created for the old errors', () => {
		updateCartErrorNotices(
			[],
			[
				cartError(
					'woocommerce_rest_cart_item_error',
					'First error message'
				),
				cartError(
					'woocommerce_rest_cart_item_error',
					'Second error message'
				),
			]
		);

		expect( mockedRemoveNotice ).toHaveBeenCalledWith(
			'woocommerce_rest_cart_item_error',
			'wc/cart'
		);
		expect( mockedRemoveNotice ).toHaveBeenCalledWith(
			'woocommerce_rest_cart_item_error__2',
			'wc/cart'
		);
	} );
} );
