/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, _n, sprintf } from '@wordpress/i18n';
import { SearchListItem } from '@woocommerce/editor-components/search-list-control';
import { withInstanceId } from '@wordpress/compose';
import useProductAttributes from '@woocommerce/base-context/hooks/use-product-attributes';
import ExpandableSearchListItem from '@woocommerce/editor-components/expandable-search-list-item/expandable-search-list-item';
import ProductTaxonomyControl from '@woocommerce/editor-components/product-taxonomy-control';
import {
	RenderItemArgs,
	SearchListControlProps,
	SearchListItem as SearchListItemProps,
} from '@woocommerce/editor-components/search-list-control/types';
import { convertAttributeObjectToSearchItem } from '@woocommerce/utils';
import type { AttributeObject, AttributeTerm } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

interface Props
	extends Omit< SearchListControlProps, 'isSingle' | 'list' | 'selected' > {
	instanceId?: string;
	/**
	 * Callback to update the category operator. If not passed in, setting is not used.
	 */
	onOperatorChange?: () => void;
	/**
	 * Setting for whether products should match all or any selected categories.
	 */
	operator: 'all' | 'any';
	/**
	 * The list of currently selected attribute ids.
	 */
	selected: { id: number }[];
}

/**
 * Attribute rows use negative IDs so they do not collide with term IDs in the
 * flat list consumed by SearchListControl (keys, expansion, buildTermsTree).
 */
const toAttributeListItem = (
	attribute: AttributeObject
): SearchListItemProps => ( {
	...convertAttributeObjectToSearchItem( attribute ),
	id: -attribute.id,
	parent: 0,
} );

const toTermListItem = (
	term: AttributeTerm,
	attributeId: number
): SearchListItemProps => ( {
	...convertAttributeObjectToSearchItem( term ),
	parent: -attributeId,
} );

const ProductAttributeTermControl = ( {
	onChange,
	onOperatorChange,
	instanceId,
	isCompact = false,
	messages = {},
	operator = 'any',
	selected,
	type = 'text',
}: Props ) => {
	const { errorLoadingAttributes, isLoadingAttributes, productsAttributes } =
		useProductAttributes( true );

	const renderItem = ( args: RenderItemArgs ) => {
		const { item, search, depth = 0 } = args;
		const count = item.count || 0;
		const classes = [
			'woocommerce-product-attributes__item',
			'woocommerce-search-list__item',
			{
				'is-searching': search.length > 0,
				'is-skip-level': depth === 0 && item.parent !== 0,
			},
		];

		if ( ! item.breadcrumbs.length ) {
			return (
				<ExpandableSearchListItem
					{ ...args }
					className={ clsx( classes ) }
					item={ item }
					isLoading={ isLoadingAttributes }
					isSelectable={ false }
					disabled={ item.count === 0 }
					name={ `attributes-${ instanceId }` }
					countLabel={ sprintf(
						/* translators: %d is the count of terms. */
						_n( '%d term', '%d terms', count, 'woocommerce' ),
						count
					) }
					aria-label={ sprintf(
						/* translators: %1$s is the item name, %2$d is the count of terms for the item. */
						_n(
							'%1$s, has %2$d term',
							'%1$s, has %2$d terms',
							count,
							'woocommerce'
						),
						item.name,
						count
					) }
				/>
			);
		}

		const itemName = `${ item.breadcrumbs[ 0 ] }: ${ item.name }`;

		return (
			<SearchListItem
				{ ...args }
				name={ `terms-${ instanceId }` }
				className={ clsx( ...classes, 'has-count' ) }
				countLabel={ sprintf(
					/* translators: %d is the count of products. */
					_n( '%d product', '%d products', count, 'woocommerce' ),
					count
				) }
				aria-label={ sprintf(
					/* translators: %1$s is the attribute name, %2$d is the count of products for that attribute. */
					_n(
						'%1$s, has %2$d product',
						'%1$s, has %2$d products',
						count,
						'woocommerce'
					),
					itemName,
					count
				) }
			/>
		);
	};

	const list = productsAttributes.reduce( ( acc, curr ) => {
		const { terms, ...attribute } = curr;

		return [
			...acc,
			toAttributeListItem( attribute ),
			...terms.map( ( term ) => toTermListItem( term, attribute.id ) ),
		];
	}, [] as SearchListItemProps[] );

	messages = {
		clear: __( 'Clear all product attributes', 'woocommerce' ),
		noItems: __(
			"Your store doesn't have any product attributes.",
			'woocommerce'
		),
		search: __( 'Search for product attributes', 'woocommerce' ),
		selected: ( n: number ) =>
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
		...messages,
	};

	return (
		<ProductTaxonomyControl
			className="woocommerce-product-attributes"
			error={ errorLoadingAttributes }
			isCompact={ isCompact }
			isHierarchical
			isLoading={ isLoadingAttributes }
			isSingle={ false }
			list={ list }
			messages={ messages }
			onChange={ onChange }
			operator={
				onOperatorChange
					? {
							className:
								'woocommerce-product-attributes__operator',
							labels: {
								all: __(
									'All selected attributes',
									'woocommerce'
								),
								any: __(
									'Any selected attributes',
									'woocommerce'
								),
								help: __(
									'Pick at least two attributes to use this setting.',
									'woocommerce'
								),
							},
							onChange: onOperatorChange,
							selectedCount: selected.length,
							value: operator,
					  }
					: undefined
			}
			renderItem={ renderItem }
			selected={
				selected
					.map( ( { id } ) =>
						list.find( ( term ) => term.id === id )
					)
					.filter( Boolean ) as SearchListItemProps[]
			}
			type={ type }
		/>
	);
};

export default withInstanceId( ProductAttributeTermControl );
