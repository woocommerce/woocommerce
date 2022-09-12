/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const useBlockSelection = () => {
	return useSelect( ( select ) => {
		const {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this exist yet.
			getSelectedBlockClientIds,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this exist yet.
			getPreviousBlockClientId,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this exist yet.
			getNextBlockClientId,
		} = select( blockEditorStore );
		const selectedBlockClientIds = getSelectedBlockClientIds();

		const blocks = select( blockEditorStore )
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this exist yet.
			.getBlocksByClientId( selectedBlockClientIds )
			.filter( ( block: BlockInstance ) => !! block ) as BlockInstance[];

		return {
			blockClientIds: selectedBlockClientIds,
			blocks,
			getPreviousBlockClientId,
			getNextBlockClientId,
		};
	}, [] );
};
