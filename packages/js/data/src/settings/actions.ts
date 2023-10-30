/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';
import { concat } from 'lodash';
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { STORE_NAME } from './constants';
import TYPES from './action-types';
import { Settings } from './types';

// Can be removed in WP 5.9, wp.data is supported in >5.7.
const resolveSelect =
	controls && controls.resolveSelect ? controls.resolveSelect : select;

export function updateSettingsForGroup(
	group: string,
	data: Settings,
	time = new Date()
) {
	return {
		type: TYPES.UPDATE_SETTINGS_FOR_GROUP,
		group,
		data,
		time,
	};
}

export function updateErrorForGroup(
	group: string,
	data: Settings | null,
	error: unknown,
	time = new Date()
) {
	return {
		type: TYPES.UPDATE_ERROR_FOR_GROUP,
		group,
		data,
		error,
		time,
	};
}

export function setIsRequesting( group: string, isRequesting: boolean ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		group,
		isRequesting,
	};
}

export function clearIsDirty( group: string ) {
	return {
		type: TYPES.CLEAR_IS_DIRTY,
		group,
	};
}

// this would replace setSettingsForGroup
export function* persistSettingsForGroup( group: string ) {
	// first dispatch the is persisting action
	yield setIsRequesting( group, true );
	// get all dirty keys with select control
	const dirtyKeys: string[] = yield resolveSelect(
		STORE_NAME,
		'getDirtyKeys',
		group
	);
	// if there is nothing dirty, bail
	if ( dirtyKeys.length === 0 ) {
		yield setIsRequesting( group, false );
		return;
	}

	// get data slice for keys
	const dirtyData: {
		[ key: string ]: Record< string, unknown >;
	} = yield resolveSelect(
		STORE_NAME,
		'getSettingsForGroup',
		group,
		dirtyKeys
	);
	const url = `${ NAMESPACE }/settings/${ group }/batch`;
	const update = dirtyKeys.reduce< Array< { id: string; value: unknown } > >(
		( updates, key ) => {
			const u = Object.keys( dirtyData[ key ] ).map( ( k ) => {
				return { id: k, value: dirtyData[ key ][ k ] };
			} );

			return concat( updates, u );
		},
		[]
	);
	try {
		const results: unknown = yield apiFetch( {
			path: url,
			method: 'POST',
			data: { update },
		} );

		yield setIsRequesting( group, false );

		if ( ! results ) {
			throw new Error(
				__(
					'There was a problem updating your settings.',
					'woocommerce'
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

// allows updating and persisting immediately in one action.
export function* updateAndPersistSettingsForGroup(
	group: string,
	data: Settings
) {
	// Preemptively set requesting to allow for loading UI when optimistically updating settings.
	yield setIsRequesting( group, true );
	yield updateSettingsForGroup( group, data );
	yield* persistSettingsForGroup( group );
}

export function clearSettings() {
	return {
		type: TYPES.CLEAR_SETTINGS,
	};
}

export type Actions = ReturnType<
	| typeof updateSettingsForGroup
	| typeof updateErrorForGroup
	| typeof setIsRequesting
	| typeof clearIsDirty
	| typeof clearSettings
>;

export type ActionDispatchers = DispatchFromMap< {
	createProduct: typeof persistSettingsForGroup;
	updateProduct: typeof updateAndPersistSettingsForGroup;
} >;
