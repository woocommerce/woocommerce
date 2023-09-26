/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface RadioBlockAttributes extends BlockAttributes {
	title: string;
	description: string;
	property: string;
	options: [];
}
