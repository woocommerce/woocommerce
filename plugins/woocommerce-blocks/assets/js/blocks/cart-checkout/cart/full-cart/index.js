/**
 * Internal dependencies
 */
import CheckoutButton from './checkout-button';
import './style.scss';

/**
 * Component that renders the Cart block when user has something in cart aka "full".
 */
const Cart = () => {
	return (
		<div className="wc-block-cart">
			<div className="wc-block-cart__main">
				<span>
					Cart block <b>full state</b> coming soon…
				</span>
			</div>
			<div className="wc-block-cart__sidebar">
				<CheckoutButton />
			</div>
		</div>
	);
};

Cart.propTypes = {};

export default Cart;
