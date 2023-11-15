/**
 * External dependencies
 */
import type { HTMLInputTypeAttribute } from 'react';
import type { BlockAttributes } from '@wordpress/blocks';

export interface TextBlockAttributes extends BlockAttributes {
	property: string;
	label: string;
	help?: string;
	tooltip?: string;
	placeholder?: string;
	type?: HTMLInputTypeAttribute;
	suffix?: boolean | string;
	required?: boolean | string;
	validationRegex?: string;
	validationErrorMessage?: string;
	minLength?: number;
	maxLength?: number;
}
