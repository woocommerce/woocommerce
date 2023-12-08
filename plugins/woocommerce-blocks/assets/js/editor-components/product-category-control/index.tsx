/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	SearchListControl,
	SearchListItem,
} from '@woocommerce/editor-components/search-list-control';
import { SelectControl } from '@wordpress/components';
import { withSearchedCategories } from '@woocommerce/block-hocs';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message';
import classNames from 'classnames';
import type { RenderItemArgs } from '@woocommerce/editor-components/search-list-control/types';
import type {
	ProductCategoryResponseItem,
	WithInjectedSearchedCategories,
} from '@woocommerce/types';
import { convertProductCategoryResponseItemToSearchItem } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import './style.scss';

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
	const renderItem = (
		args: RenderItemArgs< ProductCategoryResponseItem >
	) => {
		const { item, search, depth = 0 } = args;

		const accessibleName = ! item.breadcrumbs.length
			? item.name
			: `${ item.breadcrumbs.join( ', ' ) }, ${ item.name }`;

		const listItemAriaLabel = showReviewCount
			? sprintf(
					/* translators: %1$s is the item name, %2$d is the count of reviews for the item. */
					_n(
						'%1$s, has %2$d review',
						'%1$s, has %2$d reviews',
						item.details?.review_count || 0,
						'woo-gutenberg-products-block'
					),
					accessibleName,
					item.details?.review_count || 0
			  )
			: sprintf(
					/* translators: %1$s is the item name, %2$d is the count of products for the item. */
					_n(
						'%1$s, has %2$d product',
						'%1$s, has %2$d products',
						item.details?.count || 0,
						'woo-gutenberg-products-block'
					),
					accessibleName,
					item.details?.count || 0
			  );

		const listItemCountLabel = showReviewCount
			? sprintf(
					/* translators: %d is the count of reviews. */
					_n(
						'%d review',
						'%d reviews',
						item.details?.review_count || 0,
						'woo-gutenberg-products-block'
					),
					item.details?.review_count || 0
			  )
			: sprintf(
					/* translators: %d is the count of products. */
					_n(
						'%d product',
						'%d products',
						item.details?.count || 0,
						'woo-gutenberg-products-block'
					),
					item.details?.count || 0
			  );

		return (
			<SearchListItem
				className={ classNames(
					'woocommerce-product-categories__item',
					'has-count',
					{
						'is-searching': search.length > 0,
						'is-skip-level': depth === 0 && item.parent !== 0,
					}
				) }
				{ ...args }
				countLabel={ listItemCountLabel }
				aria-label={ listItemAriaLabel }
			/>
		);
	};

	const messages = {
		clear: __(
			'Clear all product categories',
			'woo-gutenberg-products-block'
		),
		list: __( 'Product Categories', 'woo-gutenberg-products-block' ),
		noItems: __(
			"Your store doesn't have any product categories.",
			'woo-gutenberg-products-block'
		),
		search: __(
			'Search for product categories',
			'woo-gutenberg-products-block'
		),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the count of selected categories. */
				_n(
					'%d category selected',
					'%d categories selected',
					n,
					'woo-gutenberg-products-block'
				),
				n
			),
		updated: __(
			'Category search results updated.',
			'woo-gutenberg-products-block'
		),
	};

	if ( error ) {
		return <ErrorMessage error={ error } />;
	}

	const currentList = categories.map(
		convertProductCategoryResponseItemToSearchItem
	);

	return (
		<>
			<SearchListControl
				className="woocommerce-product-categories"
				list={ currentList }
				isLoading={ isLoading }
				selected={ currentList.filter( ( { id } ) =>
					selected.includes( Number( id ) )
				) }
				onChange={ onChange }
				renderItem={ renderItem }
				messages={ messages }
				isCompact={ isCompact }
				isHierarchical
				isSingle={ isSingle }
			/>
			{ !! onOperatorChange && (
				<div hidden={ selected.length < 2 }>
					<SelectControl
						className="woocommerce-product-categories__operator"
						label={ __(
							'Display products matching',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Pick at least two categories to use this setting.',
							'woo-gutenberg-products-block'
						) }
						value={ operator }
						onChange={ onOperatorChange }
						options={ [
							{
								label: __(
									'Any selected categories',
									'woo-gutenberg-products-block'
								),
								value: 'any',
							},
							{
								label: __(
									'All selected categories',
									'woo-gutenberg-products-block'
								),
								value: 'all',
							},
						] }
					/>
				</div>
			) }
		</>
	);
};

export default withSearchedCategories( ProductCategoryControl );
