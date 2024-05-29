/**
 * Internal dependencies
 */
import {
	ProductEditorBlockAttributes,
	ProductEditorBlockEditProps,
} from '../../../types';

type AllowedFormat =
	| 'core/bold'
	| 'core/code'
	| 'core/italic'
	| 'core/link'
	| 'core/strikethrough'
	| 'core/underline'
	| 'core/text-color'
	| 'core/subscript'
	| 'core/superscript'
	| 'core/unknown';

export type TextAreaBlockEditAttributes = ProductEditorBlockAttributes & {
	property: string;
	label?: string;
	placeholder?: string;
	help?: string;
	required?: boolean;
	disabled?: boolean;
	align?: 'left' | 'center' | 'right' | 'justify';
	allowedFormats?: AllowedFormat[];
	direction?: 'ltr' | 'rtl';
	mode: 'plain-text' | 'rich-text';
};

export type TextAreaBlockEditProps =
	ProductEditorBlockEditProps< TextAreaBlockEditAttributes >;
