/**
 * Internal dependencies
 */
import { BlockAttributes, Color } from './types';

function getColorSlug( color: string | Color ) {
	if ( typeof color === 'string' ) return color;
	if ( 'slug' in color ) return color.slug;
}

function getColorCSSVariable( color: null | string | Color ) {
	if ( ! color ) return null;
	return `var( --wp--preset--color--${ getColorSlug( color ) } )`;
}

export function getCheckboxListStyles(
	attributes: BlockAttributes
): Record< string, string > {
	const {
		optionElementBorder,
		customOptionElementBorder,
		optionElementSelected,
		customOptionElementSelected,
		optionElement,
		customOptionElement,
	} = attributes;
	return {
		'--wc-product-filter-checkbox-list-option-element-border':
			getColorCSSVariable( optionElementBorder ) ||
			customOptionElementBorder,
		'--wc-product-filter-checkbox-list-option-element-selected':
			getColorCSSVariable( optionElementSelected ) ||
			customOptionElementSelected,
		'--wc-product-filter-checkbox-list-option-element':
			getColorCSSVariable( optionElement ) || customOptionElement,
		'--wc-product-filter-checkbox-list-option-element-fallback':
			getColorCSSVariable( optionElement ) || customOptionElement
				? 'none'
				: 'block',
	};
}
