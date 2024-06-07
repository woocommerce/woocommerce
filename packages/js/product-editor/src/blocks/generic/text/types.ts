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
	suffix?: boolean | string;
	required?: boolean | string;
	type?: { value?: HTMLInputTypeAttribute; message?: string };
	pattern?: { value: string; message?: string };
	minLength?: { value: number; message?: string };
	maxLength?: { value: number; message?: string };
	min?: { value: number; message?: string };
	max?: { value: number; message?: string };
}
