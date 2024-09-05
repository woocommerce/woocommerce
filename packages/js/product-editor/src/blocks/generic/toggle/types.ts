/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface ToggleBlockAttributes extends BlockAttributes {
	label: string;
	property: string;
	disabled?: boolean;
	checkedValue?: never;
	uncheckedValue?: never;
}
