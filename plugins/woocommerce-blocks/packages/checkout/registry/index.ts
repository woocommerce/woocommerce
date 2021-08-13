/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { returnTrue } from '../utils';

type CheckoutFilterFunction = < T >(
	value: T,
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
	/**
	 * Let developers know snackbarNotices is no longer available as a filter.
	 *
	 * See: https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4417
	 */
	if ( Object.keys( filters ).includes( 'couponName' ) ) {
		deprecated( 'snackbarNotices', {
			alternative: 'snackbarNoticeVisibility',
			plugin: 'WooCommerce Blocks',
			link:
				'https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4417',
		} );
	}

	/**
	 * Let the user know couponName is no longer available as a filter.
	 *
	 * See https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4312
	 */
	if ( Object.keys( filters ).includes( 'couponName' ) ) {
		deprecated( 'couponName', {
			alternative: 'coupons',
			plugin: 'WooCommerce Blocks',
			link:
				'https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/bb921d21f42e21f38df2b1c87b48e07aa4cb0538/docs/extensibility/available-filters.md#coupons',
		} );
	}

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
export const __experimentalApplyCheckoutFilter = < T >( {
	filterName,
	defaultValue,
	extensions = null,
	arg = null,
	validation = returnTrue,
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
	return useMemo( () => {
		const filters = getCheckoutFilters( filterName );

		let value = defaultValue;
		filters.forEach( ( filter ) => {
			try {
				const newValue = filter( value, extensions || {}, arg );
				if ( typeof newValue !== typeof value ) {
					throw new Error(
						sprintf(
							/* translators: %1$s is the type of the variable passed to the filter function, %2$s is the type of the value returned by the filter function. */
							__(
								'The type returned by checkout filters must be the same as the type they receive. The function received %1$s but returned %2$s.',
								'woo-gutenberg-products-block'
							),
							typeof value,
							typeof newValue
						)
					);
				}
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
