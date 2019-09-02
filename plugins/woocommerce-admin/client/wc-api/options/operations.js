/** @format */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getResourceIdentifier, getResourceName } from '../utils';
import { WC_ADMIN_NAMESPACE } from '../constants';

function read( resourceNames, fetch = apiFetch ) {
	return [ ...readOptions( resourceNames, fetch ) ];
}

function update( resourceNames, data, fetch = apiFetch ) {
	return [ ...updateOptions( resourceNames, data, fetch ) ];
}

function readOptions( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( name => {
		return name.startsWith( 'options' );
	} );

	return filteredNames.map( async resourceName => {
		const optionNames = getResourceIdentifier( resourceName );
		const url = WC_ADMIN_NAMESPACE + '/options?options=' + optionNames.join( ',' );

		return fetch( { path: url } )
			.then( optionsToResource )
			.catch( error => {
				return { [ resourceName ]: { error: String( error.message ) } };
			} );
	} );
}

function updateOptions( resourceNames, data, fetch ) {
	const url = WC_ADMIN_NAMESPACE + '/options';

	const filteredNames = resourceNames.filter( name => {
		return name.startsWith( 'options' );
	} );

	return filteredNames.map( async resourceName => {
		return fetch( { path: url, method: 'POST', data: data[ resourceName ] } )
			.then( () => optionsToResource( data[ resourceName ] ) )
			.catch( error => {
				return { [ resourceName ]: { error } };
			} );
	} );
}

function optionsToResource( options ) {
	const optionNames = Object.keys( options );
	const resourceName = getResourceName( 'options', optionNames );
	const resources = {};

	optionNames.forEach(
		optionName =>
			( resources[ getResourceName( 'options', optionName ) ] = { data: options[ optionName ] } )
	);

	return {
		[ resourceName ]: {
			data: optionNames,
		},
		...resources,
	};
}

export default {
	read,
	update,
};
