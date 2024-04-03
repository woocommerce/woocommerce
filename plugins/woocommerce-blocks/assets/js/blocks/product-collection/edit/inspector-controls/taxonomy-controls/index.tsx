/**
 * External dependencies
 */
import { Taxonomy } from '@wordpress/core-data/src/entity-types';
import { __ } from '@wordpress/i18n';
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
import { ProductCollectionQuery } from '../../../types';

interface TaxonomyControlProps {
	query: ProductCollectionQuery;
	setQueryAttribute: ( value: Partial< ProductCollectionQuery > ) => void;
}

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
	query,
}: TaxonomyControlProps ) {
	const { taxQuery } = query;

	const taxonomies = useTaxonomies();
	if ( ! taxonomies || taxonomies.length === 0 ) {
		return null;
	}

	return taxonomies.map( ( taxonomy: Taxonomy ) => {
		const termIds = taxQuery?.[ taxonomy.slug ] || [];
		const handleChange = ( newTermIds: number[] ) =>
			setQueryAttribute( {
				taxQuery: {
					...taxQuery,
					[ taxonomy.slug ]: newTermIds,
				},
			} );

		return (
			<ToolsPanelItem
				key={ taxonomy.slug }
				label={ taxonomy.name }
				hasValue={ () => termIds.length }
				onDeselect={ () => setQueryAttribute( { taxQuery: {} } ) }
			>
				<TaxonomyItem
					key={ taxonomy.slug }
					taxonomy={ taxonomy }
					termIds={ termIds }
					onChange={ handleChange }
				/>
			</ToolsPanelItem>
		);
	} );
}

export default TaxonomyControls;
