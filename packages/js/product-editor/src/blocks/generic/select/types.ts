/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export interface SelectBlockAttributes extends BlockAttributes {
	property: string;
	label: string;
	note?: string;
	help?: string;
	tooltip?: string;
	placeholder?: string;
	disabled?: boolean;
	multiple?: boolean;
	options?: Array< { label: string; value: string } >;
}
