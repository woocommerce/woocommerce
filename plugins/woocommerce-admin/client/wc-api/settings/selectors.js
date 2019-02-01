/** @format */

/**
 * Internal dependencies
 */
import { DEFAULT_REQUIREMENT } from '../constants';

const getSettings = ( getResource, requireResource ) => ( requirement = DEFAULT_REQUIREMENT ) => {
	return requireResource( requirement, 'settings' ).data;
};

export default {
	getSettings,
};
