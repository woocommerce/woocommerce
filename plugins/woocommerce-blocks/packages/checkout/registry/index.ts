/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { returnTrue } from '../';

type CheckoutFilterFunction = < T >(
	label: T,
	extensions: Record< string, unknown >,
	args?: CheckoutFilterArguments
) => T;

type CheckoutFilterArguments =
	| ( Record< string, unknown > & {
			context?: string;
	  } )
	| null;

let checkoutFilters: Record<
	string,
	Record< string, CheckoutFilterFunction >
> = {};

/**
 * Register filters for a specific extension.
 */
export const __experimentalRegisterCheckoutFilters = (
	namespace: string,
	filters: Record< string, CheckoutFilterFunction >
): void => {
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
const getCheckoutFilters = ( filterName: string ): CheckoutFilterFunction[] => {
	const namespaces = Object.keys( checkoutFilters );
	const filters = namespaces
		.map( ( namespace ) => checkoutFilters[ namespace ][ filterName ] )
		.filter( Boolean );
	return filters;
};

/**
 * Apply a filter.
 */
export const __experimentalApplyCheckoutFilter = ( {
	filterName,
	defaultValue,
	extensions,
	arg = null,
	validation = returnTrue,
}: {
	/** Name of the filter to apply. */
	filterName: string;
	/** Default value to filter. */
	defaultValue: unknown;
	/** Values extend to REST API response. */
	extensions: Record< string, unknown >;
	/** Object containing arguments for the filter function. */
	arg: CheckoutFilterArguments;
	/** Function that needs to return true when the filtered value is passed in order for the filter to be applied. */
	validation: ( value: unknown ) => boolean;
} ): unknown => {
	return useMemo( () => {
		const filters = getCheckoutFilters( filterName );

		let value = defaultValue;
		filters.forEach( ( filter ) => {
			try {
				const newValue = filter( value, extensions, arg );
				value = validation( newValue ) ? newValue : value;
			} catch ( e ) {
				if ( CURRENT_USER_IS_ADMIN ) {
					throw e;
				} else {
					// eslint-disable-next-line no-console
					console.error( e );
				}
			}
		} );
		return value;
	}, [ filterName, defaultValue, extensions, arg, validation ] );
};
