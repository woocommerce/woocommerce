/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useCallback } from 'react';

export function useProductURL( productType: string ) {
	const [ permalink ] = useEntityProp< string >(
		'postType',
		productType,
		'permalink'
	);
	const getProductURL = useCallback(
		( isPreview: boolean ) => {
			const productURL = new URL( permalink ) as URL | undefined;
			if ( isPreview ) {
				productURL?.searchParams.append( 'preview', 'true' );
			}
			return productURL?.toString() || '';
		},
		[ permalink ]
	);
	return { getProductURL };
}
