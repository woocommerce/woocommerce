/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * In Product Collection block, queryContextIncludes attribute contains
 * list of attribute names that should be included in the query context.
 *
 * This hook returns the query context object based on the attribute names
 * provided in the queryContextIncludes array.
 *
 * Example:
 * {
 * 	clientID = 'd2c7e34f-70d6-417c-b582-f554a3a575f3',
 * 	queryContextIncludes = [ 'collection' ]
 * }
 *
 * The hook will return the following query context object:
 * {
 *  collection: 'woocommerce/product-collection/featured'
 * }
 *
 * @param args                      Arguments for the hook.
 * @param args.clientId             Client ID of the inner block.
 * @param args.queryContextIncludes Array of attribute names to be included in the query context.
 *
 * @return Query context object.
 */
export const useProductCollectionQueryContext = ( {
	clientId,
	queryContextIncludes,
}: {
	clientId: string;
	queryContextIncludes: string[];
} ) => {
	const productCollectionBlockAttributes = useSelect(
		( select ) => {
			const { getBlockParentsByBlockName, getBlockAttributes } =
				select( 'core/block-editor' );

			const parentBlocksClientIds = getBlockParentsByBlockName(
				clientId,
				'woocommerce/product-collection',
				true
			);

			if ( parentBlocksClientIds?.length ) {
				const closestParentClientId = parentBlocksClientIds[ 0 ];
				return getBlockAttributes( closestParentClientId );
			}

			return null;
		},
		[ clientId ]
	);

	return useMemo( () => {
		// If the product collection block is not found, return null.
		if ( ! productCollectionBlockAttributes ) {
			return null;
		}

		/**
		 * Initialize the query context object with collection and id attributes as
		 * they should always be included in the query context.
		 */
		const queryContext: {
			[ key: string ]: unknown;
		} = {
			collection: productCollectionBlockAttributes?.collection,
			id: productCollectionBlockAttributes?.id,
		};

		if ( queryContextIncludes?.length ) {
			queryContextIncludes.forEach( ( attribute: string ) => {
				if ( productCollectionBlockAttributes?.[ attribute ] ) {
					queryContext[ attribute ] =
						productCollectionBlockAttributes[ attribute ];
				}
			} );
		}

		return queryContext;
	}, [ queryContextIncludes, productCollectionBlockAttributes ] );
};
