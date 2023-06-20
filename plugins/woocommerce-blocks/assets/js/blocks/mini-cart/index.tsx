/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { miniCartAlt } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import edit from './edit';

const settings: BlockConfiguration = {
	apiVersion: 2,
	title: __( 'Mini-Cart', 'woo-gutenberg-products-block' ),
	icon: {
		src: (
			<Icon
				icon={ miniCartAlt }
				className="wc-block-editor-components-block-icon wc-block-editor-mini-cart__icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a button for shoppers to quickly view their cart.',
		'woo-gutenberg-products-block'
	),
	providesContext: {
		priceColorValue: 'priceColorValue',
		iconColorValue: 'iconColorValue',
		productCountColorValue: 'productCountColorValue',
	},
	supports: {
		html: false,
		multiple: false,
		typography: {
			fontSize: true,
			...( isFeaturePluginBuild() && {
				__experimentalFontFamily: true,
				__experimentalFontWeight: true,
			} ),
		},
	},
	example: {
		attributes: {
			isPreview: true,
			className: 'wc-block-mini-cart--preview',
		},
	},
	attributes: {
		isPreview: {
			type: 'boolean',
			default: false,
		},
		miniCartIcon: {
			type: 'string',
			default: 'cart',
		},
		addToCartBehaviour: {
			type: 'string',
			default: 'none',
		},
		hasHiddenPrice: {
			type: 'boolean',
			default: false,
		},
		cartAndCheckoutRenderStyle: {
			type: 'string',
			default: 'hidden',
		},
		priceColor: {
			type: 'string',
		},
		priceColorValue: {
			type: 'string',
		},
		iconColor: {
			type: 'string',
		},
		iconColorValue: {
			type: 'string',
		},
		productCountColor: {
			type: 'string',
		},
		productCountColorValue: {
			type: 'string',
		},
	},
	edit,
	save() {
		return null;
	},
};

registerBlockType( 'woocommerce/mini-cart', settings );

// Remove the Mini Cart template part from the block inserter.
addFilter(
	'blocks.registerBlockType',
	'woocommerce/mini-cart',
	function ( blockSettings, blockName ) {
		if ( blockName === 'core/template-part' ) {
			return {
				...blockSettings,
				variations: blockSettings.variations.map(
					( variation: { name: string } ) => {
						if ( variation.name === 'instance_mini-cart' ) {
							return {
								...variation,
								scope: [],
							};
						}
						return variation;
					}
				),
			};
		}
		return blockSettings;
	}
);
