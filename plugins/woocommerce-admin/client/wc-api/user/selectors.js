/** @format */

/**
 * Internal dependencies
 */
import { DEFAULT_REQUIREMENT } from '../constants';

const getCurrentUserData = ( getResource, requireResource ) => (
	requirement = DEFAULT_REQUIREMENT
) => {
	return requireResource( requirement, 'current-user-data' ).data || {};
};

export default {
	getCurrentUserData,
};
