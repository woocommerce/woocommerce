/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';

export const useBlockSelection = () => {
	return useSelect( ( select ) => {
		const {
			getSelectedBlockClientIds,
			getPreviousBlockClientId,
			getNextBlockClientId,
		} = select( 'core/block-editor' );
		const selectedBlockClientIds = getSelectedBlockClientIds();

		const blocks = select( 'core/block-editor' )
			.getBlocksByClientId( selectedBlockClientIds )
			.filter( ( block ) => !! block ) as BlockInstance[];

		return {
			blockClientIds: selectedBlockClientIds,
			blocks,
			getPreviousBlockClientId,
			getNextBlockClientId,
		};
	}, [] );
};
