/**
 * External dependencies
 */
import { previewCartItems } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import CheckoutButton from './checkout-button';
import CartLineItemsTitle from './cart-line-items-title';
import CartLineItemsTable from './cart-line-items-table';

import './style.scss';
import './editor.scss';

/**
 * Component that renders the Cart block when user has something in cart aka "full".
 */
const Cart = () => {
	return (
		<div className="wc-block-cart">
			<div className="wc-block-cart__main">
				<CartLineItemsTitle itemCount={ previewCartItems.length } />
				<CartLineItemsTable lineItems={ previewCartItems } />
			</div>
			<div className="wc-block-cart__sidebar">
				<CheckoutButton />
			</div>
		</div>
	);
};

Cart.propTypes = {};

export default Cart;
