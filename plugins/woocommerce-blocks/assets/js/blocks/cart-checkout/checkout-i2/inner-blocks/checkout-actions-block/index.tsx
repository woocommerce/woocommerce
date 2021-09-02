/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, button } from '@wordpress/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import attributes from './attributes';
import { Edit, Save } from './edit';

registerFeaturePluginBlockType( 'woocommerce/checkout-actions-block', {
	title: __( 'Actions', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Allow customers to place their order.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon icon={ button } />,
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
