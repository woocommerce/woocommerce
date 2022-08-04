/**
 * External dependencies
 */
import {
	apiFetch,
	dispatch as depreciatedDispatch,
} from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { STORE_NAME } from './constants';
import { updateSettingsForGroup, updateErrorForGroup } from './actions';
import { isRestApiError } from '../types';

// Can be removed in WP 5.9.
const dispatch =
	controls && controls.dispatch ? controls.dispatch : depreciatedDispatch;

type RawSetting = {
	id: string;
	label: string;
	description: string;
	type: string;
	default: string;
	options: {
		[ key: string ]: string;
	};
	value: string;
	_links: {
		collection: Array< {
			href: string;
		} >;
		self: Array< {
			href: string;
		} >;
	};
};

function settingsToSettingsResource( settings: RawSetting[] ) {
	return settings.reduce< {
		[ id: string ]: string;
	} >( ( resource, setting ) => {
		resource[ setting.id ] = setting.value;
		return resource;
	}, {} );
}

export function* getSettings( group: string ) {
	yield dispatch( STORE_NAME, 'setIsRequesting', group, true );
	try {
		const url = NAMESPACE + '/settings/' + group;
		const results: RawSetting[] = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		const resource = settingsToSettingsResource( results );

		return updateSettingsForGroup( group, { [ group ]: resource } );
	} catch ( error ) {
		if ( error instanceof Error || isRestApiError( error ) ) {
			return updateErrorForGroup( group, null, error.message );
		}
		throw `Unexpected error ${ error }`;
	}
}

export function* getSettingsForGroup( group: string ) {
	return getSettings( group );
}
