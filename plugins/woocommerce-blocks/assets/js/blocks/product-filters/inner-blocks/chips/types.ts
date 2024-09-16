/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */

export type BlockAttributes = {
	className: string;
};

export type EditProps = BlockEditProps< BlockAttributes >;
