/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export interface TextBlockAttributes extends BlockAttributes {
	property: string;
	label?: string;
	placeholder?: string;
	required: boolean;
	validationRegex?: string;
	validationErrorMessage?: string;
	isMeta: boolean;
	minLength?: number;
	maxLength?: number;
}
