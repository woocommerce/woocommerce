/**
 * External dependencies
 */
import {
	createElement,
	useCallback,
	useEffect,
	useReducer,
} from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ProductList } from '../../../components/product-list';
import { ProductSelect } from '../../../components/product-select';
import { AdviceCard } from '../../../components/advice-card';
import { ShoppingBags } from '../../../images/shopping-bags';
import { CashRegister } from '../../../images/cash-register';
import { ProductEditorBlockEditProps } from '../../../types';
import { Skeleton } from './skeleton/skeleton';
import {
	getLoadLinkedProductsDispatcher,
	getRemoveLinkedProductDispatcher,
	getSearchProductsDispatcher,
	getSelectSearchedProductDispatcher,
	reducer,
} from './reducer';
import {
	LinkedProductListBlockAttributes,
	LinkedProductListBlockEmptyState,
} from './types';

export function EmptyStateImage( {
	image,
	tip: description,
}: LinkedProductListBlockEmptyState ) {
	switch ( image ) {
		case 'CashRegister':
			return <CashRegister />;
		case 'ShoppingBags':
			return <ShoppingBags />;
		default:
			if ( /^https?:\/\//.test( image ) ) {
				return (
					<img
						src={ image }
						alt={ description }
						height={ 88 }
						width={ 88 }
					/>
				);
			}
			return null;
	}
}

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< LinkedProductListBlockAttributes > ) {
	const { property, emptyState } = attributes;
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
				<ProductSelect
					items={ state.searchedProducts }
					selected={ null }
					filter={ filter }
					onSelect={ handleSelect }
				/>
			</div>

			{ state.isLoading && <Skeleton /> }

			{ ! state.isLoading && state.linkedProducts.length === 0 && (
				<AdviceCard
					image={ <EmptyStateImage { ...emptyState } /> }
					tip={ emptyState.tip }
					isDismissible={ emptyState.isDismissible }
				/>
			) }

			{ ! state.isLoading && state.linkedProducts.length > 0 && (
				<ProductList
					products={ state.linkedProducts }
					onRemove={ handleRemoveProductClick }
				/>
			) }
		</div>
	);
}
