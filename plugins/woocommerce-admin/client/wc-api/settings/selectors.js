/** @format */

/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * Internal dependencies
 */
import { DEFAULT_REQUIREMENT } from '../constants';

const getSettings = ( getResource, requireResource ) => ( requirement = DEFAULT_REQUIREMENT ) => {
	return requireResource( requirement, 'settings' ).data || {};
};

const getSettingsError = getResource => () => {
	return getResource( 'settings' ).error;
};

const isGetSettingsRequesting = getResource => () => {
	const { lastRequested, lastReceived } = getResource( 'settings' );
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
