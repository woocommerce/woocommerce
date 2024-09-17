/**
 * External dependencies
 */
import type { EditorBlock } from '@woocommerce/types';

export type ButtonPositionProps =
	| 'button-outside'
	| 'button-inside'
	| 'no-button'
	| 'button-only';

export interface SearchBlockAttributes {
	buttonPosition: ButtonPositionProps;
	buttonText?: string;
	buttonUseIcon: boolean;
	isSearchFieldHidden: boolean;
	label?: string;
	namespace?: string;
	placeholder?: string;
	showLabel: boolean;
}

export type ProductSearchBlockProps = EditorBlock< SearchBlockAttributes >;
