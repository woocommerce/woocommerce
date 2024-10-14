/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	displayStyle: string;
};

export type EditProps = BlockEditProps< BlockAttributes >;
