/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, asterisk } from '@woocommerce/icons';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { lazy } from '@wordpress/element';
import { WC_BLOCKS_BUILD_URL } from '@woocommerce/block-settings';

// Modify webpack publicPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = WC_BLOCKS_BUILD_URL;

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

// @todo Sample block should only be visible in correct areas, not top level.
registerCheckoutBlock( 'woocommerce/checkout-sample-block', {
	component: lazy( () =>
		import( /* webpackChunkName: "checkout-blocks/sample" */ './frontend' )
	),
	areas: [ 'shippingAddress', 'billingAddress', 'contactInformation' ],
	configuration: {
		title: __( 'Sample Block', 'woo-gutenberg-products-block' ),
		category: 'woocommerce',
		description: __(
			'A sample block showing how to integrate with Checkout i2.',
			'woo-gutenberg-products-block'
		),
		icon: {
			src: <Icon srcElement={ asterisk } />,
			foreground: '#874FB9',
		},
		supports: {
			align: false,
			html: false,
			multiple: true,
			reusable: false,
		},
		attributes: {},
		edit: Edit,
		save: Save,
	},
} );
