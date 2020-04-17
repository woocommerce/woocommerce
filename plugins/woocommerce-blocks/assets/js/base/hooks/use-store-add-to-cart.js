/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { find } from 'lodash';
import { useStoreCart } from '@woocommerce/base-hooks';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * @typedef {import('@woocommerce/type-defs/hooks').StoreCartItemAddToCart} StoreCartItemAddToCart
 */

/**
 * Get the quantity of a product in the cart.
 *
 * @param {Object} cartItems Array of items.
 * @param {number} productId  The product id to look for.
 * @return {number} Quantity in the cart.
 */
const getQuantityFromCartItems = ( cartItems, productId ) => {
	const productItem = find( cartItems, { id: productId } );

	return productItem ? productItem.quantity : 0;
};

/**
 * A custom hook for exposing cart related data for a given product id and an
 * action for adding a single quantity of the product _to_ the cart.
 *
 *
 * @param {number} productId  The product id to be added to the cart.
 *
 * @return {StoreCartItemAddToCart} An object exposing data and actions relating
 *                                  to add to cart functionality.
 */
export const useStoreAddToCart = ( productId ) => {
	const { addItemToCart } = useDispatch( storeKey );
	const { cartItems, cartIsLoading } = useStoreCart();

	const [ addingToCart, setAddingToCart ] = useState( false );
	const currentCartItemQuantity = useRef(
		getQuantityFromCartItems( cartItems, productId )
	);

	const addToCart = () => {
		setAddingToCart( true );
		addItemToCart( productId ).finally( () => {
			setAddingToCart( false );
		} );
	};

	useEffect( () => {
		const quantity = getQuantityFromCartItems( cartItems, productId );

		if ( quantity !== currentCartItemQuantity.current ) {
			currentCartItemQuantity.current = quantity;
		}
	}, [ cartItems, productId ] );

	return {
		cartQuantity: currentCartItemQuantity.current,
		addingToCart,
		cartIsLoading,
		addToCart,
	};
};
