/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { useStoreNotices } from './use-store-notices';
import { useStoreCart } from './cart';

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
	const productItem = cartItems.find( ( { id } ) => id === productId );

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
	const { addErrorNotice, removeNotice } = useStoreNotices();

	const [ addingToCart, setAddingToCart ] = useState( false );
	const currentCartItemQuantity = useRef(
		getQuantityFromCartItems( cartItems, productId )
	);

	const addToCart = ( quantity = 1 ) => {
		setAddingToCart( true );
		addItemToCart( productId, quantity )
			.then( ( result ) => {
				if ( result === true ) {
					removeNotice( 'add-to-cart' );
				}
			} )
			.catch( ( error ) => {
				addErrorNotice( decodeEntities( error.message ), {
					context: 'wc/all-products',
					id: 'add-to-cart',
					isDismissible: true,
				} );
			} )
			.finally( () => {
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
		cartQuantity: Number.isFinite( currentCartItemQuantity.current )
			? currentCartItemQuantity.current
			: 0,
		addingToCart,
		cartIsLoading,
		addToCart,
	};
};
