/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useRef, useState } from '@wordpress/element';

type MetaDataEntry = { id?: number; key: string; value: string };

/**
 * Fetch meta_data for a variation from the WC REST API.
 *
 * The V4 entity store strips meta_data from embedded variations for
 * performance. This hook fetches it separately so legacy fields can
 * read and write meta values.
 */
export function useVariationMeta(
	parentId: number | undefined,
	variationId: number | undefined
): MetaDataEntry[] | undefined {
	const [ metaData, setMetaData ] = useState< MetaDataEntry[] | undefined >(
		undefined
	);
	const cacheRef = useRef<
		Record< string, MetaDataEntry[] >
	>( {} );

	useEffect( () => {
		if ( ! parentId || ! variationId ) {
			setMetaData( undefined );
			return;
		}

		const cacheKey = `${ parentId }:${ variationId }`;
		if ( cacheRef.current[ cacheKey ] ) {
			setMetaData( cacheRef.current[ cacheKey ] );
			return;
		}

		let cancelled = false;

		apiFetch< { meta_data: MetaDataEntry[] } >( {
			path: `/wc/v3/products/${ parentId }/variations/${ variationId }?_fields=meta_data`,
		} )
			.then( ( response ) => {
				if ( cancelled ) {
					return;
				}

				const meta = response.meta_data ?? [];
				cacheRef.current[ cacheKey ] = meta;
				setMetaData( meta );
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setMetaData( [] );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ parentId, variationId ] );

	return metaData;
}
