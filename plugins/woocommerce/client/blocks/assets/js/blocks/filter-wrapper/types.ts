/**
 * External dependencies
 */
import { type BlockEditProps } from '@wordpress/blocks';

export type Attributes = {
	heading: string;
	filterType: string;
};

export type EditProps = BlockEditProps< Attributes >;
