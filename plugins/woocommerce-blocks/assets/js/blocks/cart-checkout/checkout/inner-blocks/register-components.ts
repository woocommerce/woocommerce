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
import checkoutActionsMetadata from './checkout-actions-block/block.json';
import checkoutBillingAddressMetadata from './checkout-billing-address-block/block.json';
import checkoutContactInformationMetadata from './checkout-contact-information-block/block.json';
import checkoutExpressPaymentMetadata from './checkout-express-payment-block/block.json';
import checkoutFieldsMetadata from './checkout-fields-block/block.json';
import checkoutOrderNoteMetadata from './checkout-order-note-block/block.json';
import checkoutOrderSummaryMetadata from './checkout-order-summary-block/block.json';
import checkoutPaymentMetadata from './checkout-payment-block/block.json';
import checkoutShippingAddressMetadata from './checkout-shipping-address-block/block.json';
import checkoutShippingMethodsMetadata from './checkout-shipping-methods-block/block.json';
import checkoutTermsMetadata from './checkout-terms-block/block.json';
import checkoutTotalsMetadata from './checkout-totals-block/block.json';

// @todo When forcing all blocks at once, they will append based on the order they are registered. Introduce formal sorting param.
registerCheckoutBlock( {
	metadata: checkoutFieldsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/fields" */ './checkout-fields-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutExpressPaymentMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/express-payment" */ './checkout-express-payment-block/block'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutContactInformationMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/contact-information" */ './checkout-contact-information-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutShippingAddressMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/shipping-address" */ './checkout-shipping-address-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutBillingAddressMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/billing-address" */ './checkout-billing-address-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutShippingMethodsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/shipping-methods" */ './checkout-shipping-methods-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutPaymentMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/payment" */ './checkout-payment-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutOrderNoteMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/order-note" */ './checkout-order-note-block/block'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutTermsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/terms" */ './checkout-terms-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutActionsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/actions" */ './checkout-actions-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutTotalsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/totals" */ './checkout-totals-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: checkoutOrderSummaryMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/order-summary" */ './checkout-order-summary-block/block'
		)
	),
} );
