/**
 * Internal dependencies
 */
import { getResourceName, getResourcePrefix } from '../utils';

export const getSettingsGroupNames = ( state ) => {
	const groupNames = new Set(
		Object.keys( state ).map( ( resourceName ) => {
			return getResourcePrefix( resourceName );
		} )
	);
	return [ ...groupNames ];
};

export const getSettings = ( state, group ) => {
	const settings = {};
	const settingIds = ( state[ group ] && state[ group ].data ) || [];
	if ( settingIds.length === 0 ) {
		return settings;
	}
	settingIds.forEach( ( id ) => {
		settings[ id ] = state[ getResourceName( group, id ) ].data;
	} );
	return settings;
};

export const getDirtyKeys = ( state, group ) => {
	return state[ group ].dirty || [];
};

export const getIsDirty = ( state, group, keys = [] ) => {
	const dirtyMap = getDirtyKeys( state, group );
	// if empty array bail
	if ( dirtyMap.length === 0 ) {
		return false;
	}
	// if at least one of the keys is in the dirty map then the state is dirty
	// meaning it hasn't been persisted.
	return keys.some( ( key ) => dirtyMap.includes( key ) );
};

export const getSettingsForGroup = ( state, group, keys ) => {
	const allSettings = getSettings( state, group );
	return keys.reduce( ( accumulator, key ) => {
		accumulator[ key ] = allSettings[ key ] || {};
		return accumulator;
	}, {} );
};

export const isUpdateSettingsRequesting = ( state, group ) => {
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
	state,
	group,
	name,
	fallback = false,
	filter = ( val ) => val
) {
	const resourceName = getResourceName( group, name );
	const value =
		( state[ resourceName ] && state[ resourceName ].data ) || fallback;
	return filter( value, fallback );
}

export const getLastSettingsErrorForGroup = ( state, group ) => {
	const settingsIds = state[ group ].data;
	if ( settingsIds.length === 0 ) {
		return state[ group ].error;
	}
	return [ ...settingsIds ].pop().error;
};

export const getSettingsError = ( state, group, id ) => {
	if ( ! id ) {
		return ( state[ group ] && state[ group ].error ) || false;
	}
	return state[ getResourceName( group, id ) ].error || false;
};
