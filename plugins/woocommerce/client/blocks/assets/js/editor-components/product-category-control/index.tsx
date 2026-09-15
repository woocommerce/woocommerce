/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { withSearchedCategories } from '@woocommerce/block-hocs';
import ProductTaxonomyControl from '@woocommerce/editor-components/product-taxonomy-control';
import type {
	ProductCategoryResponseItem,
	WithInjectedSearchedCategories,
} from '@woocommerce/types';
import { convertProductCategoryResponseItemToSearchItem } from '@woocommerce/utils';

interface ProductCategoryControlProps {
	/**
	 * Callback to update the selected product categories.
	 */
	onChange: () => void;
	/**
	 * Whether or not the search control should be displayed in a compact way, so it occupies less space.
	 */
	isCompact?: boolean;
	/**
	 * Allow only a single selection. Defaults to false.
	 */
	isSingle?: boolean;
	/**
	 * Callback to update the category operator. If not passed in, setting is not used.
	 */
	onOperatorChange?: () => void;
	/**
	 * Setting for whether products should match all or any selected categories.
	 */
	operator?: 'all' | 'any';
	/**
	 * Whether or not to display the number of reviews for a category in the list.
	 */
	showReviewCount?: boolean;
}

const ProductCategoryControl = ( {
	categories = [],
	error = null,
	isLoading = false,
	onChange,
	onOperatorChange,
	operator = 'any',
	selected,
	isCompact = false,
	isSingle = false,
	showReviewCount,
}: ProductCategoryControlProps & WithInjectedSearchedCategories ) => {
	const messages = {
		clear: __( 'Clear all product categories', 'woocommerce' ),
		list: __( 'Product Categories', 'woocommerce' ),
		noItems: __(
			"Your store doesn't have any product categories.",
			'woocommerce'
		),
		search: __( 'Search for product categories', 'woocommerce' ),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the count of selected categories. */
				_n(
					'%d category selected',
					'%d categories selected',
					n,
					'woocommerce'
				),
				n
			),
		updated: __( 'Category search results updated.', 'woocommerce' ),
	};
	const list = categories.map(
		convertProductCategoryResponseItemToSearchItem
	);

	return (
		<ProductTaxonomyControl< ProductCategoryResponseItem >
			className="woocommerce-product-categories"
			countType={ showReviewCount ? 'review' : 'product' }
			error={ error }
			isCompact={ isCompact }
			isHierarchical
			isLoading={ isLoading }
			isSingle={ isSingle }
			itemClassName="woocommerce-product-categories__item"
			list={ list }
			messages={ messages }
			onChange={ onChange }
			operator={
				onOperatorChange
					? {
							className:
								'woocommerce-product-categories__operator',
							labels: {
								all: __(
									'All selected categories',
									'woocommerce'
								),
								any: __(
									'Any selected categories',
									'woocommerce'
								),
								help: __(
									'Pick at least two categories to use this setting.',
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

export default withSearchedCategories( ProductCategoryControl );
