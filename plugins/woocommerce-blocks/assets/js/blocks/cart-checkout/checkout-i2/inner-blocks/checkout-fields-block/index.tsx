/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, column } from '@wordpress/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType( 'woocommerce/checkout-fields-block', {
	title: __( 'Checkout Fields Block', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Column containing checkout address fields.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon icon={ column } />,
		foreground: '#874FB9',
	},
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
	},
	parent: [ 'woocommerce/checkout-i2' ],
	apiVersion: 2,
	edit: Edit,
	save: Save,
} );
