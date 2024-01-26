/**
 * External dependencies
 */
import type { BlockEditProps, BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	ProductEditorBlockAttributes,
	ProductEditorBlockEditProps,
} from '../../../types';

export type TextAreaBlockEditAttributes = ProductEditorBlockAttributes & {
	align: 'left' | 'center' | 'right' | 'justify';
	allowedFormats?: string[];
	direction: 'ltr' | 'rtl';
	label: string;
	property: string;
	helpText?: string;
	placeholder?: string;
};

export type TextAreaBlockEditProps =
	ProductEditorBlockEditProps< TextAreaBlockEditAttributes >;

export interface CoreParagraphBlockEditProps
	extends BlockEditProps< ProductEditorBlockAttributes > {
	readonly name: string;
	innerBlocks: BlockInstance[];
	content: string;
}
