type CheckoutFilterFunction< U = unknown > = < T >(
	value: T | U,
	extensions: Record< string, unknown >,
	args?: CheckoutFilterArguments
) => T | U;

type CheckoutFilterArguments =
	| ( Record< string, unknown > & {
			context?: string;
	  } )
	| null;

declare global {
	interface Window {
		wc: {
			_internalBlocksCheckoutFilters: Record<
				string,
				Record< string, CheckoutFilterFunction >
			>;
		};
	}
}

window.wc = window.wc || {};
window.wc._internalBlocksCheckoutFilters =
	window.wc._internalBlocksCheckoutFilters || {};

/**
 * Register filters for a specific extension.
 */
export const registerCheckoutFilters = (
	namespace: string,
	filters: Record< string, CheckoutFilterFunction >
): void => {
	window.wc._internalBlocksCheckoutFilters = {
		...window.wc._internalBlocksCheckoutFilters,
		[ namespace ]: filters,
	};
};

/**
 * Get all filters with a specific name.
 *
 * @param {string} filterName Name of the filter to search for.
 * @return {Function[]} Array of functions that are registered for that filter
 *                      name.
 */
const getCheckoutFilters = ( filterName: string ): CheckoutFilterFunction[] => {
	const namespaces = Object.keys( window.wc._internalBlocksCheckoutFilters );
	const filters = namespaces
		.map(
			( namespace ) =>
				window.wc._internalBlocksCheckoutFilters[ namespace ][
					filterName
				]
		)
		.filter( Boolean );
	return filters;
};

/**
 * Apply a filter.
 */
export const applyCheckoutFilter = < T >( {
	filterName,
	defaultValue,
	extensions = null,
	arg = null,
	validation = () => true,
}: {
	/** Name of the filter to apply. */
	filterName: string;
	/** Default value to filter. */
	defaultValue: T;
	/** Values extend to REST API response. */
	extensions?: Record< string, unknown > | null;
	/** Object containing arguments for the filter function. */
	arg?: CheckoutFilterArguments;
	/** Function that needs to return true when the filtered value is passed in order for the filter to be applied. */
	validation?: ( value: T ) => true | Error;
} ): T => {
	const filters = getCheckoutFilters( filterName );
	let value = defaultValue;
	filters.forEach( ( filter ) => {
		try {
			const newValue = filter( value, extensions || {}, arg ) as T;
			if ( typeof newValue !== typeof value ) {
				throw new Error(
					`The type returned by checkout filters must be the same as the type they receive. The function received ${ typeof value } but returned ${ typeof newValue }.`
				);
			}
			value = validation( newValue ) ? newValue : value;
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( e );
		}
	} );
	return value;
};
