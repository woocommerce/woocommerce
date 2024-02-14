/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	displayStyle: 'list' | 'chips';
};

export type EditProps = BlockEditProps< BlockAttributes >;
