/**
 * External dependencies
 */

/**
 * Mini Cart Express Checkout Block
 *
 * This block provides the container element for express payment methods.
 * The actual React components are rendered by the iAPI frontend script
 * via renderExpressPaymentMethodsIsland(), which uses CheckoutProvider
 * to access the normal Redux payment data store.
 */
const Block = (): JSX.Element => {
	return (
		<div className="wc-block-mini-cart__express-checkout">
			<div className="wc-block-mini-cart__express-checkout-title">
				<span>Express Checkout non-interactive</span>
			</div>
		</div>
	);
};

export default Block;
