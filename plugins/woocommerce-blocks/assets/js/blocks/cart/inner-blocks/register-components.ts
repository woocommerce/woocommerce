/**
 * External dependencies
 */
import { lazy } from '@wordpress/element';
import { WC_BLOCKS_BUILD_URL } from '@woocommerce/block-settings';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

// Modify webpack publicPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = WC_BLOCKS_BUILD_URL;

/**
 * Internal dependencies
 */
import filledCartMetadata from './filled-cart-block/block.json';
import emptyCartMetadata from './empty-cart-block/block.json';
import cartItemsMetadata from './cart-items-block/block.json';
import cartExpressPaymentMetadata from './cart-express-payment-block/block.json';
import cartLineItemsMetadata from './cart-line-items-block/block.json';
import cartOrderSummaryMetadata from './cart-order-summary-block/block.json';
import cartTotalsMetadata from './cart-totals-block/block.json';
import cartProceedToCheckoutMetadata from './proceed-to-checkout-block/block.json';
import cartAcceptedPaymentMethodsMetadata from './cart-accepted-payment-methods-block/block.json';

registerCheckoutBlock( {
	metadata: filledCartMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/filled-cart" */ './filled-cart-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: emptyCartMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/empty-cart" */ './empty-cart-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: filledCartMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/filled-cart" */ './filled-cart-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: emptyCartMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/empty-cart" */ './empty-cart-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: cartItemsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/items" */ './cart-items-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: cartLineItemsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/line-items" */ './cart-line-items-block/block'
		)
	),
} );

registerCheckoutBlock( {
	metadata: cartTotalsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/totals" */ './cart-totals-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: cartOrderSummaryMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/order-summary" */ './cart-order-summary-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: cartExpressPaymentMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/express-payment" */ './cart-express-payment-block/block'
		)
	),
} );

registerCheckoutBlock( {
	metadata: cartProceedToCheckoutMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/checkout-button" */ './proceed-to-checkout-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: cartAcceptedPaymentMethodsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "cart-blocks/accepted-payment-methods" */ './cart-accepted-payment-methods-block/frontend'
		)
	),
} );
