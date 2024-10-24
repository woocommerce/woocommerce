/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	clearButton: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;
