/**
 * External dependencies
 */
import { Taxonomy } from '@wordpress/core-data/src/entity-types';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import TaxonomyItem from './taxonomy-item';
import {
	QueryControlProps,
	CoreFilterNames,
	CoreCollectionNames,
} from '../../../types';

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

/**
 * Normalize the name so first letter of every word is capitalized.
 */
const normalizeName = ( name: string | undefined | null ) => {
	if ( ! name ) {
		return '';
	}

	return name
		.split( ' ' )
		.map( ( word ) => word.charAt( 0 ).toUpperCase() + word.slice( 1 ) )
		.join( ' ' );
};

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
				isFiltersPanel
					? taxonomy.slug !== 'product_cat'
					: taxonomy.slug === 'product_cat'
			);
		}
		if ( collection === CoreCollectionNames.BY_TAG ) {
			return taxonomies.filter( ( taxonomy ) =>
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

	const createDeselectCallback = ( slug: string ) => () => {
		createHandleChange( slug )( [] );
	};

	const shouldShowTaxonomyControl = filteredTaxonomies.length > 0;

	return {
		filteredTaxonomies,
		taxQuery,
		createHandleChange,
		createDeselectCallback,
		shouldShowTaxonomyControl,
	};
}

function TaxonomyControls( {
	setQueryAttribute,
	trackInteraction,
	query,
	collection,
}: QueryControlProps & { collection: string | undefined } ) {
	const {
		filteredTaxonomies,
		taxQuery,
		createHandleChange,
		createDeselectCallback,
		shouldShowTaxonomyControl,
	} = useTaxonomyControls( {
		query,
		collection,
		setQueryAttribute,
		trackInteraction,
		isFiltersPanel: true,
	} );

	if ( ! shouldShowTaxonomyControl ) {
		return null;
	}

	return (
		<>
			{ filteredTaxonomies.map( ( taxonomy: Taxonomy ) => {
				const { slug, name } = taxonomy;
				const termIds = taxQuery?.[ slug ] || [];
				const handleChange = createHandleChange( slug );
				const deselectCallback = createDeselectCallback( slug );

				return (
					<ToolsPanelItem
						key={ slug }
						label={ normalizeName( name ) }
						hasValue={ () => termIds.length > 0 }
						onDeselect={ deselectCallback }
						resetAllFilter={ deselectCallback }
					>
						<TaxonomyItem
							taxonomy={ taxonomy }
							termIds={ termIds }
							onChange={ handleChange }
						/>
					</ToolsPanelItem>
				);
			} ) }
		</>
	);
}

export function TaxonomyControlsField( {
	setQueryAttribute,
	trackInteraction,
	query,
	collection,
}: QueryControlProps & { collection: string | undefined } ) {
	const {
		filteredTaxonomies,
		taxQuery,
		createHandleChange,
		shouldShowTaxonomyControl,
	} = useTaxonomyControls( {
		query,
		collection,
		setQueryAttribute,
		trackInteraction,
		isFiltersPanel: false,
	} );

	if ( ! shouldShowTaxonomyControl ) {
		return null;
	}

	return (
		<>
			{ filteredTaxonomies.map( ( taxonomy: Taxonomy ) => {
				const { slug } = taxonomy;
				const termIds = taxQuery?.[ slug ] || [];
				const handleChange = createHandleChange( slug );

				return (
					<TaxonomyItem
						key={ slug }
						taxonomy={ taxonomy }
						termIds={ termIds }
						onChange={ handleChange }
					/>
				);
			} ) }
		</>
	);
}

export default TaxonomyControls;
