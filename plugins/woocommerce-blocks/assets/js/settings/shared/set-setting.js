/**
 * Internal dependencies
 */
import { allSettings } from './settings-init';

/**
 * Sets a value to a property on the settings state.
 *
 * @export
 * @param {string}   name                        The setting property key for the
 *                                               setting being mutated.
 * @param {mixed}    value                       The value to set.
 * @param {Function} [filter=( val ) => val]     Allows for providing a callback
 *                                               to sanitize the setting (eg.
 *                                               ensure it's a number)
 */
export function setSetting( name, value, filter = ( val ) => val ) {
	allSettings[ name ] = filter( value );
}
