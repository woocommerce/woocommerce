/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { Color, SelectableItemsBlockContext } from '../../types';

export type BlockAttributes = {
	className: string;
	optionElementBorder: string;
	customOptionElementBorder: string;
	optionElementSelected: string;
	customOptionElementSelected: string;
	optionElement: string;
	customOptionElement: string;
	labelElement: string;
	customLabelElement: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: SelectableItemsBlockContext;
	optionElementBorder: Color;
	setOptionElementBorder: ( value: string ) => void;
	optionElementSelected: Color;
	setOptionElementSelected: ( value: string ) => void;
	optionElement: Color;
	setOptionElement: ( value: string ) => void;
	labelElement: Color;
	setLabelElement: ( value: string ) => void;
};
