/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, truck } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';

registerFeaturePluginBlockType( 'woocommerce/checkout-shipping-methods-block', {
	title: __( 'Shipping Options', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Shipping options for your store.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ truck } />,
		foreground: '#874FB9',
	},
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
	},
	parent: [ 'woocommerce/checkout-fields-block' ],
	attributes,
	apiVersion: 2,
	edit: Edit,
	save: Save,
} );
