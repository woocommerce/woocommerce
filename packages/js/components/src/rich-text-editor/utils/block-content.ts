/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

export const getBlockContent = ( block: BlockInstance ) => {
	if ( block.name === 'core/list' ) {
		return block.attributes.values;
	}

	if ( block.name === 'core/quote' || block.name === 'core/pullquote' ) {
		return block.attributes.value;
	}

	return ( block as BlockInstance ).attributes.content;
};
