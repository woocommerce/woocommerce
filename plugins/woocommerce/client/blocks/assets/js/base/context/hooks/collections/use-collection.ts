/**
 * External dependencies
 */
import { COLLECTIONS_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { useShallowEqual, useThrowError } from '@woocommerce/base-hooks';
import { isError } from '@woocommerce/types';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Wrap a non-Error value from the store into a real Error while preserving
 * the original metadata (name, code, status, statusText, data). When the raw
 * value has no usable message, build a contextual fallback that names the
 * collection being loaded so the BlockErrorBoundary renders something
 * actionable instead of "something went wrong".
 */
function wrapNonError(
	error: unknown,
	namespace: string,
	resourceName: string
): Error {
	const source =
		typeof error === 'object' && error !== null
			? ( error as {
					message?: unknown;
					name?: unknown;
					code?: unknown;
					status?: unknown;
					statusText?: unknown;
					data?: unknown;
			  } )
			: {};
	const trimmed =
		typeof source.message === 'string' ? source.message.trim() : '';
	const codeSuffix =
		typeof source.code === 'string' || typeof source.code === 'number'
			? ` (code: ${ source.code })`
			: '';
	const fallback =
		sprintf(
			// translators: %1$s: store namespace, %2$s: resource name.
			__( 'Failed to load %2$s from %1$s', 'woocommerce' ),
			namespace,
			resourceName
		) + codeSuffix;
	const wrapped = Object.assign(
		new Error( trimmed || fallback ),
		{
			code: source.code,
			status: source.status,
			statusText: source.statusText,
			data: source.data,
		},
		typeof source.name === 'string' ? { name: source.name } : {}
	);
	return wrapped;
}

/**
 * This is a custom hook that is wired up to the `wc/store/collections` data
 * store. Given a collections option object, this will ensure a component is
 * kept up to date with the collection matching that query in the store state.
 *
 * @throws {Object} Throws an exception object if there was a problem with the
 * 					API request, to be picked up by BlockErrorBoundary.
 *
 * @param {Object}  options                  An object declaring the various
 *                                           collection arguments.
 * @param {string}  options.namespace        The namespace for the collection.
 *                                           Example: `'/wc/blocks'`
 * @param {string}  options.resourceName     The name of the resource for the
 *                                           collection. Example:
 *                                           `'products/attributes'`
 * @param {Array}   [options.resourceValues] An array of values (in correct order)
 *                                           that are substituted in the route
 *                                           placeholders for the collection route.
 *                                           Example: `[10, 20]`
 * @param {Object}  [options.query]          An object of key value pairs for the
 *                                           query to execute on the collection
 *                                           Example:
 *                                           `{ order: 'ASC', order_by: 'price' }`
 * @param {boolean} [options.shouldSelect]   If false, the previous results will be
 *                                           returned and internal selects will not
 *                                           fire.
 *
 * @return {Object} This hook will return an object with two properties:
 *                  - results   An array of collection items returned.
 *                  - isLoading A boolean indicating whether the collection is
 *                              loading (true) or not.
 */

export interface useCollectionOptions {
	namespace: string;
	resourceName: string;
	resourceValues?: number[];
	query?: Record< string, unknown >;
	shouldSelect?: boolean;
	isEditor?: boolean;
}

export const useCollection = < T >(
	options: useCollectionOptions
): {
	results: T[];
	isLoading: boolean;
} => {
	const {
		namespace,
		resourceName,
		resourceValues = [],
		query = {},
		shouldSelect = true,
	} = options;
	if ( ! namespace || ! resourceName ) {
		throw new Error(
			'The options object must have valid values for the namespace and ' +
				'the resource properties.'
		);
	}
	const currentResults = useRef< { results: T[]; isLoading: boolean } >( {
		results: [],
		isLoading: true,
	} );
	// Tracks the last raw non-Error value we've logged. wp-data's useSelect
	// mapSelect callback can be invoked multiple times per render (notably
	// via SCRIPT_DEBUG's unstable-reference double-invoke check), so guarding
	// on reference identity prevents duplicate console output for a single
	// underlying error.
	const lastLoggedError = useRef< unknown >();
	// ensure we feed the previous reference if it's equivalent
	const currentQuery = useShallowEqual( query );
	const currentResourceValues = useShallowEqual( resourceValues );
	const throwError = useThrowError();
	const results = useSelect(
		( select ) => {
			if ( ! shouldSelect ) {
				return null;
			}

			const store = select( storeKey );
			const args = [
				namespace,
				resourceName,
				currentQuery,
				currentResourceValues,
			];
			const error = store.getCollectionError( ...args );

			if ( error ) {
				if ( isError( error ) ) {
					throwError( error );
				} else {
					if ( lastLoggedError.current !== error ) {
						lastLoggedError.current = error;
						// eslint-disable-next-line no-console
						console.error(
							'useCollection received a non-Error value from the store:',
							error
						);
					}
					throwError(
						wrapNonError( error, namespace, resourceName )
					);
				}
			}

			return {
				results: store.getCollection< T[] >( ...args ),
				isLoading: ! store.hasFinishedResolution(
					'getCollection',
					args
				),
			};
		},
		[
			namespace,
			resourceName,
			currentResourceValues,
			currentQuery,
			shouldSelect,
			throwError,
		]
	);
	// if selector was not bailed, then update current results. Otherwise return
	// previous results
	if ( results !== null ) {
		currentResults.current = results;
	}
	return currentResults.current;
};
