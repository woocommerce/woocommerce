"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getRemoveLinkedProductDispatcher = exports.getSelectSearchedProductDispatcher = exports.getLoadLinkedProductsDispatcher = exports.reducer = void 0;
/**
 * External dependencies
 */
const data_1 = require("@wordpress/data");
const data_2 = require("@woocommerce/data");
function reducer(state, action) {
    switch (action.type) {
        case 'SELECT_SEARCHED_PRODUCT':
        case 'REMOVE_LINKED_PRODUCT':
            if (action.payload.selectedProduct) {
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
exports.reducer = reducer;
function getLoadLinkedProductsDispatcher(dispatch) {
    return async function loadLinkedProductsDispatcher(linkedProductIds) {
        if (linkedProductIds.length === 0) {
            dispatch({
                type: 'SET_LINKED_PRODUCTS',
                payload: {
                    linkedProducts: [],
                },
            });
            return Promise.resolve([]);
        }
        dispatch({
            type: 'LOADING_LINKED_PRODUCTS',
            payload: {
                isLoading: true,
            },
        });
        return (0, data_1.resolveSelect)(data_2.PRODUCTS_STORE_NAME)
            .getProducts({
            include: linkedProductIds,
        })
            .then((response) => {
            dispatch({
                type: 'SET_LINKED_PRODUCTS',
                payload: {
                    linkedProducts: response,
                },
            });
            return response;
        })
            .finally(() => {
            dispatch({
                type: 'LOADING_LINKED_PRODUCTS',
                payload: {
                    isLoading: false,
                },
            });
        });
    };
}
exports.getLoadLinkedProductsDispatcher = getLoadLinkedProductsDispatcher;
function getSelectSearchedProductDispatcher(dispatch) {
    return function selectSearchedProductDispatcher(selectedProduct, linkedProducts) {
        if (!Array.isArray(selectedProduct)) {
            selectedProduct = [selectedProduct];
        }
        const newLinkedProducts = [...linkedProducts, ...selectedProduct];
        dispatch({
            type: 'SELECT_SEARCHED_PRODUCT',
            payload: { selectedProduct, linkedProducts: newLinkedProducts },
        });
        return newLinkedProducts.map((product) => product.id);
    };
}
exports.getSelectSearchedProductDispatcher = getSelectSearchedProductDispatcher;
function getRemoveLinkedProductDispatcher(dispatch) {
    return function removeLinkedProductDispatcher(selectedProduct, linkedProducts) {
        const newLinkedProducts = linkedProducts.reduce((list, current) => {
            if (current.id === selectedProduct.id) {
                return list;
            }
            return [...list, current];
        }, []);
        dispatch({
            type: 'REMOVE_LINKED_PRODUCT',
            payload: { selectedProduct, linkedProducts: newLinkedProducts },
        });
        return newLinkedProducts.map((product) => product.id);
    };
}
exports.getRemoveLinkedProductDispatcher = getRemoveLinkedProductDispatcher;
