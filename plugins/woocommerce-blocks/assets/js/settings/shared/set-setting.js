/**
 * Internal dependencies
 */
import { allSettings } from './settings-init';

/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Sets a value to a property on the settings state.
 *
 * @export
 * @param {string}   name                        The setting property key for the
 *                                               setting being mutated.
 * @param {*}        value                       The value to set.
 * @param {Function} [filter=( val ) => val]     Allows for providing a callback
 *                                               to sanitize the setting (eg.
 *                                               ensure it's a number)
 *
 * @todo  Remove setSetting function from `@woocommerce/settings`.
 *
 *  The `wc.wcSettings.setSetting` function was deprecated beginning with WooCommerce Blocks 3.2.0
 *  and can be removed completely with WooCommerce Blocks 3.8.0 (that gives 6 versions of the
 *  feature plugin for warning and 3 versions of WooCommerce core).
 */
export function setSetting( name, value, filter = ( val ) => val ) {
	deprecated( 'setSetting', {
		version: '3.8.0',
		alternative: `a locally scoped value for "${ name }"`,
		plugin: 'WooCommerce Blocks',
		hint:
			'wc.wcSettings is a global settings configuration object that should not be mutated during a session. Hence the removal of this function.',
	} );
	allSettings[ name ] = filter( value );
}
