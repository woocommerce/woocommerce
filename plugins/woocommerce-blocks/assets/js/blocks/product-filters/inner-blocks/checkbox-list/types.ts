/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { FilterBlockContext } from '../../types';

export type Color = {
	slug: string;
	class: string;
	name: string;
	color: string;
};

export type BlockAttributes = {
	className: string;
	optionElementBorder: string;
	customOptionElementBorder: string;
	optionElementSelected: string;
	customOptionElementSelected: string;
	optionElement: string;
	customOptionElement: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: FilterBlockContext;
	optionElementBorder: Color;
	setOptionElementBorder: ( value: string ) => void;
	optionElementSelected: Color;
	setOptionElementSelected: ( value: string ) => void;
	optionElement: Color;
	setOptionElement: ( value: string ) => void;
};
