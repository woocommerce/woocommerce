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
import { QueryControlProps, CoreFilterNames } from '../../../types';

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

function TaxonomyControls( {
	setQueryAttribute,
	trackInteraction,
	query,
}: QueryControlProps ) {
	const { taxQuery } = query;

	const taxonomies = useTaxonomies();
	if ( ! taxonomies || taxonomies.length === 0 ) {
		return null;
	}

	return (
		<>
			{ taxonomies.map( ( taxonomy: Taxonomy ) => {
				const { slug, name } = taxonomy;
				const termIds = taxQuery?.[ slug ] || [];
				const handleChange = ( newTermIds: number[] ) => {
					setQueryAttribute( {
						taxQuery: {
							...taxQuery,
							[ slug ]: newTermIds,
						},
					} );
					trackInteraction(
						`${ CoreFilterNames.TAXONOMY }__${ slug }`
					);
				};

				const deselectCallback = () => {
					handleChange( [] );
					trackInteraction(
						`${ CoreFilterNames.TAXONOMY }__${ slug }`
					);
				};

				return (
					<ToolsPanelItem
						key={ slug }
						label={ name }
						hasValue={ () => termIds.length }
						onDeselect={ deselectCallback }
						resetAllFilter={ deselectCallback }
					>
						<TaxonomyItem
							key={ slug }
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

export default TaxonomyControls;
