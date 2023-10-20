/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export interface TextBlockAttributes extends BlockAttributes {
	property: string;
	label: string;
	help?: string;
	tooltip?: string;
	placeholder?: string;
	required?: boolean;
	validationRegex?: string;
	validationErrorMessage?: string;
	minLength?: number;
	maxLength?: number;
}
