/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { SelectControl } from '@wordpress/components';

export interface SelectBlockAttributes extends BlockAttributes {
	property: string;
	label: string;
	help?: string;
	placeholder?: string;
	disabled?: boolean;
	multiple?: boolean;
	options?: SelectControl.Option[];
}
