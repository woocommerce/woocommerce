/* eslint-disable @typescript-eslint/no-explicit-any */

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

if ( ! ( window.wc as any )?.blocksCheckout ) {
	window.wc = window.wc || {};
	( window.wc as any ).blocksCheckout = {};

	let checkoutFilters: Record<
		string,
		Record< string, CheckoutFilterFunction >
	> = {};

	/**
	 * Register filters for a specific extension.
	 */
	( window.wc as any ).blocksCheckout.registerCheckoutFilters =
		( window.wc as any ).blocksCheckout?.registerCheckoutFilters ||
		( (
			namespace: string,
			filters: Record< string, CheckoutFilterFunction >
		): void => {
			checkoutFilters = {
				...checkoutFilters,
				[ namespace ]: filters,
			};
		} );

	/**
	 * Get all filters with a specific name.
	 */
	const getCheckoutFilters = (
		filterName: string
	): CheckoutFilterFunction[] => {
		const namespaces = Object.keys( checkoutFilters );
		const filters = namespaces
			.map( ( namespace ) => checkoutFilters[ namespace ][ filterName ] )
			.filter( Boolean );
		return filters;
	};

	/**
	 * Apply a filter.
	 */
	( window.wc as any ).blocksCheckout.applyCheckoutFilter =
		( window.wc as any ).blocksCheckout?.applyCheckoutFilter ||
		( < T >( {
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
					const newValue = filter(
						value,
						extensions || {},
						arg
					) as T;
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
		} );
}

export const applyCheckoutFilter = ( window.wc as any ).blocksCheckout
	.applyCheckoutFilter;
