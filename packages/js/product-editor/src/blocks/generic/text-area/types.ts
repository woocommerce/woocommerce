/**
 * External dependencies
 */
import type { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import {
	// @ts-expect-error no exported member.
	type ComponentType,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	ProductEditorBlockAttributes,
	ProductEditorContext,
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
	BlockEditProps< TextAreaBlockEditAttributes >;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface ConnectedBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly context: ProductEditorContext & {
		readonly 'product-editor/entity-prop': string;
	};
	readonly name: string;
}

export type ConnectedBlockEditInstance =
	ConnectedBlockEditProps< BlockAttributes >;

export type ConnectedBlockEditComponent =
	ComponentType< ConnectedBlockEditInstance >;
