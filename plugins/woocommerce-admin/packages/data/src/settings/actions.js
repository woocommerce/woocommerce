/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { apiFetch, select } from '@wordpress/data-controls';
import { concat } from 'lodash';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { STORE_NAME } from './constants';
import TYPES from './action-types';

export function updateSettingsForGroup( group, data, time = new Date() ) {
	return {
		type: TYPES.UPDATE_SETTINGS_FOR_GROUP,
		group,
		data,
		time,
	};
}

export function updateErrorForGroup( group, data, error, time = new Date() ) {
	return {
		type: TYPES.UPDATE_ERROR_FOR_GROUP,
		group,
		data,
		error,
		time,
	};
}

export function setIsRequesting( group, isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		group,
		isRequesting,
	};
}

export function clearIsDirty( group ) {
	return {
		type: TYPES.CLEAR_IS_DIRTY,
		group,
	};
}

// allows updating and persisting immediately in one action.
export function* updateAndPersistSettingsForGroup( group, data ) {
	yield updateSettingsForGroup( group, data );
	yield* persistSettingsForGroup( group );
}

// this would replace setSettingsForGroup
export function* persistSettingsForGroup( group ) {
	// first dispatch the is persisting action
	yield setIsRequesting( group, true );
	// get all dirty keys with select control
	const dirtyKeys = yield select( STORE_NAME, 'getDirtyKeys', group );
	// if there is nothing dirty, bail
	if ( dirtyKeys.length === 0 ) {
		yield setIsRequesting( group, false );
		return;
	}

	// get data slice for keys
	const dirtyData = yield select(
		STORE_NAME,
		'getSettingsForGroup',
		group,
		dirtyKeys
	);
	const url = `${ NAMESPACE }/settings/${ group }/batch`;

	const update = dirtyKeys.reduce( ( updates, key ) => {
		const u = Object.keys( dirtyData[ key ] ).map( ( k ) => {
			return { id: k, value: dirtyData[ key ][ k ] };
		} );
		return concat( updates, u );
	}, [] );
	try {
		const results = yield apiFetch( {
			path: url,
			method: 'POST',
			data: { update },
		} );

		yield setIsRequesting( group, false );

		if ( ! results ) {
			throw new Error(
				__(
					'There was a problem updating your settings.',
					'woocommerce-admin'
				)
			);
		}

		// remove dirtyKeys from map - note we're only doing this if there is no error.
		yield clearIsDirty( group );
	} catch ( e ) {
		yield updateErrorForGroup( group, null, e );
		yield setIsRequesting( group, false );
		throw e;
	}
}

export function clearSettings() {
	return {
		type: TYPES.CLEAR_SETTINGS,
	};
}
