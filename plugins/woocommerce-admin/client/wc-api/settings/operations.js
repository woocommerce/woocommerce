/** @format */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';

function read( resourceNames, fetch = apiFetch ) {
	return [ ...readSettings( resourceNames, fetch ) ];
}

function update( resourceNames, data, fetch = apiFetch ) {
	return [ ...updateSettings( resourceNames, data, fetch ) ];
}

function readSettings( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( name => {
		return name.startsWith( 'settings/' );
	} );

	return filteredNames.map( async resourceName => {
		const url = NAMESPACE + '/' + resourceName;

		return fetch( { path: url } )
			.then( settingsToSettingsResource.bind( null, resourceName ) )
			.catch( error => {
				return { [ resourceName ]: { error: String( error.message ) } };
			} );
	} );
}

function updateSettings( resourceNames, data, fetch ) {
	const filteredNames = resourceNames.filter( name => {
		return name.startsWith( 'settings/' );
	} );

	return filteredNames.map( async resourceName => {
		const url = NAMESPACE + '/' + resourceName + '/batch';
		const settingsData = Object.keys( data[ resourceName ] ).map( key => {
			return { id: key, value: data[ resourceName ][ key ] };
		} );

		return fetch( {
			path: url,
			method: 'POST',
			data: { update: settingsData },
		} )
			.then( settingToSettingsResource.bind( null, resourceName ) )
			.catch( error => {
				return { [ resourceName ]: { error } };
			} );
	} );
}

function settingsToSettingsResource( resourceName, settings ) {
	const settingsData = {};
	settings.forEach( setting => ( settingsData[ setting.id ] = setting.value ) );
	return { [ resourceName ]: { data: settingsData } };
}

function settingToSettingsResource( resourceName, data ) {
	const settings = {};
	if ( 'undefined' === typeof data.update ) {
		return '';
	}

	// @todo This will only return updated fields so fields
	// not updated may be temporarily overwritten in the store.
	data.update.forEach( setting => ( settings[ setting.id ] = setting.value ) );

	return { [ resourceName ]: { data: settings } };
}

export default {
	read,
	update,
};
