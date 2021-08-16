/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, card } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType( 'woocommerce/checkout-express-payment-block', {
	title: __( 'Express Checkout', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Provide an express payment option for your customers.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ card } />,
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
	attributes: {
		lock: {
			type: 'object',
			default: {
				remove: true,
				move: true,
			},
		},
	},
	apiVersion: 2,
	edit: Edit,
	save: Save,
} );
