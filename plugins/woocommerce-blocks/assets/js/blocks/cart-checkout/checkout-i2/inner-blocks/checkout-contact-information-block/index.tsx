/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, contact } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';

registerFeaturePluginBlockType(
	'woocommerce/checkout-contact-information-block',
	{
		title: __( 'Contact Information', 'woo-gutenberg-products-block' ),
		category: 'woocommerce',
		description: __(
			"Collect your customer's contact information.",
			'woo-gutenberg-products-block'
		),
		icon: {
			src: <Icon srcElement={ contact } />,
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
	}
);
