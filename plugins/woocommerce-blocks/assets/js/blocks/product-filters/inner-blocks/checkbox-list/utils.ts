/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';

function getCSSVar( slug: string | undefined, value: string | undefined ) {
	if ( slug ) {
		return `var(--wp--preset--color--${ slug })`;
	}
	return value || '';
}

export function getColorVars( attributes: BlockAttributes ) {
	const {
		optionElement,
		optionElementBorder,
		optionElementSelected,
		customOptionElement,
		customOptionElementBorder,
		customOptionElementSelected,
	} = attributes;

	const vars: Record< string, string > = {
		'--wc-product-filter-checkbox-list-option-element': getCSSVar(
			optionElement,
			customOptionElement
		),
		'--wc-product-filter-checkbox-list-option-element-border': getCSSVar(
			optionElementBorder,
			customOptionElementBorder
		),
		'--wc-product-filter-checkbox-list-option-element-selected': getCSSVar(
			optionElementSelected,
			customOptionElementSelected
		),
	};

	return Object.keys( vars ).reduce(
		( acc: Record< string, string >, key ) => {
			if ( vars[ key ] ) {
				acc[ key ] = vars[ key ];
			}
			return acc;
		},
		{}
	);
}

export function getColorClasses( attributes: BlockAttributes ) {
	const {
		optionElement,
		optionElementBorder,
		optionElementSelected,
		customOptionElement,
		customOptionElementBorder,
		customOptionElementSelected,
	} = attributes;

	return {
		'has-option-element-color': optionElement || customOptionElement,
		'has-option-element-border-color':
			optionElementBorder || customOptionElementBorder,
		'has-option-element-selected-color':
			optionElementSelected || customOptionElementSelected,
	};
}
