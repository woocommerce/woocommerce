/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { Product } from '@woocommerce/data';
/**
 * Internal dependencies
 */
import { Metadata } from '../types';

function useProductMetadata(
	postType?: string,
	id?: number,
	parentPostType?: string
) {
	const [ thisMetadata, setMetadata ] = useEntityProp< Metadata< string >[] >(
		'postType',
		postType || 'product',
		'meta_data'
	);

	const metadata = useSelect(
		( select ) => {
			let usedMetadata: Metadata< string >[];
			if ( id ) {
				// @ts-expect-error There are no types for this.
				const { getEditedEntityRecord } = select( 'core' );
				const { meta_data: parentMetadata }: Product =
					getEditedEntityRecord( 'postType', parentPostType, id );
				usedMetadata = parentMetadata;
			} else {
				usedMetadata = thisMetadata;
			}

			return usedMetadata
				? usedMetadata.reduce( function ( acc, cur ) {
						acc[ cur.key ] = cur.value;
						return acc;
				  }, {} as Record< string, string | undefined > )
				: undefined;
		},
		[ id ]
	);

	return {
		updateMetadata: ( entries: Metadata< string >[] ) => {
			setMetadata( [
				...thisMetadata.filter(
					( item ) =>
						entries.findIndex( ( e ) => e.key === item.key ) === -1
				),
				...entries,
			] );
		},
		metadata,
	};
}

export default useProductMetadata;
