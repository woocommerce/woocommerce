/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface ToggleBlockAttributes extends BlockAttributes {
	label: string;
	property: string;
}
