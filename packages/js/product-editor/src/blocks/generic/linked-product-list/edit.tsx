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
import { ProductList } from '../../../components/product-list';
import { ProductSelect } from '../../../components/product-select';
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

			{ Boolean( state.linkedProducts.length ) && (
				<ProductList
					products={ state.linkedProducts }
					onRemove={ handleRemoveProductClick }
				/>
			) }
		</div>
	);
}
