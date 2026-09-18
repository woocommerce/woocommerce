/**
 * External dependencies
 */
import type { BlockVariation } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

// These areas require their owning WooCommerce block to render correctly.
const INCOMPATIBLE_TEMPLATE_PART_AREAS = [
	'mini-cart',
	'add-to-cart-with-options',
];

addFilter(
	'blocks.registerBlockType',
	'woocommerce/hide-incompatible-template-parts',
	(
		blockSettings: { variations?: BlockVariation< { area?: string } >[] },
		blockName: string
	) => {
		if (
			blockName !== 'core/template-part' ||
			! blockSettings.variations
		) {
			return blockSettings;
		}

		return {
			...blockSettings,
			variations: blockSettings.variations.map( ( variation ) => {
				if (
					INCOMPATIBLE_TEMPLATE_PART_AREAS.includes(
						variation.attributes?.area ?? ''
					)
				) {
					return {
						...variation,
						scope: variation.scope?.filter(
							( scope ) => scope !== 'inserter'
						),
					};
				}
				return variation;
			} ),
		};
	}
);
