/**
 * Internal dependencies
 */
import { getColorCSSVar } from '../../utils/colors';
import { BlockAttributes } from './types';

export function getColorVars( attributes: BlockAttributes ) {
	const {
		optionElement,
		optionElementBorder,
		optionElementSelected,
		customOptionElement,
		customOptionElementBorder,
		customOptionElementSelected,
		labelElement,
		customLabelElement,
	} = attributes;

	const vars: Record< string, string > = {
		'--wc-product-filter-checkbox-list-option-element': getColorCSSVar(
			optionElement,
			customOptionElement
		),
		'--wc-product-filter-checkbox-list-option-element-border':
			getColorCSSVar( optionElementBorder, customOptionElementBorder ),
		'--wc-product-filter-checkbox-list-option-element-selected':
			getColorCSSVar(
				optionElementSelected,
				customOptionElementSelected
			),
		'--wc-product-filter-checkbox-list-label-element': getColorCSSVar(
			labelElement,
			customLabelElement
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
		labelElement,
		customLabelElement,
	} = attributes;

	return {
		'has-option-element-color': optionElement || customOptionElement,
		'has-option-element-border-color':
			optionElementBorder || customOptionElementBorder,
		'has-option-element-selected-color':
			optionElementSelected || customOptionElementSelected,
		'has-label-element-color': labelElement || customLabelElement,
	};
}
