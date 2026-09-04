/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { withSearchedBrands } from '@woocommerce/block-hocs';
import ProductTaxonomyControl from '@woocommerce/editor-components/product-taxonomy-control';
import type {
	ProductBrandResponseItem,
	WithInjectedSearchedBrands,
} from '@woocommerce/types';
import { convertProductBrandResponseItemToSearchItem } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import type { SearchListItem as SearchListItemProps } from '../search-list-control/types';

interface ProductBrandControlProps {
	/**
	 * Callback to update the selected product brands.
	 */
	onChange: ( selected: SearchListItemProps[] ) => void;
	/**
	 * Whether or not the search control should be displayed in a compact way, so it occupies less space.
	 */
	isCompact?: boolean;
	/**
	 * Allow only a single selection. Defaults to false.
	 */
	isSingle?: boolean;
	/**
	 * Callback to update the brand operator. If not passed in, setting is not used.
	 */
	onOperatorChange?: ( operator: string ) => void;
	/**
	 * Setting for whether products should match all or any selected brands.
	 */
	operator?: 'all' | 'any';
	/**
	 * Whether or not to display the number of reviews for a brand in the list.
	 */
	showReviewCount?: boolean;
}

const ProductBrandControl = ( {
	brands = [],
	error = null,
	isLoading = false,
	onChange,
	onOperatorChange,
	operator = 'any',
	selected = [],
	isCompact = false,
	isSingle = false,
	showReviewCount,
}: ProductBrandControlProps & WithInjectedSearchedBrands ) => {
	const messages = {
		clear: __( 'Clear all product brands', 'woocommerce' ),
		list: __( 'Product Brands', 'woocommerce' ),
		noItems: __(
			"Your store doesn't have any product brands.",
			'woocommerce'
		),
		search: __( 'Search for product brands', 'woocommerce' ),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the count of selected brands. */
				_n(
					'%d brand selected',
					'%d brands selected',
					n,
					'woocommerce'
				),
				n
			),
		updated: __( 'Brand search results updated.', 'woocommerce' ),
	};
	const list = brands.map( convertProductBrandResponseItemToSearchItem );

	return (
		<ProductTaxonomyControl< ProductBrandResponseItem >
			className="woocommerce-product-brands"
			countType={ showReviewCount ? 'review' : 'product' }
			error={ error }
			isCompact={ isCompact }
			isHierarchical
			isLoading={ isLoading }
			isSingle={ isSingle }
			itemClassName="woocommerce-product-brands__item"
			list={ list }
			messages={ messages }
			onChange={ onChange }
			operator={
				onOperatorChange
					? {
							className: 'woocommerce-product-brands__operator',
							labels: {
								all: __( 'All selected brands', 'woocommerce' ),
								any: __( 'Any selected brands', 'woocommerce' ),
								help: __(
									'Pick at least two brands to use this setting.',
									'woocommerce'
								),
							},
							onChange: onOperatorChange,
							selectedCount: selected.length,
							value: operator,
					  }
					: undefined
			}
			selected={ list.filter( ( { id } ) =>
				selected.includes( Number( id ) )
			) }
		/>
	);
};

export default withSearchedBrands( ProductBrandControl );
