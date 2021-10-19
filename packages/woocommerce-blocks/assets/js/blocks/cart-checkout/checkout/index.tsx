/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Icon, fields } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { blockName, blockAttributes } from './attributes';
import './inner-blocks';

const settings = {
	title: __( 'Checkout', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ fields } />,
		foreground: '#874FB9',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a checkout form so your customers can submit orders.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
	},
	attributes: blockAttributes,
	apiVersion: 2,
	edit: Edit,
	save: Save,
	// Migrates v1 to v2 checkout.
	deprecated: [
		{
			attributes: blockAttributes,
			save( { attributes }: { attributes: { className: string } } ) {
				return (
					<div
						className={ classnames(
							'is-loading',
							attributes.className
						) }
					/>
				);
			},
		},
	],
};

registerFeaturePluginBlockType( blockName, settings );
