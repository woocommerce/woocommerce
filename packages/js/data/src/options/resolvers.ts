/**
 * Internal dependencies
 */
import { receiveOptions, setRequestingError } from './actions';
import { batchFetch } from './controls';
import { Options } from './types';

/**
 * Request an option value.
 *
 * @param {string} name - Option name
 */
export function* getOption( name: string ) {
	try {
		const result: Options = yield batchFetch( name );
		yield receiveOptions( result );
	} catch ( error ) {
		yield setRequestingError( error, name );
	}
}
