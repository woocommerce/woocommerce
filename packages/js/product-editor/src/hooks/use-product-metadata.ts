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

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const { editEntityRecord } = useDispatch( 'core' );

	const { isLoading, meta_data } = useSelect(
		( select ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			const { getEditedEntityRecord, hasFinishedResolution } =
				select( 'core' );
			const { meta_data: metadata }: Product = getEditedEntityRecord(
				'postType',
				postType,
				id
			);
			const isResolutionFinished = hasFinishedResolution(
				'getEditedEntityRecord',
				[ 'postType', postType, id ]
			);

			return {
				meta_data: metadata || [],
				isLoading: ! isResolutionFinished,
			};
		},
		[ id ]
	);

	return {
		metadata: meta_data.reduce( function ( acc, cur ) {
			acc[ cur.key ] = cur.value;
			return acc;
		}, {} as Record< string, string | undefined > ),
		update: ( entries: Metadata< string >[] ) =>
			editEntityRecord( 'postType', postType, id, {
				meta_data: [
					...meta_data.filter(
						( item ) =>
							entries.findIndex( ( e ) => e.key === item.key ) ===
							-1
					),
					...entries,
				],
			} ),
		isLoading,
	};
}

export default useProductMetadata;
