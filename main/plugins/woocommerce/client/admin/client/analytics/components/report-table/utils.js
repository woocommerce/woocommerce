/**
 * External dependencies
 */
import { first } from 'lodash';

/**
 * Recursively collects all `param` keys from a filters/subFilters config tree.
 *
 * @param {Array}       configs Array of filter config objects to traverse.
 * @param {Set<string>} keys    Set to collect param keys into.
 */
function collectFilterParamKeys( configs, keys ) {
	for ( const config of Array.isArray( configs ) ? configs : [] ) {
		if ( ! config || typeof config !== 'object' ) {
			continue;
		}
		if ( config.param ) {
			keys.add( config.param );
		}
		if ( config.settings && config.settings.param ) {
			keys.add( config.settings.param );
		}
		collectFilterParamKeys( config.filters, keys );
		collectFilterParamKeys( config.subFilters, keys );
	}
}

/**
 * Collects all `param` keys declared across a filters config array, including
 * any sub-params defined in nested filter and subFilter settings.
 *
 * @param {Array} filters Report filters config (from props.filters).
 * @return {Set<string>} Set of URL query param keys owned by the filters.
 */
function getFilterParamKeys( filters = [] ) {
	const keys = new Set();
	collectFilterParamKeys( filters, keys );
	return keys;
}

/**
 * Builds the query object to pass to startExport, merging the processed report
 * query with any URL params that belong to a known filter config but are not
 * already present in the report query.
 *
 * `reportQuery` (built by `getReportTableQuery`) only forwards a fixed set of
 * fields (orderby, order, after, before, page, per_page, plus active filter
 * values). Params added by plugins via `applyFilters` on the report's filters
 * config — such as `currency` — are not forwarded because live API requests
 * rely on `$_GET` server-side. Exports run as Action Scheduler background jobs
 * with no HTTP context, so those params must be carried explicitly in the job
 * payload.
 *
 * @param {Object} reportQuery     Processed query from tableData.query.
 * @param {Object} urlQuery        Raw URL query params from props.query.
 * @param {Array}  filters         Report filters config from props.filters.
 * @param {Object} advancedFilters Report advanced filters config from props.advancedFilters.
 * @return {Object} Query object for the export request.
 */
export function getExportQuery(
	reportQuery = {},
	urlQuery = {},
	filters = [],
	advancedFilters = {}
) {
	const safeReportQuery =
		reportQuery && typeof reportQuery === 'object' ? reportQuery : {};
	const safeUrlQuery =
		urlQuery && typeof urlQuery === 'object' ? urlQuery : {};
	const filterParamKeys = getFilterParamKeys( filters );
	const advancedFilterMap =
		advancedFilters && typeof advancedFilters === 'object'
			? advancedFilters.filters || {}
			: {};

	for ( const key of Object.keys( advancedFilterMap ) ) {
		filterParamKeys.add( key );
	}

	const extraParams = Object.fromEntries(
		Object.entries( safeUrlQuery ).filter(
			( [ key, value ] ) =>
				filterParamKeys.has( key ) &&
				! ( key in safeReportQuery ) &&
				value !== undefined &&
				value !== null &&
				value !== ''
		)
	);

	return { ...safeReportQuery, ...extraParams };
}

export function extendTableData(
	extendedStoreSelector,
	props,
	queriedTableData
) {
	const { extendItemsMethodNames, itemIdField } = props;
	const itemsData = queriedTableData.items.data;
	if (
		! Array.isArray( itemsData ) ||
		! itemsData.length ||
		! extendItemsMethodNames ||
		! itemIdField
	) {
		return queriedTableData;
	}

	const {
		[ extendItemsMethodNames.getError ]: getErrorMethod,
		[ extendItemsMethodNames.isRequesting ]: isRequestingMethod,
		[ extendItemsMethodNames.load ]: loadMethod,
	} = extendedStoreSelector;
	const extendQuery = {
		include: itemsData.map( ( item ) => item[ itemIdField ] ).join( ',' ),
		per_page: itemsData.length,
	};
	const extendedItems = loadMethod( extendQuery );
	const isExtendedItemsRequesting = isRequestingMethod
		? isRequestingMethod( extendQuery )
		: false;
	const isExtendedItemsError = getErrorMethod
		? getErrorMethod( extendQuery )
		: false;

	const extendedItemsData = itemsData.map( ( item ) => {
		const extendedItemData = first(
			extendedItems.filter(
				( extendedItem ) => item.id === extendedItem.id
			)
		);
		return {
			...item,
			...extendedItemData,
		};
	} );

	const isRequesting =
		queriedTableData.isRequesting || isExtendedItemsRequesting;
	const isError = queriedTableData.isError || isExtendedItemsError;

	return {
		...queriedTableData,
		isRequesting,
		isError,
		items: {
			...queriedTableData.items,
			data: extendedItemsData,
		},
	};
}
