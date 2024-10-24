/**
 * External dependencies
 */
import { Cart, CartItem } from '@woocommerce/types';
import { dispatch, select } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis, @woocommerce/dependency-group
import { __unstableStripHTML as stripHTML } from '@wordpress/dom';

/**
 * Internal dependencies
 */
import { STORE_KEY as CART_STORE_KEY } from './constants';

interface NotifyQuantityChangesArgs {
	oldCart: Cart;
	newCart: Cart;
	cartItemsPendingQuantity?: string[] | undefined;
	cartItemsPendingDelete?: string[] | undefined;
}

const isWithinQuantityLimits = ( cartItem: CartItem ) => {
	return (
		cartItem.quantity >= cartItem.quantity_limits.minimum &&
		cartItem.quantity <= cartItem.quantity_limits.maximum &&
		cartItem.quantity % cartItem.quantity_limits.multiple_of === 0
	);
};

const stripAndDecode = ( text: string ) => {
	return stripHTML( decodeEntities( text ) );
};

const notifyIfQuantityChanged = (
	oldCart: Cart,
	newCart: Cart,
	cartItemsPendingQuantity: string[]
) => {
	newCart.items.forEach( ( cartItem ) => {
		if ( cartItemsPendingQuantity.includes( cartItem.key ) ) {
			return;
		}
		const oldCartItem = oldCart.items.find( ( item ) => {
			return item && item.key === cartItem.key;
		} );
		if ( ! oldCartItem ) {
			return;
		}

		if ( cartItem.key === oldCartItem.key ) {
			if (
				cartItem.quantity !== oldCartItem.quantity &&
				isWithinQuantityLimits( cartItem )
			) {
				dispatch( 'core/notices' ).createInfoNotice(
					sprintf(
						/* translators: %1$s is the name of the item, %2$d is the quantity of the item. */
						__(
							'The quantity of "%1$s" was changed to %2$d.',
							'woocommerce'
						),
						stripAndDecode( cartItem.name ),
						cartItem.quantity
					),
					{
						context: 'wc/cart',
						speak: true,
						type: 'snackbar',
						id: `${ cartItem.key }-quantity-update`,
					}
				);
			}
			return cartItem;
		}
	} );
};

/**
 * Checks whether the old cart contains an item that the new cart doesn't, and that the item was not slated for removal.
 *
 * @param oldCart                The old cart.
 * @param newCart                The new cart.
 * @param cartItemsPendingDelete The cart items that are pending deletion.
 */
const notifyIfRemoved = (
	oldCart: Cart,
	newCart: Cart,
	cartItemsPendingDelete: string[]
) => {
	oldCart.items.forEach( ( oldCartItem ) => {
		if ( cartItemsPendingDelete.includes( oldCartItem.key ) ) {
			return;
		}

		const newCartItem = newCart.items.find( ( item: CartItem ) => {
			return item && item.key === oldCartItem.key;
		} );

		if ( ! newCartItem ) {
			dispatch( 'core/notices' ).createInfoNotice(
				sprintf(
					/* translators: %s is the name of the item. */
					__( '"%s" was removed from your cart.', 'woocommerce' ),
					stripAndDecode( oldCartItem.name )
				),
				{
					context: 'wc/cart',
					speak: true,
					type: 'snackbar',
					id: `${ oldCartItem.key }-removed`,
				}
			);
		}
	} );
};

/**
 * This function is used to notify the user when the quantity of an item in the cart has changed. It checks both the
 * item's quantity and quantity limits.
 */
export const notifyQuantityChanges = ( {
	oldCart,
	newCart,
	cartItemsPendingQuantity = [],
	cartItemsPendingDelete = [],
}: NotifyQuantityChangesArgs ) => {
	const isResolutionFinished =
		select( CART_STORE_KEY ).hasFinishedResolution( 'getCartData' );
	if ( ! isResolutionFinished ) {
		return;
	}
	notifyIfRemoved( oldCart, newCart, cartItemsPendingDelete );
	notifyIfQuantityChanged( oldCart, newCart, cartItemsPendingQuantity );
};
