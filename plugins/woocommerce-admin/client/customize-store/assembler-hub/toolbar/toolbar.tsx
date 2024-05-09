/**
 * External dependencies
 */

import { useSelect } from '@wordpress/data';
import { BlockInstance } from '@wordpress/blocks';
import { ToolbarGroup, Toolbar as WPToolbar } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import {
	BlockMover,
	BlockPopover,
	store as blockEditorStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';

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
			// @ts-expect-error missing type
			select( blockEditorStore ).getSelectedBlockClientId();
		const nextBlockClientId =
			// @ts-expect-error missing type
			select( blockEditorStore ).getNextBlockClientId();
		const previousBlockClientId =
			// @ts-expect-error missing type
			select( blockEditorStore ).getPreviousBlockClientId();

		// @ts-expect-error missing type
		const [ current ] = select( blockEditorStore ).getBlocksByClientId( [
			selectedBlockId,
		] );

		// @ts-expect-error missing type
		const [ next ] = select( blockEditorStore ).getBlocksByClientId( [
			nextBlockClientId,
		] );

		const [ previous ] = select(
			blockEditorStore
			// @ts-expect-error missing type
		).getBlocksByClientId( [ previousBlockClientId ] );

		return {
			currentBlock: current,
			nextBlock: next,
			previousBlock: previous,
		};
	}, [] );

	const { isPreviousBlockTemplatePart, isNextBlockTemplatePart } =
		useMemo( () => {
			return {
				isPreviousBlockTemplatePart:
					previousBlock?.name === 'core/template-part',
				isNextBlockTemplatePart:
					nextBlock?.name === 'core/template-part',
			};
		}, [ nextBlock?.name, previousBlock?.name ] );

	return (
		<BlockPopover clientId={ currentBlock?.clientId }>
			<div className="woocommerce-customize-store-block-toolbar">
				<WPToolbar label="Options">
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
