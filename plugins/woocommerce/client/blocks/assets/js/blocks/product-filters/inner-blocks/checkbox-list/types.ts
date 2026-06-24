/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { SelectableItemsBlockContext } from '../../../../types/type-defs/selectable-items';
import type { VisualAttributeTerm } from '../../../../base/utils/visual-attribute-terms';

export type Color = {
	slug?: string;
	name?: string;
	class?: string;
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
	labelElement: string;
	customLabelElement: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: SelectableItemsBlockContext< {
		count?: number;
		visual?: VisualAttributeTerm;
		depth?: number;
	} >;
	optionElementBorder: Color;
	setOptionElementBorder: ( value: string ) => void;
	optionElementSelected: Color;
	setOptionElementSelected: ( value: string ) => void;
	optionElement: Color;
	setOptionElement: ( value: string ) => void;
	labelElement: Color;
	setLabelElement: ( value: string ) => void;
};
