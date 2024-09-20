/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	showInputFields: boolean;
	inlineInput: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;
