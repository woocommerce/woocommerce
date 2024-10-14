/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { noBlocksPlaceholderClientId } from './use-add-no-blocks-placeholder';

export const useIsNoBlocksPlaceholderPresent = ( blocks: BlockInstance[] ) => {
	return (
		blocks.find(
			( block ) => block.clientId === noBlocksPlaceholderClientId
		) !== undefined
	);
};
