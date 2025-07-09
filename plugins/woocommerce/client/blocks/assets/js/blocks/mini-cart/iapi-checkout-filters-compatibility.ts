/**
 * External dependencies
 */
import type { ComparableObject } from '@wordpress/is-shallow-equal';

const returnTrue = (): true => true;

const isObject = < T extends Record< string, unknown >, U >(
	term: T | U
): term is NonNullable< T > => {
	return (
		term !== null && term instanceof Object && term.constructor === Object
	);
};

const isShallowEqual = ( a: unknown, b: unknown ) => {
	if ( a && b ) {
		if ( isObject( a ) && isObject( b ) ) {
			if ( a === b ) {
				return true;
			}

			const aKeys = Object.keys( a );
			const bKeys = Object.keys( b );

			if ( aKeys.length !== bKeys.length ) {
				return false;
			}

			let i = 0;

			while ( i < aKeys.length ) {
				const key = aKeys[ i ];
				const aValue = a[ key ];

				if (
					( aValue === undefined && ! b.hasOwnProperty( key ) ) ||
					aValue !== b[ key ]
				) {
					return false;
				}

				i++;
			}

			return true;
		} else if ( Array.isArray( a ) && Array.isArray( b ) ) {
			if ( a === b ) {
				return true;
			}

			if ( a.length !== b.length ) {
				return false;
			}

			for ( let i = 0, len = a.length; i < len; i++ ) {
				if ( a[ i ] !== b[ i ] ) {
					return false;
				}
			}

			return true;
		}
	}
	return a === b;
};

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

let cachedValues: Record< string, unknown > = {};

/**
 * Register filters for a specific extension.
 */
export const registerCheckoutFilters = (
	namespace: string,
	filters: Record< string, CheckoutFilterFunction >
): void => {
	// Clear cached values when registering new filters because otherwise we get outdated results when applying them.
	cachedValues = {};
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

const cachedFilterRuns: Record<
	string,
	{
		arg?: CheckoutFilterArguments;
		extensions?: Record< string, unknown > | null;
		defaultValue: unknown;
	} & Record< string, unknown >
> = {};

const updatePreviousFilterRun = < T >(
	filterName: string,
	arg: CheckoutFilterArguments,
	extensions: Record< string, unknown > | null,
	defaultValue: T
): void => {
	cachedFilterRuns[ filterName ] = {
		arg,
		extensions,
		defaultValue,
	};
};

/**
 * A function that checks the shallow equality of an object's members.
 */
const checkMembersShallowEqual = <
	T extends Record< string, unknown > | null,
	U extends Record< string, unknown > | null
>(
	a: T,
	b: U
) => {
	// For the case when extensions is null across runs.
	if ( a === null && b === null ) {
		return true;
	}

	return (
		isObject( a ) &&
		isObject( b ) &&
		Object.keys( a ).length === Object.keys( b ).length &&
		Object.keys( a ).every( ( aKey ) => {
			return (
				isObject( b ) &&
				aKey in b &&
				isShallowEqual(
					a[ aKey ] as ComparableObject,
					b[ aKey ] as ComparableObject
				)
			);
		} )
	);
};

/**
 * A function that checks the arg and extensions that were passed the last time a specific filter ran.
 * If they are shallowly equal, then return the cached value and prevent third party code running. If they are
 * different then the third party filters are run and the result is cached.
 */
const shouldReRunFilters = < T >(
	filterName: string,
	arg: CheckoutFilterArguments,
	extensions: Record< string, unknown > | null,
	defaultValue: T
): boolean => {
	const previousFilterRun = cachedFilterRuns[ filterName ];

	if ( ! previousFilterRun ) {
		// This is the first time the filter is running so let it continue;
		updatePreviousFilterRun( filterName, arg, extensions, defaultValue );
		return true;
	}
	const {
		arg: previousArg = {} as Record< string, unknown >,
		extensions: previousExtensions = {} as Record< string, unknown >,
		defaultValue: previousDefaultValue = null,
	} = previousFilterRun;

	// Check length of arg and previousArg, and that all keys are present in both arg and previousArg
	const argIsEqual = checkMembersShallowEqual( arg, previousArg );
	if ( ! argIsEqual ) {
		updatePreviousFilterRun( filterName, arg, extensions, defaultValue );
		return true;
	}

	// Check length of arg and previousArg, and that all keys are present in both arg and previousArg
	const defaultValueIsEqual = defaultValue === previousDefaultValue;
	if ( ! defaultValueIsEqual ) {
		updatePreviousFilterRun( filterName, arg, extensions, defaultValue );
		return true;
	}

	const extensionsIsEqual = checkMembersShallowEqual(
		extensions,
		previousExtensions
	);
	if ( ! extensionsIsEqual ) {
		updatePreviousFilterRun( filterName, arg, extensions, defaultValue );
		return true;
	}
	return false;
};

/**
 * Apply a filter.
 */
export const applyCheckoutFilter = < T >( {
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
	if (
		! shouldReRunFilters( filterName, arg, extensions, defaultValue ) &&
		cachedValues[ filterName ] !== undefined
	) {
		return cachedValues[ filterName ] as T;
	}
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
	cachedValues[ filterName ] = value;
	return value;
};
