/**
 * External dependencies
 */
import {
	BlockMover,
	BlockPopover,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { ToolbarGroup, Toolbar as WPToolbar } from '@wordpress/components';
import { useMemo } from 'react';

export const Toolbar = () => {
	const {
		currentBlock,
		nextBlock,
		previousBlock,
	}: {
		currentBlock: BlockInstance | undefined;
		nextBlock: BlockInstance | undefined;
		previousBlock: BlockInstance | undefined;
	} = useSelect( ( select ) => {
		const selectedBlockId =
			select( blockEditorStore ).getSelectedBlockClientId();
		const nextBlockClientId =
			select( blockEditorStore ).getNextBlockClientId();
		const previousBlockClientId =
			select( blockEditorStore ).getPreviousBlockClientId();

		const [ currentBlock ] = select( blockEditorStore ).getBlocksByClientId(
			[ selectedBlockId ]
		);
		const [ nextBlock ] = select( blockEditorStore ).getBlocksByClientId( [
			nextBlockClientId,
		] );
		const [ previousBlock ] = select(
			blockEditorStore
		).getBlocksByClientId( [ previousBlockClientId ] );

		return {
			currentBlock,
			nextBlock,
			previousBlock,
		};
	}, [] );

	const isPreviousBlockTemplatePart = useMemo( () => {
		return previousBlock?.name === 'core/template-part';
	}, [ previousBlock ] );

	const isNextBlockTemplatePart = useMemo( () => {
		return nextBlock?.name === 'core/template-part';
	}, [ nextBlock ] );

	return (
		<BlockPopover clientId={ currentBlock?.clientId }>
			<div>
				<WPToolbar id="options-toolbar" label="Options">
					<>
						<ToolbarGroup>
							<BlockMover
								clientIds={ [ currentBlock?.clientId ] }
								isBlockMoverUpButtonDisabled={
									isPreviousBlockTemplatePart
								}
								isBlockMoverDownButtonDisabled={
									isNextBlockTemplatePart
								}
							/>
						</ToolbarGroup>
					</>
				</WPToolbar>
			</div>
		</BlockPopover>
	);
};
