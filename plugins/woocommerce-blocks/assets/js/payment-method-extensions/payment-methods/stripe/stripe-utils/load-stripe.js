/**
 * External dependencies
 */
import { loadStripe } from '@stripe/stripe-js';

/**
 * Internal dependencies
 */
import { getApiKey } from './utils';

const stripePromise = new Promise( ( resolve ) => {
	let stripe = null;
	try {
		stripe = loadStripe( getApiKey() );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		//console.error( error.message );
	}
	resolve( stripe );
} );

export default stripePromise;
