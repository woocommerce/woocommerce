/**
 * External dependencies
 */
import { lazy } from '@wordpress/element';
import { registerBlockComponent } from '@woocommerce/blocks-registry';
import { WC_BLOCKS_BUILD_URL } from '@woocommerce/block-settings';

// Modify webpack publicPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = WC_BLOCKS_BUILD_URL;

registerBlockComponent( {
	blockName: 'woocommerce/checkout-fields-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/fields" */ './checkout-fields-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-terms-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/terms" */ './checkout-terms-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-totals-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/totals" */ './checkout-totals-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-billing-address-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/billing-address" */ './checkout-billing-address-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-actions-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/actions" */ './checkout-actions-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-contact-information-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/contact-information" */ './checkout-contact-information-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-order-note-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/order-note" */ './checkout-order-note-block/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-order-summary-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/order-summary" */ './checkout-order-summary-block/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-payment-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/payment" */ './checkout-payment-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-shipping-address-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/shipping-address" */ './checkout-shipping-address-block/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-express-payment-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/express-payment" */ './checkout-express-payment-block/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/checkout-shipping-methods-block',
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/shipping-methods" */ './checkout-shipping-methods-block/frontend'
		)
	),
} );
