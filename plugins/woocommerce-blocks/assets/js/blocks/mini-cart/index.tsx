/**
 * External dependencies
 */
import { miniCartAlt } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { addFilter } from '@wordpress/hooks';
/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

const featurePluginSupport = {
	...metadata.supports,
	...( isFeaturePluginBuild() && {
		typography: {
			...metadata.supports.typography,
			__experimentalFontFamily: true,
			__experimentalFontWeight: true,
		},
	} ),
};

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ miniCartAlt }
				className="wc-block-editor-components-block-icon wc-block-editor-mini-cart__icon"
			/>
		),
	},
	supports: {
		...featurePluginSupport,
	},
	example: {
		...metadata.example,
	},
	attributes: {
		...metadata.attributes,
	},
	edit,
	save() {
		return null;
	},
} );

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
						if ( variation.name === 'mini-cart' ) {
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
