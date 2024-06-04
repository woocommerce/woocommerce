/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */

import { BlockInstance } from '@wordpress/blocks';
import { ToolbarGroup, Toolbar as WPToolbar } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';

import {
	BlockMover,
	BlockPopover,
	store as blockEditorStore,
	// @ts-expect-error missing type
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useQuery } from '@woocommerce/navigation';
import Shuffle from './shuffle';
import Delete from './delete';
import './style.scss';

const isHomepageUrl = ( path: string ) => {
	return path === '/customize-store/assembler-hub/homepage';
};

export const Toolbar = () => {
	const [ isHomepageSidebarOpen, setIsHomepageSidebarOpen ] =
		useState( false );

	const {
		currentBlock,
		nextBlock,
		previousBlock,
		allBlocks,
	}: {
		currentBlock: BlockInstance | undefined;
		nextBlock: BlockInstance | undefined;
		previousBlock: BlockInstance | undefined;
		allBlocks: BlockInstance[];
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

		const blocks = select(
			blockEditorStore
			// @ts-expect-error missing type
		).getBlocks();

		return {
			currentBlock: current,
			nextBlock: next,
			previousBlock: previous,
			allBlocks: blocks,
		};
	}, [] );

	const firstBlock = useMemo( () => {
		return allBlocks.find(
			( block: BlockInstance ) => block.name !== 'core/template-part'
		);
	}, [ allBlocks ] );

	const query = useQuery();

	useEffect( () => {
		const path = query.path;

		setIsHomepageSidebarOpen( isHomepageUrl( path ) );
	}, [ query ] );
	const { isPreviousBlockTemplatePart, isNextBlockTemplatePart } =
		useMemo( () => {
			return {
				isPreviousBlockTemplatePart:
					previousBlock?.name === 'core/template-part',
				isNextBlockTemplatePart:
					nextBlock?.name === 'core/template-part',
			};
		}, [ nextBlock?.name, previousBlock?.name ] );

	const selectedBlockClientId =
		currentBlock?.clientId ?? firstBlock?.clientId;

	if ( ! isHomepageSidebarOpen || ! selectedBlockClientId ) {
		return null;
	}

	return (
		<BlockPopover clientId={ selectedBlockClientId }>
			<div className="woocommerce-customize-store-block-toolbar">
				<WPToolbar label="Options">
					<>
						<ToolbarGroup>
							<BlockMover
								clientIds={ [ selectedBlockClientId ] }
								isBlockMoverUpButtonDisabled={
									isPreviousBlockTemplatePart
								}
								isBlockMoverDownButtonDisabled={
									isNextBlockTemplatePart
								}
							/>
						</ToolbarGroup>
						<Shuffle clientId={ selectedBlockClientId } />
						<Delete clientId={ selectedBlockClientId } />
					</>
				</WPToolbar>
			</div>
		</BlockPopover>
	);
};
