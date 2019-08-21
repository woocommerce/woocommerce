/** @format */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
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
	const resources = {};

	const settingIds = settings.map( setting => setting.id );
	settings.forEach(
		setting =>
			( resources[ getResourceName( resourceName, setting.id ) ] = { data: setting.value } )
	);

	return {
		[ resourceName ]: {
			data: settingIds,
		},
		...resources,
	};
}

function settingToSettingsResource( resourceName, data ) {
	if ( 'undefined' === typeof data.update ) {
		return '';
	}

	// Override lastReceived time for group when batch updating.
	const resources = { [ resourceName ]: { lastReceived: Date.now() } };
	data.update.forEach(
		setting =>
			( resources[ getResourceName( resourceName, setting.id ) ] = { data: setting.value } )
	);

	return resources;
}

export default {
	read,
	update,
};
