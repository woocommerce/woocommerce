/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

// Remove mutable data from settings object to prevent access. Data stores should be used instead.
const mutableSources = [ 'wcAdminSettings', 'preloadSettings' ];
const settings = typeof wcSettings === 'object' ? wcSettings : {};
const SOURCE = Object.keys( settings ).reduce( ( source, key ) => {
	if ( ! mutableSources.includes( key ) ) {
		source[ key ] = settings[ key ];
	}
	return source;
}, {} );

export const ADMIN_URL = SOURCE.adminUrl;
export const COUNTRIES = SOURCE.countries;
export const CURRENCY = SOURCE.currency;
export const LOCALE = SOURCE.locale;
export const ORDER_STATUSES = SOURCE.orderStatuses;
export const SITE_TITLE = SOURCE.siteTitle;
export const WC_ASSET_URL = SOURCE.wcAssetUrl;

/**
 * Retrieves a setting value from the setting state.
 *
 * @param {string}   name                         The identifier for the setting.
 * @param {*}    [fallback=false]             The value to use as a fallback
 *                                                if the setting is not in the
 *                                                state.
 * @param {Function} [filter=( val ) => val]  	  A callback for filtering the
 *                                                value before it's returned.
 *                                                Receives both the found value
 *                                                (if it exists for the key) and
 *                                                the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */
export function getSetting( name, fallback = false, filter = ( val ) => val ) {
	if ( mutableSources.includes( name ) ) {
		throw new Error(
			__( 'Mutable settings should be accessed via data store.' )
		);
	}
	const value = SOURCE.hasOwnProperty( name ) ? SOURCE[ name ] : fallback;
	return filter( value, fallback );
}

/**
 * Sets a value to a property on the settings state.
 *
 * NOTE: This feature is to be removed in favour of data stores when a full migration
 * is complete.
 *
 * @deprecated
 *
 * @param {string}   name                        The setting property key for the
 *                                               setting being mutated.
 * @param {*}    value                       The value to set.
 * @param {Function} [filter=( val ) => val]     Allows for providing a callback
 *                                               to sanitize the setting (eg.
 *                                               ensure it's a number)
 */
export function setSetting( name, value, filter = ( val ) => val ) {
	if ( mutableSources.includes( name ) ) {
		throw new Error(
			__( 'Mutable settings should be mutated via data store.' )
		);
	}
	SOURCE[ name ] = filter( value );
}

/**
 * Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {string} path Relative path.
 * @return {string} Full admin URL.
 */
export function getAdminLink( path ) {
	return ( ADMIN_URL || '' ) + path;
}

/**
 * Adds a script to the page if it has not already been loaded. JS version of `wp_enqueue_script`.
 *
 * @param {Object} script WP_Script
 * @param {string} script.handle Script handle.
 * @param {string} script.src Script URL.
 */
export function enqueueScript( script ) {
	return new Promise( ( resolve, reject ) => {
		if ( document.querySelector( `#${ script.handle }-js` ) ) {
			resolve();
		}

		const domElement = document.createElement( 'script' );
		domElement.src = script.src;
		domElement.id = `${ script.handle }-js`;
		domElement.async = true;
		domElement.onload = resolve;
		domElement.onerror = reject;
		document.body.appendChild( domElement );
	} );
}
