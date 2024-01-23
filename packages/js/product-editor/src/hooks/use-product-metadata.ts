// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';
/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Product } from '@woocommerce/data';
/**
 * Internal dependencies
 */
import { Metadata } from '../types';

interface Options {
	postType?: string;
	id?: number;
}

function useProductMetadata( options?: Options ) {
	const postType = options?.postType || 'product';
	const thisId = useEntityId( 'postType', postType );
	const id = options?.id || thisId;

	// @ts-expect-error There are no types for this.
	const { editEntityRecord } = useDispatch( 'core' );

	return useSelect(
		( select ) => {
			// @ts-expect-error There are no types for this.
			const { getEditedEntityRecord } = select( 'core' );
			const { meta_data: metadata }: Product = getEditedEntityRecord(
				'postType',
				postType,
				id
			);

			return {
				metadata: metadata.reduce( function ( acc, cur ) {
					acc[ cur.key ] = cur.value;
					return acc;
				}, {} as Record< string, string | undefined > ),
				updateMetadata: ( entries: Metadata< string >[] ) => {
					editEntityRecord( 'postType', postType, id, {
						meta_data: [
							...metadata.filter(
								( item ) =>
									entries.findIndex(
										( e ) => e.key === item.key
									) === -1
							),
							...entries,
						],
					} );
				},
			};
		},
		[ id ]
	);
}

export default useProductMetadata;
