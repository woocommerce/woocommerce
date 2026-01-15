/**
 * External dependencies
 */
import { WC_BLOCKS_BUILD_URL } from '@woocommerce/block-settings';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import metadata from './component-metadata';
import FilledCartFrontend from './filled-cart-block/frontend';
import EmptyCartFrontend from './empty-cart-block/frontend';
import CartItemsFrontend from './cart-items-block/frontend';
import CartLineItemsBlock from './cart-line-items-block/frontend';
import CartCrossSellsFrontend from './cart-cross-sells-block/frontend';
import CartCrossSellsProductsFrontend from './cart-cross-sells-products/frontend';
import CartTotalsFrontend from './cart-totals-block/frontend';
import CartExpressPaymentFrontend from './cart-express-payment-block/frontend';
import ProceedToCheckoutFrontend from './proceed-to-checkout-block/frontend';
import CartAcceptedPaymentMethodsFrontend from './cart-accepted-payment-methods-block/frontend';
import CartOrderSummaryFrontend from './cart-order-summary-block/frontend';
import CartOrderSummaryHeadingFrontend from './cart-order-summary-heading/frontend';
import CartOrderSummarySubtotalFrontend from './cart-order-summary-subtotal/frontend';
import CartOrderSummaryFeeFrontend from './cart-order-summary-fee/frontend';
import CartOrderSummaryDiscountFrontend from './cart-order-summary-discount/frontend';
import CartOrderSummaryCouponFormFrontend from './cart-order-summary-coupon-form/frontend';
import CartOrderSummaryShippingFrontend from './cart-order-summary-shipping/frontend';
import CartOrderSummaryTotalsFrontend from './cart-order-summary-totals/frontend';
import CartOrderSummaryTaxesFrontend from './cart-order-summary-taxes/frontend';

// Modify webpack publicPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = WC_BLOCKS_BUILD_URL;

registerCheckoutBlock( {
	metadata: metadata.FILLED_CART,
	component: FilledCartFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.EMPTY_CART,
	component: EmptyCartFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ITEMS,
	component: CartItemsFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_LINE_ITEMS,
	component: CartLineItemsBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_CROSS_SELLS,
	component: CartCrossSellsFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_CROSS_SELLS_PRODUCTS,
	component: CartCrossSellsProductsFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_TOTALS,
	component: CartTotalsFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_EXPRESS_PAYMENT,
	component: CartExpressPaymentFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.PROCEED_TO_CHECKOUT,
	component: ProceedToCheckoutFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ACCEPTED_PAYMENT_METHODS,
	component: CartAcceptedPaymentMethodsFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY,
	component: CartOrderSummaryFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_HEADING,
	component: CartOrderSummaryHeadingFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_SUBTOTAL,
	component: CartOrderSummarySubtotalFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_FEE,
	component: CartOrderSummaryFeeFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_DISCOUNT,
	component: CartOrderSummaryDiscountFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_COUPON_FORM,
	component: CartOrderSummaryCouponFormFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_SHIPPING,
	component: CartOrderSummaryShippingFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_TOTALS,
	component: CartOrderSummaryTotalsFrontend,
} );

registerCheckoutBlock( {
	metadata: metadata.CART_ORDER_SUMMARY_TAXES,
	component: CartOrderSummaryTaxesFrontend,
} );
