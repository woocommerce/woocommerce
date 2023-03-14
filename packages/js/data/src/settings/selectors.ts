/**
 * Internal dependencies
 */
import { getResourceName, getResourcePrefix } from '../utils';
import { SettingsState, Settings } from './types';

export const getSettingsGroupNames = ( state: SettingsState ) => {
	const groupNames = new Set(
		Object.keys( state ).map( ( resourceName ) => {
			return getResourcePrefix( resourceName );
		} )
	);
	return [ ...groupNames ];
};

export const getSettings = ( state: SettingsState, group: string ) => {
	const settings: Settings = {};
	const settingIds = ( state[ group ] && state[ group ].data ) || [];
	if ( ! Array.isArray( settingIds ) || settingIds.length === 0 ) {
		return settings;
	}
	settingIds.forEach( ( id ) => {
		settings[ id ] = state[ getResourceName( group, id ) ].data;
	} );
	return settings;
};

export const getDirtyKeys = ( state: SettingsState, group: string ) => {
	return state[ group ].dirty || [];
};

export const getIsDirty = (
	state: SettingsState,
	group: string,
	keys: string[] = []
) => {
	const dirtyMap = getDirtyKeys( state, group );
	// if empty array bail
	if ( dirtyMap.length === 0 ) {
		return false;
	}
	// if at least one of the keys is in the dirty map then the state is dirty
	// meaning it hasn't been persisted.
	return keys.some( ( key ) => dirtyMap.includes( key ) );
};

export const getSettingsForGroup = (
	state: SettingsState,
	group: string,
	keys: string[]
) => {
	const allSettings = getSettings( state, group );
	return keys.reduce< Settings >( ( accumulator, key ) => {
		accumulator[ key ] = allSettings[ key ] || {};
		return accumulator;
	}, {} );
};

export const isUpdateSettingsRequesting = (
	state: SettingsState,
	group: string
) => {
	return state[ group ] && Boolean( state[ group ].isRequesting );
};

/**
 * Retrieves a setting value from the setting store.
 *
 * @param {Object}   state                   State param added by wp.data.
 * @param {string}   group                   The settings group.
 * @param {string}   name                    The identifier for the setting.
 * @param {*}        [fallback=false]        The value to use as a fallback
 *                                           if the setting is not in the
 *                                           state.
 * @param {Function} [filter=( val ) => val] A callback for filtering the
 *                                           value before it's returned.
 *                                           Receives both the found value
 *                                           (if it exists for the key) and
 *                                           the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */
export function getSetting(
	state: SettingsState,
	group: string,
	name: string,
	fallback = false,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- _fallback in default filter is unused.
	filter = ( val: unknown, _fallback: unknown | boolean ) => val
) {
	const resourceName = getResourceName( group, name );
	const value =
		( state[ resourceName ] && state[ resourceName ].data ) || fallback;
	return filter( value, fallback );
}

export const getLastSettingsErrorForGroup = (
	state: SettingsState,
	group: string
) => {
	const settingsIds = state[ group ].data;
	if ( ! Array.isArray( settingsIds ) || settingsIds.length === 0 ) {
		return state[ group ].error;
	}
	return [ ...settingsIds ].pop().error;
};

export const getSettingsError = (
	state: SettingsState,
	group: string,
	id: string
) => {
	if ( ! id ) {
		return ( state[ group ] && state[ group ].error ) || false;
	}
	return state[ getResourceName( group, id ) ].error || false;
};
