/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface SubsectionBlockAttributes extends BlockAttributes {
	title?: string;
	description?: string;
	blockGap: 'lg' | '2xlg';
}
