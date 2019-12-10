/**
 * Internal dependencies
 */
import { allSettings } from './settings-init';

/**
 * Retrieves a setting value from the setting state.
 *
 * @export
 * @param {string}   name                         The identifier for the setting.
 * @param {*}    [fallback=false]             The value to use as a fallback
 *                                                if the setting is not in the
 *                                                state.
 * @param {Function} [filter=( val ) => val]  	  A callback for filtering the
 *                                                value before it's returned.
 *                                                Receives both the found value
 *                                                (if it exists for the key) and
 *                                                the provided fallback arg.
 * @return {*} The setting value.
 */
export function getSetting( name, fallback = false, filter = ( val ) => val ) {
	const value = allSettings.hasOwnProperty( name )
		? allSettings[ name ]
		: fallback;
	return filter( value, fallback );
}
