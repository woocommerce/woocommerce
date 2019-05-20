/** @format */

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { DEFAULT_REQUIREMENT } from '../constants';

const getImportTotals = ( getResource, requireResource ) => (
	query = {},
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'import-totals', query );
	return requireResource( requirement, resourceName ) || { customers: null, orders: null };
};

export default {
	getImportTotals,
};
