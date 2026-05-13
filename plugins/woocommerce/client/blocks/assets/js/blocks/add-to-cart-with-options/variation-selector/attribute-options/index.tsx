/**
 * External dependencies
 */
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon, buttons } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import AttributeOptionsEdit from './edit';
import './style.scss';

const INNER_CHIPS = 'woocommerce/product-filter-chips';
const INNER_DROPDOWN = 'woocommerce/product-filter-dropdown';

registerBlockType( metadata, {
	edit: AttributeOptionsEdit,
	icon: {
		src: <Icon icon={ buttons } />,
	},
	save: () => null,
	deprecated: [
		{
			isEligible( attributes, innerBlocks ) {
				const style = attributes?.optionStyle;
				const legacyPills = style === 'pills';
				const missingInner =
					! Array.isArray( innerBlocks ) || innerBlocks.length === 0;
				return legacyPills || missingInner;
			},
			migrate( attributes, innerBlocks ) {
				const defaultAttributes = {
					optionStyle: metadata.attributes.optionStyle.default,
					autoselect: metadata.attributes.autoselect.default,
					disabledAttributesAction:
						metadata.attributes.disabledAttributesAction.default,
				};
				const nextAttributes = { ...defaultAttributes, ...attributes };
				if ( nextAttributes.optionStyle === 'pills' ) {
					nextAttributes.optionStyle = 'chips';
				}
				if ( Array.isArray( innerBlocks ) && innerBlocks.length > 0 ) {
					return [ nextAttributes, innerBlocks ];
				}
				const innerName =
					nextAttributes.optionStyle === 'dropdown'
						? INNER_DROPDOWN
						: INNER_CHIPS;
				return [ nextAttributes, [ createBlock( innerName ) ] ];
			},
			save: () => null,
		},
	],
} );
