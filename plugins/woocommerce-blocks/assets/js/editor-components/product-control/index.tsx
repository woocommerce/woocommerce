/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { isEmpty } from '@woocommerce/types';
import {
	SearchListControl,
	SearchListItem,
} from '@woocommerce/editor-components/search-list-control';
import type {
	SearchListControlProps,
	RenderItemArgs,
} from '@woocommerce/editor-components/search-list-control/types';
import { withInstanceId } from '@wordpress/compose';
import {
	withProductVariations,
	withSearchedProducts,
	withTransformSingleSelectToMultipleSelect,
} from '@woocommerce/block-hocs';
import type {
	ProductResponseItem,
	WithInjectedInstanceId,
	WithInjectedProductVariations,
	WithInjectedSearchedProducts,
} from '@woocommerce/types';
import { convertProductResponseItemToSearchItem } from '@woocommerce/utils';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message';
import clsx from 'clsx';
import ExpandableSearchListItem from '@woocommerce/editor-components/expandable-search-list-item/expandable-search-list-item';

/**
 * Internal dependencies
 */
import './style.scss';

interface ProductControlProps {
	/**
	 * Callback to update the selected products.
	 */
	onChange: () => void;
	/**
	 * Whether or not the search control should be displayed in a compact way, so it occupies less space.
	 */
	isCompact?: boolean;
	/**
	 * The ID of the currently expanded product.
	 */
	expandedProduct: number | null;
	/**
	 * Callback to search products by their name.
	 */
	onSearch: () => void;
	/**
	 * Callback to render each item in the selection list, allows any custom object-type rendering.
	 */
	renderItem: SearchListControlProps[ 'renderItem' ] | null;
	/**
	 * The ID of the currently selected item (product or variation).
	 */
	selected: number[];
	/**
	 * Whether to show variations in the list of items available.
	 */
	showVariations?: boolean;
	/**
	 * Different messages to display in the component.
	 * If any of the messages are not provided, the default message will be used.
	 */
	messages?: {
		list?: string;
		noItems?: string;
		search?: string;
		updated?: string;
	};
}

const messages = {
	list: __( 'Products', 'woocommerce' ),
	noItems: __( "Your store doesn't have any products.", 'woocommerce' ),
	search: __( 'Search for a product to display', 'woocommerce' ),
	updated: __( 'Product search results updated.', 'woocommerce' ),
};

const ProductControl = (
	props: ProductControlProps &
		WithInjectedSearchedProducts &
		WithInjectedProductVariations &
		WithInjectedInstanceId
) => {
	const {
		expandedProduct = null,
		error,
		instanceId,
		isCompact = false,
		isLoading,
		onChange,
		onSearch,
		products,
		renderItem,
		selected = [],
		showVariations = false,
		variations,
		variationsLoading,
	} = props;

	const renderItemWithVariations = (
		args: RenderItemArgs< ProductResponseItem >
	) => {
		const { item, search, depth = 0, isSelected, onSelect } = args;
		const variationsCount =
			item.details?.variations && Array.isArray( item.details.variations )
				? item.details.variations.length
				: 0;
		const classes = clsx(
			'woocommerce-search-product__item',
			'woocommerce-search-list__item',
			`depth-${ depth }`,
			'has-count',
			{
				'is-searching': search.length > 0,
				'is-skip-level': depth === 0 && item.parent !== 0,
				'is-variable': variationsCount > 0,
			}
		);

		// Top level items custom rendering based on SearchListItem.
		if ( ! item.breadcrumbs.length ) {
			const hasVariations =
				item.details?.variations && item.details.variations.length > 0;

			return (
				<ExpandableSearchListItem
					{ ...args }
					className={ clsx( classes, {
						'is-selected': isSelected,
					} ) }
					isSelected={ isSelected }
					item={ item }
					onSelect={ () => {
						return () => {
							onSelect( item )();
						};
					} }
					isLoading={ isLoading || variationsLoading }
					countLabel={
						hasVariations
							? sprintf(
									/* translators: %1$d is the number of variations of a product product. */
									__( '%1$d variations', 'woocommerce' ),
									item.details?.variations.length
							  )
							: null
					}
					name={ `products-${ instanceId }` }
					aria-label={
						hasVariations
							? sprintf(
									/* translators: %1$s is the product name, %2$d is the number of variations of that product. */
									_n(
										'%1$s, has %2$d variation',
										'%1$s, has %2$d variations',
										item.details?.variations
											?.length as number,
										'woocommerce'
									),
									item.name,
									item.details?.variations.length
							  )
							: undefined
					}
				/>
			);
		}

		const itemArgs = isEmpty( item.details?.variation )
			? args
			: {
					...args,
					item: {
						...args.item,
						name: item.details?.variation as string,
					},
					'aria-label': `${ item.breadcrumbs[ 0 ] }: ${ item.details?.variation }`,
			  };

		return (
			<SearchListItem
				{ ...itemArgs }
				className={ classes }
				name={ `variations-${ instanceId }` }
			/>
		);
	};

	const getRenderItemFunc = () => {
		if ( renderItem ) {
			return renderItem;
		} else if ( showVariations ) {
			return renderItemWithVariations;
		}
		return undefined;
	};

	if ( error ) {
		return <ErrorMessage error={ error } />;
	}

	const currentVariations =
		variations && expandedProduct && variations[ expandedProduct ]
			? variations[ expandedProduct ]
			: [];
	const currentList = [ ...products, ...currentVariations ].map(
		convertProductResponseItemToSearchItem
	);

	return (
		<SearchListControl
			className="woocommerce-products"
			list={ currentList }
			isCompact={ isCompact }
			isLoading={ isLoading }
			isSingle
			selected={ currentList.filter( ( { id } ) =>
				selected.includes( Number( id ) )
			) }
			onChange={ onChange }
			renderItem={ getRenderItemFunc() }
			onSearch={ onSearch }
			messages={ {
				...messages,
				...props.messages,
			} }
			isHierarchical
		/>
	);
};

export default withTransformSingleSelectToMultipleSelect(
	withSearchedProducts(
		withProductVariations( withInstanceId( ProductControl ) )
	)
);
