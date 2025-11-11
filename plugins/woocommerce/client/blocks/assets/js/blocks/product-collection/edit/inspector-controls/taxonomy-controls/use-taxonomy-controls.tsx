/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useTaxonomies } from './index';
import {
	QueryControlProps,
	CoreFilterNames,
	CoreCollectionNames,
} from '../../../types';

/**
 * Shared hook for taxonomy control logic - filters taxonomies based on context and provides common handlers.
 */
function useTaxonomyControls( {
	setQueryAttribute,
	trackInteraction,
	query,
	collection,
	isFiltersPanel,
}: QueryControlProps & { collection: string | undefined } & {
	isFiltersPanel?: boolean;
} ) {
	const { taxQuery } = query;
	const taxonomies = useTaxonomies();

	const filteredTaxonomies = useMemo( () => {
		if ( ! taxonomies || taxonomies.length === 0 ) {
			return [];
		}

		if ( collection === CoreCollectionNames.BY_CATEGORY ) {
			return taxonomies.filter( ( taxonomy ) =>
				// If it's in filter panel, we want to show everything BUT the category control.
				// Otherwise, it's a collection specific filter and we want to show ONLY the category control.
				isFiltersPanel
					? taxonomy.slug !== 'product_cat'
					: taxonomy.slug === 'product_cat'
			);
		}
		if ( collection === CoreCollectionNames.BY_TAG ) {
			return taxonomies.filter( ( taxonomy ) =>
				// If it's in filter panel, we want to show everything BUT the tag control.
				// Otherwise, it's a collection specific filter and we want to show ONLY the tag control.
				isFiltersPanel
					? taxonomy.slug !== 'product_tag'
					: taxonomy.slug === 'product_tag'
			);
		}

		return isFiltersPanel ? taxonomies : [];
	}, [ taxonomies, collection, isFiltersPanel ] );

	const createHandleChange = ( slug: string ) => ( newTermIds: number[] ) => {
		setQueryAttribute( {
			taxQuery: {
				...taxQuery,
				[ slug ]: newTermIds,
			},
		} );
		trackInteraction( `${ CoreFilterNames.TAXONOMY }__${ slug }` );
	};

	const shouldShowTaxonomyControl = filteredTaxonomies.length > 0;

	return {
		filteredTaxonomies,
		taxQuery,
		createHandleChange,
		shouldShowTaxonomyControl,
	};
}

export default useTaxonomyControls;
