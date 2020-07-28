/**
 * Internal dependencies
 */
import { receiveOptions, setRequestingError } from './actions';
import { batchFetch } from './controls';

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
