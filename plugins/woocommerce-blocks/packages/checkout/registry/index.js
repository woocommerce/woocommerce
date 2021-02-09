let checkoutFilters = {};

/**
 * Register filters for a specific extension.
 *
 * @param {string} namespace Name of the extension namespace.
 * @param {Object} filters   Object of filters for that namespace. Each key of
 *                           the object is the name of a filter.
 */
export const __experimentalRegisterCheckoutFilters = ( namespace, filters ) => {
	checkoutFilters = {
		...checkoutFilters,
		[ namespace ]: filters,
	};
};

/**
 * Get all filters with a specific name.
 *
 * @param {string} filterName   Name of the filter to search for.
 * @return {Function[]} Array of functions that are registered for that filter
 *                      name.
 */
const getCheckoutFilters = ( filterName ) => {
	const namespaces = Object.keys( checkoutFilters );
	const filters = namespaces
		.map( ( namespace ) => checkoutFilters[ namespace ][ filterName ] )
		.filter( Boolean );
	return filters;
};

/**
 * Apply a filter.
 *
 * @param {Object}   o              Object of arguments.
 * @param {string}   o.filterName   Name of the filter to apply.
 * @param {any}      o.defaultValue Default value to filter.
 * @param {any}      [o.arg]        Argument to pass to registered functions. If
 *                                  several arguments need to be passed, use an
 *                                  object.
 * @param {Function} [o.validation] Function that needs to return true when the
 *                                  filtered value is passed in order for the
 *                                  filter to be applied.
 * @return {any} Filtered value.
 */
export const __experimentalApplyCheckoutFilter = ( {
	filterName,
	defaultValue,
	arg = null,
	validation = () => true,
} ) => {
	const filters = getCheckoutFilters( filterName );
	let value = defaultValue;
	filters.forEach( ( filter ) => {
		try {
			const newValue = filter( value, arg );
			value = validation( newValue ) ? newValue : value;
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.log( e );
		}
	} );
	return value;
};
