/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { PRODUCTS_STORE_NAME, Product } from '@woocommerce/data';

export type State = {
	linkedProducts: Product[];
	isLoading?: boolean;
	selectedProduct?: Product | Product[];
};

export type ActionType =
	| 'LOADING_LINKED_PRODUCTS'
	| 'SET_LINKED_PRODUCTS'
	| 'SELECT_SEARCHED_PRODUCT'
	| 'REMOVE_LINKED_PRODUCT';

export type Action = {
	type: ActionType;
	payload: Partial< State >;
};

export function reducer( state: State, action: Action ): State {
	switch ( action.type ) {
		case 'SELECT_SEARCHED_PRODUCT':
		case 'REMOVE_LINKED_PRODUCT':
			if ( action.payload.selectedProduct ) {
				return {
					...state,
					...action.payload,
				};
			}
			return state;
		default:
			return {
				...state,
				...action.payload,
			};
	}
}

export function getLoadLinkedProductsDispatcher(
	dispatch: ( value: Action ) => void
) {
	return async function loadLinkedProductsDispatcher(
		linkedProductIds: number[]
	) {
		if ( linkedProductIds.length === 0 ) {
			dispatch( {
				type: 'SET_LINKED_PRODUCTS',
				payload: {
					linkedProducts: [],
				},
			} );
			return Promise.resolve( [] );
		}

		dispatch( {
			type: 'LOADING_LINKED_PRODUCTS',
			payload: {
				isLoading: true,
			},
		} );
		return resolveSelect( PRODUCTS_STORE_NAME )
			.getProducts< Product[] >( {
				include: linkedProductIds,
			} )
			.then( ( response ) => {
				dispatch( {
					type: 'SET_LINKED_PRODUCTS',
					payload: {
						linkedProducts: response,
					},
				} );
				return response;
			} )
			.finally( () => {
				dispatch( {
					type: 'LOADING_LINKED_PRODUCTS',
					payload: {
						isLoading: false,
					},
				} );
			} );
	};
}

export function getSelectSearchedProductDispatcher(
	dispatch: ( value: Action ) => void
) {
	return function selectSearchedProductDispatcher(
		selectedProduct: Product | Product[],
		linkedProducts: Product[]
	) {
		if ( ! Array.isArray( selectedProduct ) ) {
			selectedProduct = [ selectedProduct ];
		}

		const newLinkedProducts = [ ...linkedProducts, ...selectedProduct ];

		dispatch( {
			type: 'SELECT_SEARCHED_PRODUCT',
			payload: { selectedProduct, linkedProducts: newLinkedProducts },
		} );

		return newLinkedProducts.map( ( product ) => product.id );
	};
}

export function getRemoveLinkedProductDispatcher(
	dispatch: ( value: Action ) => void
) {
	return function removeLinkedProductDispatcher(
		selectedProduct: Product,
		linkedProducts: Product[]
	) {
		const newLinkedProducts = linkedProducts.reduce< Product[] >(
			( list, current ) => {
				if ( current.id === selectedProduct.id ) {
					return list;
				}
				return [ ...list, current ];
			},
			[]
		);

		dispatch( {
			type: 'REMOVE_LINKED_PRODUCT',
			payload: { selectedProduct, linkedProducts: newLinkedProducts },
		} );

		return newLinkedProducts.map( ( product ) => product.id );
	};
}
