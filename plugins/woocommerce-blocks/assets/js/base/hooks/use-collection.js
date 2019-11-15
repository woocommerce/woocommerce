/**
 * External dependencies
 */
import { COLLECTIONS_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useShallowEqual } from './use-shallow-equal';

/**
 * This is a custom hook that is wired up to the `wc/store/collections` data
 * store. Given a collections option object, this will ensure a component is
 * kept up to date with the collection matching that query in the store state.
 *
 * @param {Object} options                An object declaring the various
 *                                        collection arguments.
 * @param {string} options.namespace      The namespace for the collection.
 *                                        Example: `'/wc/blocks'`
 * @param {string} options.resourceName   The name of the resource for the
 *                                        collection. Example:
 *                                        `'products/attributes'`
 * @param {Array}  options.resourceValues An array of values (in correct order)
 *                                        that are substituted in the route
 *                                        placeholders for the collection route.
 *                                        Example: `[10, 20]`
 * @param {Object} options.query          An object of key value pairs for the
 *                                        query to execute on the collection
 *                                        (optional). Example:
 *                                        `{ order: 'ASC', order_by: 'price' }`
 *
 * @return {Object} This hook will return an object with two properties:
 *                  - results   An array of collection items returned.
 *                  - isLoading A boolean indicating whether the collection is
 *                              loading (true) or not.
 */
export const useCollection = ( options ) => {
	const {
		namespace,
		resourceName,
		resourceValues = [],
		query = {},
	} = options;
	if ( ! namespace || ! resourceName ) {
		throw new Error(
			'The options object must have valid values for the namespace and ' +
				'the resource properties.'
		);
	}
	// ensure we feed the previous reference if it's equivalent
	const currentQuery = useShallowEqual( query );
	const currentResourceValues = useShallowEqual( resourceValues );
	const { results = [], isLoading = true } = useSelect(
		( select ) => {
			const store = select( storeKey );
			// filter out query if it is undefined.
			const args = [
				namespace,
				resourceName,
				currentQuery,
				currentResourceValues,
			];
			return {
				results: store.getCollection( ...args ),
				isLoading: ! store.hasFinishedResolution(
					'getCollection',
					args
				),
			};
		},
		[ namespace, resourceName, currentResourceValues, currentQuery ]
	);
	return {
		results,
		isLoading,
	};
};
