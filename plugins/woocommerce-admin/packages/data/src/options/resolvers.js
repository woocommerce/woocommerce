/**
 * External Dependencies
 */
import { batchFetch } from './controls';

/**
 * Internal dependencies
 */
import { receiveOptions, setRequestingError } from './actions';

/**
 * Request an option value.
 *
 * @param {string} name - Option name
 */
export function* getOption( name ) {
	try {
		const result = yield batchFetch( name );
		yield receiveOptions( result );
	} catch ( error ) {
		yield setRequestingError( error, name );
	}
}
