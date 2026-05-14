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
import AttributeOptionsSave from './save';
import './style.scss';

const INNER_CHIPS = 'woocommerce/product-filter-chips';
const INNER_DROPDOWN = 'woocommerce/product-filter-dropdown';

registerBlockType( metadata, {
	edit: AttributeOptionsEdit,
	icon: {
		src: <Icon icon={ buttons } />,
	},
	save: AttributeOptionsSave,
	deprecated: [
		{
			isEligible( attributes, innerBlocks ) {
				const style = attributes?.optionStyle;
				const legacyStyle = style === 'pills' || style === 'dropdown';
				const missingInner =
					! Array.isArray( innerBlocks ) || innerBlocks.length === 0;
				return legacyStyle || missingInner;
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
					nextAttributes.optionStyle =
						'woocommerce/product-filter-chips';
				}
				if ( Array.isArray( innerBlocks ) && innerBlocks.length > 0 ) {
					return [ nextAttributes, innerBlocks ];
				}
				const innerName =
					nextAttributes.optionStyle ===
					'woocommerce/product-filter-dropdown'
						? INNER_DROPDOWN
						: INNER_CHIPS;
				return [ nextAttributes, [ createBlock( innerName ) ] ];
			},
			save: () => null,
		},
	],
} );
