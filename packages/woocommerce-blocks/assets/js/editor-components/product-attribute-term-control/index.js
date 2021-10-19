/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { SearchListControl, SearchListItem } from '@woocommerce/components';
import { SelectControl } from '@wordpress/components';
import { withInstanceId } from '@wordpress/compose';
import { withAttributes } from '@woocommerce/block-hocs';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message.js';
import classNames from 'classnames';
import ExpandableSearchListItem from '@woocommerce/editor-components/expandable-search-list-item/expandable-search-list-item.tsx';

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
	instanceId,
	isCompact,
	isLoading,
	operator,
	selected,
	termsAreLoading,
	termsList,
} ) => {
	const renderItem = ( args ) => {
		const { item, search, depth = 0 } = args;
		const classes = [
			'woocommerce-product-attributes__item',
			'woocommerce-search-list__item',
			{
				'is-searching': search.length > 0,
				'is-skip-level': depth === 0 && item.parent !== 0,
			},
		];

		if ( ! item.breadcrumbs.length ) {
			const isSelected = expandedAttribute === item.id;
			return (
				<ExpandableSearchListItem
					{ ...args }
					className={ classNames( ...classes, {
						'is-selected': isSelected,
					} ) }
					isSelected={ isSelected }
					item={ item }
					isLoading={ termsAreLoading }
					disabled={ item.count === '0' }
					onSelect={ ( { id } ) => {
						return () => {
							onChange( [] );
							onExpandAttribute( id );
						};
					} }
					name={ `attributes-${ instanceId }` }
					countLabel={ sprintf(
						/* translators: %d is the count of terms. */
						_n(
							'%d term',
							'%d terms',
							item.count,
							'woocommerce'
						),
						item.count
					) }
					aria-label={ sprintf(
						/* translators: %1$s is the item name, %2$d is the count of terms for the item. */
						_n(
							'%1$s, has %2$d term',
							'%1$s, has %2$d terms',
							item.count,
							'woocommerce'
						),
						item.name,
						item.count
					) }
				/>
			);
		}

		const itemName = `${ item.breadcrumbs[ 0 ] }: ${ item.name }`;

		return (
			<SearchListItem
				{ ...args }
				name={ `terms-${ instanceId }` }
				className={ classNames( ...classes, 'has-count' ) }
				countLabel={ sprintf(
					/* translators: %d is the count of products. */
					_n(
						'%d product',
						'%d products',
						item.count,
						'woocommerce'
					),
					item.count
				) }
				aria-label={ sprintf(
					/* translators: %1$s is the attribute name, %2$d is the count of products for that attribute. */
					_n(
						'%1$s, has %2$d product',
						'%1$s, has %2$d products',
						item.count,
						'woocommerce'
					),
					itemName,
					item.count
				) }
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
				/* translators: %d is the count of attributes selected. */
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
					.map( ( { id } ) =>
						currentList.find(
							( currentListItem ) => currentListItem.id === id
						)
					)
					.filter( Boolean ) }
				onChange={ onChange }
				renderItem={ renderItem }
				messages={ messages }
				isCompact={ isCompact }
				isHierarchical
			/>
			{ !! onOperatorChange && (
				<div hidden={ selected.length < 2 }>
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
	isCompact: PropTypes.bool,
	isLoading: PropTypes.bool,
	termsAreLoading: PropTypes.bool,
	termsList: PropTypes.object,
};

ProductAttributeTermControl.defaultProps = {
	isCompact: false,
	operator: 'any',
};

export default withAttributes( withInstanceId( ProductAttributeTermControl ) );
