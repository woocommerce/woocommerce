/** @format */

/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * Internal dependencies
 */
import { DEFAULT_REQUIREMENT } from '../constants';
import { getResourceName } from '../utils';

const getSettings = ( getResource, requireResource ) => (
	group,
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = `settings/${ group }`;
	const ids = requireResource( requirement, resourceName ).data || [];
	const settings = {};

	ids.forEach( id => {
		settings[ id ] = getResource( getResourceName( resourceName, id ) ).data;
	} );

	return settings;
};

const getSettingsError = getResource => group => {
	return getResource( `settings/${ group }` ).error;
};

const isGetSettingsRequesting = getResource => group => {
	const { lastReceived, lastRequested } = getResource( `settings/${ group }` );
	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getSettings,
	getSettingsError,
	isGetSettingsRequesting,
};
