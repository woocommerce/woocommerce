/**
 * External dependencies
 */
import { button } from '@wordpress/icons';
import { getPlugin, registerPlugin } from '@wordpress/plugins';
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import type { BlockConfiguration } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import ProductTypeSelectorPlugin from './plugins';
import metadata from './block.json';
import AddToCartOptionsEdit from './edit';
import '../../base/components/quantity-selector/style.scss';
import type { Attributes } from './types';

// Register a plugin that adds a product type selector to the template sidebar.
const PLUGIN_NAME = 'document-settings-template-selector-pane';
if ( ! getPlugin( PLUGIN_NAME ) ) {
	registerPlugin( PLUGIN_NAME, {
		render: ProductTypeSelectorPlugin,
	} );
}

// Register the block
registerProductBlockType< Attributes >(
	{
		...( metadata as BlockConfiguration< Attributes > ),
		icon: {
			src: ( { size }: { size?: number } ) => (
				<span
					className="wp-block-woocommerce-add-to-cart-with-options__block-icon"
					style={ { height: size, width: size } }
				>
					{ button }
				</span>
			),
		},
		edit: AddToCartOptionsEdit,
		save: () => null,
		ancestor: [ 'woocommerce/single-product' ],
	},
	{
		isAvailableOnPostEditor: true,
	}
);

// Remove the Add to Cart + Options template part from the block inserter.
addFilter(
	'blocks.registerBlockType',
	'woocommerce/area_add-to-cart-with-options',
	function ( blockSettings, blockName ) {
		if ( blockName === 'core/template-part' ) {
			return {
				...blockSettings,
				variations: blockSettings.variations.map(
					( variation: { name: string } ) => {
						if (
							variation.name === 'area_add-to-cart-with-options'
						) {
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
