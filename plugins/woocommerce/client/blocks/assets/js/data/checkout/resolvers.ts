/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import type {
	ApiErrorResponse,
	CartResponse,
	CheckoutResponseSuccess,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { isEditor } from '../utils/is-editor';
import { processErrorResponse } from '../utils';
import { STORE_KEY } from './constants';
import { CART_STORE_KEY } from '../cart';
import type { CheckoutDispatchFromMap } from './index';

/**
 * Proper cookie-name match (avoids substring false positives from `includes`).
 */
const hasCartHashCookie = (): boolean =>
	document.cookie
		.split( ';' )
		.some( ( cookie ) =>
			cookie.trim().startsWith( 'woocommerce_cart_hash=' )
		);

const previewCheckoutData: CheckoutResponseSuccess = {
	order_id: 1,
	customer_id: 0,
	billing_address: {} as CheckoutResponseSuccess[ 'billing_address' ],
	shipping_address: {} as CheckoutResponseSuccess[ 'shipping_address' ],
	customer_note: '',
	extensions: {},
	order_key: '',
	payment_method: '',
	payment_result: {
		payment_details: {},
		payment_status: 'success',
		redirect_url: '',
	},
	status: 'draft',
};

/**
 * Resolver for checkout data. Skipped when state is already hydrated.
 */
export const getCheckoutData =
	() =>
	async ( {
		dispatch: checkoutDispatch,
		select,
	}: {
		dispatch: CheckoutDispatchFromMap;
		select: ( key: typeof STORE_KEY ) => {
			getOrderId: () => number;
		};
	} ) => {
		if ( isEditor() ) {
			checkoutDispatch.receiveCheckoutData( previewCheckoutData );
			return;
		}

		if ( select( STORE_KEY ).getOrderId() > 0 ) {
			return;
		}

		if ( ! hasCartHashCookie() ) {
			return;
		}

		try {
			const response = await apiFetch<
				CheckoutResponseSuccess & {
					// eslint-disable-next-line @typescript-eslint/naming-convention -- experimental Store API response field.
					__experimentalCart?: CartResponse;
				}
			>( {
				path: '/wc/store/v1/checkout?__experimental_calc_totals=true',
				method: 'GET',
				cache: 'no-store',
			} );

			if ( response ) {
				checkoutDispatch.receiveCheckoutData( response );

				if ( response.__experimentalCart ) {
					dispatch( CART_STORE_KEY ).receiveCartContents(
						response.__experimentalCart
					);
				}
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Checkout data fetch failed:', error );
			processErrorResponse( error as ApiErrorResponse );
		}
	};
