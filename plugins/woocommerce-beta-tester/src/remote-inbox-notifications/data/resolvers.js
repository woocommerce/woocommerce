/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { setLoadingState, setNotifications } from './actions';

export function* getNotifications() {
	yield setLoadingState( true );

	try {
		const response = yield apiFetch( {
			path: 'wc-admin-test-helper/remote-inbox-notifications',
		} );

		yield setNotifications( response );
	} catch ( error ) {
		throw new Error();
	}
}
