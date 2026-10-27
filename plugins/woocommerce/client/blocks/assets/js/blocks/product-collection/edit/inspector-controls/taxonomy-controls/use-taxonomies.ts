/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import type { Taxonomy } from '@wordpress/core-data';

/**
 * Hook that returns the taxonomies associated with product post type.
 */
export const useTaxonomies = (): Taxonomy[] => {
	const taxonomies = useSelect( ( select ) => {
		const { getTaxonomies } = select( coreStore );
		const filteredTaxonomies: Taxonomy[] = getTaxonomies( {
			type: 'product',
			per_page: -1,
		} );
		return filteredTaxonomies;
	}, [] );
	return useMemo( () => {
		return taxonomies?.filter(
			( { visibility } ) => !! visibility?.publicly_queryable
		);
	}, [ taxonomies ] );
};
