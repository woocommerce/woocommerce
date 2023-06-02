/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { find } from 'lodash';
import PropTypes from 'prop-types';
import { SearchListControl, SearchListItem } from '@woocommerce/components';
import { SelectControl, Spinner } from '@wordpress/components';
import { withAttributes } from '@woocommerce/block-hocs';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message.js';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductAttributeTermControl = ( {
	attributes,
	error,
	expandedAttribute,
	onChange,
	onExpandAttribute,
	onOperatorChange,
	isLoading,
	operator,
	selected,
	termsAreLoading,
	termsList,
} ) => {
	const onSelectAttribute = ( item ) => {
		return () => {
			onChange( [] );
			onExpandAttribute( item.id );
		};
	};

	const renderItem = ( args ) => {
		const { item, search, depth = 0 } = args;
		const classes = [
			'woocommerce-product-attributes__item',
			'woocommerce-search-list__item',
		];
		if ( search.length ) {
			classes.push( 'is-searching' );
		}
		if ( depth === 0 && item.parent ) {
			classes.push( 'is-skip-level' );
		}

		if ( ! item.breadcrumbs.length ) {
			return [
				<SearchListItem
					key={ `attr-${ item.id }` }
					{ ...args }
					className={ classes.join( ' ' ) }
					isSelected={ expandedAttribute === item.id }
					onSelect={ onSelectAttribute }
					isSingle
					disabled={ item.count === '0' }
					aria-expanded={ expandedAttribute === item.id }
					aria-label={ sprintf(
						// Translators: %1$s is the item name, %2$d is the count of terms for the item.
						_n(
							'%1$s, has %2$d term',
							'%1$s, has %2$d terms',
							item.count,
							'woocommerce'
						),
						item.name,
						item.count
					) }
				/>,
				expandedAttribute === item.id && termsAreLoading && (
					<div
						key="loading"
						className={
							'woocommerce-search-list__item woocommerce-product-attributes__item' +
							'depth-1 is-loading is-not-active'
						}
					>
						<Spinner />
					</div>
				),
			];
		}

		return (
			<SearchListItem
				className={ classes.join( ' ' ) }
				{ ...args }
				showCount
				aria-label={ `${ item.breadcrumbs[ 0 ] }: ${ item.name }` }
			/>
		);
	};

	const currentTerms = termsList[ expandedAttribute ] || [];
	const currentList = [ ...attributes, ...currentTerms ];

	const messages = {
		clear: __(
			'Clear all product attributes',
			'woocommerce'
		),
		list: __( 'Product Attributes', 'woocommerce' ),
		noItems: __(
			"Your store doesn't have any product attributes.",
			'woocommerce'
		),
		search: __(
			'Search for product attributes',
			'woocommerce'
		),
		selected: ( n ) =>
			sprintf(
				// Translators: %d is the count of attributes selected.
				_n(
					'%d attribute selected',
					'%d attributes selected',
					n,
					'woocommerce'
				),
				n
			),
		updated: __(
			'Product attribute search results updated.',
			'woocommerce'
		),
	};

	if ( error ) {
		return <ErrorMessage error={ error } />;
	}

	return (
		<>
			<SearchListControl
				className="woocommerce-product-attributes"
				list={ currentList }
				isLoading={ isLoading }
				selected={ selected
					.map( ( { id } ) => find( currentList, { id } ) )
					.filter( Boolean ) }
				onChange={ onChange }
				renderItem={ renderItem }
				messages={ messages }
				isHierarchical
			/>
			{ !! onOperatorChange && (
				<div
					className={
						selected.length < 2 ? 'screen-reader-text' : ''
					}
				>
					<SelectControl
						className="woocommerce-product-attributes__operator"
						label={ __(
							'Display products matching',
							'woocommerce'
						) }
						help={ __(
							'Pick at least two attributes to use this setting.',
							'woocommerce'
						) }
						value={ operator }
						onChange={ onOperatorChange }
						options={ [
							{
								label: __(
									'Any selected attributes',
									'woocommerce'
								),
								value: 'any',
							},
							{
								label: __(
									'All selected attributes',
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

ProductAttributeTermControl.propTypes = {
	/**
	 * Callback to update the selected product attributes.
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
	 * The list of currently selected attribute slug/ID pairs.
	 */
	selected: PropTypes.array.isRequired,
	// from withAttributes
	attributes: PropTypes.array,
	error: PropTypes.object,
	expandedAttribute: PropTypes.number,
	onExpandAttribute: PropTypes.func,
	isLoading: PropTypes.bool,
	termsAreLoading: PropTypes.bool,
	termsList: PropTypes.object,
};

ProductAttributeTermControl.defaultProps = {
	operator: 'any',
};

export default withAttributes( ProductAttributeTermControl );
