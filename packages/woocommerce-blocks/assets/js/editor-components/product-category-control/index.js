/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { find } from 'lodash';
import PropTypes from 'prop-types';
import { SearchListControl, SearchListItem } from '@woocommerce/components';
import { SelectControl } from '@wordpress/components';
import { withCategories } from '@woocommerce/block-hocs';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message.js';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductCategoryControl = ( {
	categories,
	error,
	isLoading,
	onChange,
	onOperatorChange,
	operator,
	selected,
	isSingle,
	showReviewCount,
} ) => {
	const renderItem = ( args ) => {
		const { item, search, depth = 0 } = args;
		const classes = [ 'woocommerce-product-categories__item' ];
		if ( search.length ) {
			classes.push( 'is-searching' );
		}
		if ( depth === 0 && item.parent !== 0 ) {
			classes.push( 'is-skip-level' );
		}

		const accessibleName = ! item.breadcrumbs.length
			? item.name
			: `${ item.breadcrumbs.join( ', ' ) }, ${ item.name }`;

		const listItemAriaLabel = showReviewCount
			? sprintf(
					// Translators: %1$s is the item name, %2$d is the count of reviews for the item.
					_n(
						'%1$s, has %2$d review',
						'%1$s, has %2$d reviews',
						item.review_count,
						'woocommerce'
					),
					accessibleName,
					item.review_count
			  )
			: sprintf(
					// Translators: %1$s is the item name, %2$d is the count of products for the item.
					_n(
						'%1$s, has %2$d product',
						'%1$s, has %2$d products',
						item.count,
						'woocommerce'
					),
					accessibleName,
					item.count
			  );

		const listItemCountLabel = showReviewCount
			? sprintf(
					// Translators: %d is the count of reviews.
					_n(
						'%d Review',
						'%d Reviews',
						item.review_count,
						'woocommerce'
					),
					item.review_count
			  )
			: sprintf(
					// Translators: %d is the count of products.
					_n(
						'%d Product',
						'%d Products',
						item.count,
						'woocommerce'
					),
					item.count
			  );
		return (
			<SearchListItem
				className={ classes.join( ' ' ) }
				{ ...args }
				showCount
				countLabel={ listItemCountLabel }
				aria-label={ listItemAriaLabel }
			/>
		);
	};

	const messages = {
		clear: __(
			'Clear all product categories',
			'woocommerce'
		),
		list: __( 'Product Categories', 'woocommerce' ),
		noItems: __(
			"Your store doesn't have any product categories.",
			'woocommerce'
		),
		search: __(
			'Search for product categories',
			'woocommerce'
		),
		selected: ( n ) =>
			sprintf(
				// Translators: %d is the count of selected categories.
				_n(
					'%d category selected',
					'%d categories selected',
					n,
					'woocommerce'
				),
				n
			),
		updated: __(
			'Category search results updated.',
			'woocommerce'
		),
	};

	if ( error ) {
		return <ErrorMessage error={ error } />;
	}

	return (
		<>
			<SearchListControl
				className="woocommerce-product-categories"
				list={ categories }
				isLoading={ isLoading }
				selected={ selected
					.map( ( id ) => find( categories, { id } ) )
					.filter( Boolean ) }
				onChange={ onChange }
				renderItem={ renderItem }
				messages={ messages }
				isHierarchical
				isSingle={ isSingle }
			/>
			{ !! onOperatorChange && (
				<div
					className={
						selected.length < 2 ? 'screen-reader-text' : ''
					}
				>
					<SelectControl
						className="woocommerce-product-categories__operator"
						label={ __(
							'Display products matching',
							'woocommerce'
						) }
						help={ __(
							'Pick at least two categories to use this setting.',
							'woocommerce'
						) }
						value={ operator }
						onChange={ onOperatorChange }
						options={ [
							{
								label: __(
									'Any selected categories',
									'woocommerce'
								),
								value: 'any',
							},
							{
								label: __(
									'All selected categories',
									'woocommerce'
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

ProductCategoryControl.propTypes = {
	/**
	 * Callback to update the selected product categories.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * Callback to update the category operator. If not passed in, setting is not used.
	 */
	onOperatorChange: PropTypes.func,
	/**
	 * Setting for whether products should match all or any selected categories.
	 */
	operator: PropTypes.oneOf( [ 'all', 'any' ] ),
	/**
	 * The list of currently selected category IDs.
	 */
	selected: PropTypes.array.isRequired,
	/**
	 * Allow only a single selection. Defaults to false.
	 */
	isSingle: PropTypes.bool,
};

ProductCategoryControl.defaultProps = {
	operator: 'any',
	isSingle: false,
};

export default withCategories( ProductCategoryControl );
