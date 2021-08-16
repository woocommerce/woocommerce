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
import attributes from './attributes';

registerFeaturePluginBlockType( 'woocommerce/checkout-payment-block', {
	title: __( 'Payment Options', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Manage your payment options.',
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
	attributes,
	apiVersion: 2,
	edit: Edit,
	save: Save,
} );
