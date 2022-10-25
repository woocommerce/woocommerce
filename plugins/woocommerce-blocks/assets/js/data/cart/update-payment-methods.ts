/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { STORE_KEY as PAYMENT_STORE_KEY } from '../payment/constants';
import { STORE_KEY } from './constants';

export const updatePaymentMethods = debounce( async () => {
	const isInitialized =
		select( STORE_KEY ).hasFinishedResolution( 'getCartData' );

	if ( ! isInitialized ) {
		return;
	}

	await dispatch(
		PAYMENT_STORE_KEY
	).__internalUpdateAvailablePaymentMethods();
}, 1000 );
