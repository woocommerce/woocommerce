/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, address } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';

registerFeaturePluginBlockType( 'woocommerce/checkout-billing-address-block', {
	title: __( 'Billing Address', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Manage your address requirements.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ address } />,
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
