/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useCallback,
	useEffect,
	useReducer,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
	useAsyncFilter,
	Spinner,
} from '@woocommerce/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { FormattedPrice } from '../../../components/formatted-price';
import { ProductList } from '../../../components/product-list';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ProductEditorBlockEditProps } from '../../../types';
import {
	getLoadLinkedProductsDispatcher,
	getRemoveLinkedProductDispatcher,
	getSearchProductsDispatcher,
	getSelectSearchedProductDispatcher,
	reducer,
} from './reducer';
import { LinkedProductListBlockAttributes } from './types';

export function getProductImageStyle( product: Product ) {
	return product.images.length > 0
		? {
				backgroundImage: `url(${ product.images[ 0 ].src })`,
		  }
		: undefined;
}

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< LinkedProductListBlockAttributes > ) {
	const { property } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const [ state, dispatch ] = useReducer( reducer, {
		linkedProducts: [],
		searchedProducts: [],
	} );
	const loadLinkedProductsDispatcher =
		getLoadLinkedProductsDispatcher( dispatch );
	const searchProductsDispatcher = getSearchProductsDispatcher( dispatch );
	const selectSearchedProductDispatcher =
		getSelectSearchedProductDispatcher( dispatch );
	const removeLinkedProductDispatcher =
		getRemoveLinkedProductDispatcher( dispatch );

	const [ linkedProductIds, setLinkedProductIds ] = useProductEntityProp<
		number[]
	>( property, { postType } );

	useEffect( () => {
		if ( ! state.selectedProduct ) {
			loadLinkedProductsDispatcher( linkedProductIds ?? [] );
		}
	}, [ linkedProductIds, state.selectedProduct ] );

	const filter = useCallback(
		( search = '' ) => {
			return searchProductsDispatcher( linkedProductIds ?? [], search );
		},
		[ linkedProductIds ]
	);

	useEffect( () => {
		filter();
	}, [ filter ] );

	const { isFetching, ...selectProps } = useAsyncFilter< Product >( {
		filter,
	} );

	function handleSelect( product: Product ) {
		const newLinkedProductIds = selectSearchedProductDispatcher(
			product,
			state.linkedProducts
		);
		setLinkedProductIds( newLinkedProductIds );
	}

	function handleRemoveProductClick( product: Product ) {
		const newLinkedProductIds = removeLinkedProductDispatcher(
			product,
			state.linkedProducts
		);

		setLinkedProductIds( newLinkedProductIds );
	}

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-linked-list-field__form-group-content">
				<SelectControl< Product >
					{ ...selectProps }
					items={ state.searchedProducts }
					placeholder={ __( 'Search for products', 'woocommerce' ) }
					label=""
					selected={ null }
					onSelect={ handleSelect }
					__experimentalOpenMenuOnFocus
				>
					{ ( {
						items,
						isOpen,
						highlightedIndex,
						getMenuProps,
						getItemProps,
					} ) => (
						<Menu
							isOpen={ isOpen }
							getMenuProps={ getMenuProps }
							className="wp-block-woocommerce-product-linked-list-field__menu"
						>
							{ isFetching ? (
								<div className="wp-block-woocommerce-product-linked-list-field__menu-loading">
									<Spinner />
								</div>
							) : (
								items.map( ( item, index ) => (
									<MenuItem< Product >
										key={ item.id }
										index={ index }
										isActive={ highlightedIndex === index }
										item={ item }
										getItemProps={ ( options ) => ( {
											...getItemProps( options ),
											className:
												'wp-block-woocommerce-product-linked-list-field__menu-item',
										} ) }
									>
										<>
											<div
												className="wp-block-woocommerce-product-linked-list-field__menu-item-image"
												style={ getProductImageStyle(
													item
												) }
											/>
											<div className="wp-block-woocommerce-product-linked-list-field__menu-item-content">
												<div className="wp-block-woocommerce-product-linked-list-field__menu-item-title">
													{ item.name }
												</div>

												<FormattedPrice
													product={ item }
													className="wp-block-woocommerce-product-linked-list-field__menu-item-description"
												/>
											</div>
										</>
									</MenuItem>
								) )
							) }
						</Menu>
					) }
				</SelectControl>
			</div>

			{ Boolean( state.linkedProducts.length ) && (
				<ProductList
					products={ state.linkedProducts }
					onRemove={ handleRemoveProductClick }
				/>
			) }
		</div>
	);
}
