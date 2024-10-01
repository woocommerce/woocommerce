/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useCallback } from '@wordpress/element';

export function useProductURL( productType: string ) {
	const [ permalink ] = useEntityProp< string >(
		'postType',
		productType,
		'permalink'
	);

	const getProductURL = useCallback(
		( isPreview: boolean ) => {
			if ( ! permalink ) return undefined;

			const productURL = new URL( permalink );
			if ( isPreview ) {
				productURL.searchParams.append( 'preview', 'true' );
			}
			return productURL.toString();
		},
		[ permalink ]
	);

	return { getProductURL };
}
