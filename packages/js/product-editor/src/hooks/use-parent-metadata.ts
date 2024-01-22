/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

export function useParentMetadata( parentId: number | undefined ) {
	return useSelect(
		( select ) => {
			// @ts-expect-error There are no types for this.
			const { getEditedEntityRecord } = select( 'core' );
			const { meta_data: metadata }: Product = getEditedEntityRecord(
				'postType',
				'product',
				parentId
			);

			return metadata
				? metadata.reduce( function ( acc, cur ) {
						acc[ cur.key ] = cur.value;
						return acc;
				  }, {} as Record< string, string | undefined > )
				: undefined;
		},
		[ parentId ]
	);
}
