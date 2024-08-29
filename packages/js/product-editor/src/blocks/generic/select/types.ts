/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { SelectControl } from '@wordpress/components';

export interface SelectBlockAttributes extends BlockAttributes {
	property: string;
	label: string;
	note?: string;
	help?: string;
	tooltip?: string;
	placeholder?: string;
	disabled?: boolean;
	multiple?: boolean;
	options?: SelectControl.Option[];
}
