/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface SectionBlockAttributes extends BlockAttributes {
	title: string;
	description: string;
}
