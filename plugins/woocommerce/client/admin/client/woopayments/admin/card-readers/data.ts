/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { WooPaymentsCardReader } from './types';

const READERS_PATH = '/wc/v3/payments/readers';

export const getWooPaymentsCardReaders = (
	limit = 10
): Promise< WooPaymentsCardReader[] > =>
	apiFetch< WooPaymentsCardReader[] >( {
		path: `${ READERS_PATH }?limit=${ encodeURIComponent( limit ) }`,
		method: 'GET',
	} );
