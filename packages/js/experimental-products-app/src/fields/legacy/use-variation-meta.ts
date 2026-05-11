/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

type MetaDataEntry = { id?: number; key: string; value: string };

const metaCache: Record< string, MetaDataEntry[] > = {};

function getCacheKey( parentId: number, variationId: number ) {
	return `${ parentId }:${ variationId }`;
}

export function updateVariationMetaCache(
	parentId: number,
	variationId: number,
	meta: Array< { id?: number; key: string; value?: string } >
) {
	metaCache[ getCacheKey( parentId, variationId ) ] = meta.map( ( m ) => ( {
		...m,
		value: m.value ?? '',
	} ) );
}

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
	const [ fetchedData, setFetchedData ] = useState<
		MetaDataEntry[] | undefined
	>( undefined );

	useEffect( () => {
		if ( ! parentId || ! variationId ) {
			setFetchedData( undefined );
			return;
		}

		const cacheKey = getCacheKey( parentId, variationId );
		if ( metaCache[ cacheKey ] ) {
			setFetchedData( metaCache[ cacheKey ] );
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
				metaCache[ cacheKey ] = meta;
				setFetchedData( meta );
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setFetchedData( [] );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ parentId, variationId ] );

	if ( parentId && variationId ) {
		const cached = metaCache[ getCacheKey( parentId, variationId ) ];
		if ( cached ) {
			return cached;
		}
	}

	return fetchedData;
}
