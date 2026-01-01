/**
 * External dependencies
 */
import {
	WC_BLOCKS_BUILD_URL,
	LOCAL_PICKUP_ENABLED,
} from '@woocommerce/block-settings';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import metadata from './component-metadata';
import CheckoutFieldsBlock from './checkout-fields-block/frontend';
import CheckoutExpressPaymentBlock from './checkout-express-payment-block/frontend';
import CheckoutContactInformationBlock from './checkout-contact-information-block/frontend';
import CheckoutShippingMethodBlock from './checkout-shipping-method-block/frontend';
import CheckoutPickupOptionsBlock from './checkout-pickup-options-block/frontend';
import CheckoutShippingAddressBlock from './checkout-shipping-address-block/frontend';
import CheckoutBillingAddressBlock from './checkout-billing-address-block/frontend';
import CheckoutShippingMethodsBlock from './checkout-shipping-methods-block/frontend';
import CheckoutPaymentBlock from './checkout-payment-block/frontend';
import CheckoutAdditionalInformationBlock from './checkout-additional-information-block/frontend';
import CheckoutOrderNoteBlock from './checkout-order-note-block/block';
import CheckoutTermsBlock from './checkout-terms-block/frontend';
import CheckoutActionsBlock from './checkout-actions-block/frontend';
import CheckoutTotalsBlock from './checkout-totals-block/frontend';
import CheckoutOrderSummaryBlock from './checkout-order-summary-block/frontend';
import CheckoutOrderSummaryCartItemsBlock from './checkout-order-summary-cart-items/frontend';
import CheckoutOrderSummarySubtotalBlock from './checkout-order-summary-subtotal/frontend';
import CheckoutOrderSummaryFeeBlock from './checkout-order-summary-fee/frontend';
import CheckoutOrderSummaryDiscountBlock from './checkout-order-summary-discount/frontend';
import CheckoutOrderSummaryCouponFormBlock from './checkout-order-summary-coupon-form/frontend';
import CheckoutOrderSummaryShippingBlock from './checkout-order-summary-shipping/frontend';
import CheckoutOrderSummaryTaxesBlock from './checkout-order-summary-taxes/frontend';

// Modify webpack publicPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = WC_BLOCKS_BUILD_URL;

// @todo When forcing all blocks at once, they will append based on the order they are registered. Introduce formal sorting param.
registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_FIELDS,
	component: CheckoutFieldsBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_EXPRESS_PAYMENT,
	component: CheckoutExpressPaymentBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_CONTACT_INFORMATION,
	component: CheckoutContactInformationBlock,
} );

if ( LOCAL_PICKUP_ENABLED ) {
	registerCheckoutBlock( {
		metadata: metadata.CHECKOUT_SHIPPING_METHOD,
		component: CheckoutShippingMethodBlock,
	} );
	registerCheckoutBlock( {
		metadata: metadata.CHECKOUT_PICKUP_LOCATION,
		component: CheckoutPickupOptionsBlock,
	} );
}

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_SHIPPING_ADDRESS,
	component: CheckoutShippingAddressBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_BILLING_ADDRESS,
	component: CheckoutBillingAddressBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_SHIPPING_METHODS,
	component: CheckoutShippingMethodsBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_PAYMENT,
	component: CheckoutPaymentBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_INFORMATION,
	component: CheckoutAdditionalInformationBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_NOTE,
	component: CheckoutOrderNoteBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_TERMS,
	component: CheckoutTermsBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ACTIONS,
	component: CheckoutActionsBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_TOTALS,
	component: CheckoutTotalsBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY,
	component: CheckoutOrderSummaryBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY_CART_ITEMS,
	component: CheckoutOrderSummaryCartItemsBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY_SUBTOTAL,
	component: CheckoutOrderSummarySubtotalBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY_FEE,
	component: CheckoutOrderSummaryFeeBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY_DISCOUNT,
	component: CheckoutOrderSummaryDiscountBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY_COUPON_FORM,
	component: CheckoutOrderSummaryCouponFormBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY_SHIPPING,
	component: CheckoutOrderSummaryShippingBlock,
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_ORDER_SUMMARY_TAXES,
	component: CheckoutOrderSummaryTaxesBlock,
} );
