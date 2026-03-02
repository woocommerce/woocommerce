/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, category, tag, store } from '@wordpress/icons';
import { Placeholder, Button } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import ProductTagControl from '@woocommerce/editor-components/product-tag-control';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import ProductBrandControl from '@woocommerce/editor-components/product-brand-control';

/**
 * Internal dependencies
 */
import type { ProductCollectionEditComponentProps } from '../types';
import { CoreCollectionNames } from '../types';
import { getCollectionByName } from '../collections';
import { setQueryAttribute } from '../utils';

interface TaxonomyPickerProps extends ProductCollectionEditComponentProps {
	onDone: () => void;
}

/**
 * Get the taxonomy slug for a given collection.
 */
export const getTaxonomySlugForCollection = (
	collection: string | undefined
): string | null => {
	switch ( collection ) {
		case CoreCollectionNames.BY_CATEGORY:
			return 'product_cat';
		case CoreCollectionNames.BY_TAG:
			return 'product_tag';
		case CoreCollectionNames.BY_BRAND:
			return 'product_brand';
		default:
			return null;
	}
};

/**
 * Get the description text for a given collection.
 */
const getDescriptionForCollection = (
	collection: string | undefined
): string => {
	switch ( collection ) {
		case CoreCollectionNames.BY_CATEGORY:
			return __(
				'Display a grid of products from your selected categories.',
				'woocommerce'
			);
		case CoreCollectionNames.BY_TAG:
			return __(
				'Display a grid of products from your selected tags.',
				'woocommerce'
			);
		case CoreCollectionNames.BY_BRAND:
			return __(
				'Display a grid of products from your selected brands.',
				'woocommerce'
			);
		default:
			return __(
				'Select taxonomy terms to display products from.',
				'woocommerce'
			);
	}
};

/**
 * Get the icon for a given collection.
 */
const getIconForCollection = ( collection: string | undefined ) => {
	switch ( collection ) {
		case CoreCollectionNames.BY_CATEGORY:
			return category;
		case CoreCollectionNames.BY_TAG:
			return tag;
		case CoreCollectionNames.BY_BRAND:
			return store;
		default:
			return category;
	}
};

const TaxonomyPicker = ( props: TaxonomyPickerProps ) => {
	const { attributes, onDone } = props;
	const blockProps = useBlockProps();

	const collectionData = getCollectionByName( attributes.collection );
	const taxonomySlug = getTaxonomySlugForCollection( attributes.collection );

	// Get selected term IDs for the relevant taxonomy
	const selectedTermIds: number[] = taxonomySlug
		? attributes.query?.taxQuery?.[ taxonomySlug ] || []
		: [];

	const hasSelectedTerms = selectedTermIds.length > 0;

	if ( ! collectionData || ! taxonomySlug ) {
		return null;
	}

	const handleTermChange = ( termIds: number[] ) => {
		setQueryAttribute( props, {
			taxQuery: {
				...attributes.query?.taxQuery,
				[ taxonomySlug ]: termIds,
			},
		} );
	};

	const renderTaxonomyControl = () => {
		switch ( attributes.collection ) {
			case CoreCollectionNames.BY_CATEGORY:
				return (
					<ProductCategoryControl
						selected={ selectedTermIds }
						onChange={ ( value = [] ) => {
							const ids = value.map(
								( { id }: { id: number } ) => id
							);
							handleTermChange( ids );
						} }
					/>
				);
			case CoreCollectionNames.BY_TAG:
				return (
					<ProductTagControl
						selected={ selectedTermIds }
						onChange={ ( value = [] ) => {
							const ids = value.map(
								( { id }: { id: number | string } ) =>
									Number( id )
							);
							handleTermChange( ids );
						} }
					/>
				);
			case CoreCollectionNames.BY_BRAND:
				return (
					<ProductBrandControl
						selected={ selectedTermIds }
						onChange={ ( value = [] ) => {
							const ids = value.map(
								( { id }: { id: number } ) => id
							);
							handleTermChange( ids );
						} }
					/>
				);
			default:
				return null;
		}
	};

	return (
		<div { ...blockProps }>
			<Placeholder
				icon={
					// @ts-expect-error Icon types are incomplete
					<Icon
						icon={ getIconForCollection( attributes.collection ) }
						className="block-editor-block-icon"
					/>
				}
				label={ collectionData.title }
			>
				{ getDescriptionForCollection( attributes.collection ) }
				<div className="wc-block-editor-product-collection__taxonomy-picker-selection">
					{ renderTaxonomyControl() }
					<Button
						variant="primary"
						onClick={ onDone }
						disabled={ ! hasSelectedTerms }
					>
						{ __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			</Placeholder>
		</div>
	);
};

export default TaxonomyPicker;
