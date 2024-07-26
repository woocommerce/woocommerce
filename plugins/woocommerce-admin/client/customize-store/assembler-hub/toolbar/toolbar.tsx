/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */

import { BlockInstance } from '@wordpress/blocks';
import {
	ToolbarGroup,
	Toolbar as WPToolbar,
	Popover,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
	useContext,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';

import {
	BlockMover,
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
import { useIsNoBlocksPlaceholderPresent } from '../hooks/block-placeholder/use-is-no-blocks-placeholder-present';
import { SelectedBlockContext } from '../context/selected-block-ref-context';

const isHomepageUrl = ( path: string ) => {
	return path.includes( '/customize-store/assembler-hub/homepage' );
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

	const query = useQuery();

	useEffect( () => {
		const path = query.path;
		if ( ! path ) {
			return;
		}
		setIsHomepageSidebarOpen( isHomepageUrl( path ) );
	}, [ query ] );

	const selectedBlockClientId = currentBlock?.clientId ?? null;

	const { isBlockMoverUpButtonDisabled, isBlockMoverDownButtonDisabled } =
		useMemo( () => {
			const isPreviousBlockTemplatePart =
				previousBlock?.name === 'core/template-part';
			const isNextBlockTemplatePart =
				nextBlock?.name === 'core/template-part';

			return {
				isBlockMoverUpButtonDisabled:
					isPreviousBlockTemplatePart ||
					// The first block is the header, which is not movable.
					allBlocks[ 1 ]?.clientId === selectedBlockClientId,
				isBlockMoverDownButtonDisabled:
					isNextBlockTemplatePart ||
					// The last block is the footer, which is not movable.
					allBlocks[ allBlocks.length - 2 ]?.clientId ===
						selectedBlockClientId,
			};
		}, [
			allBlocks,
			nextBlock?.name,
			previousBlock?.name,
			selectedBlockClientId,
		] );

	const isNoBlocksPlaceholderPresent =
		useIsNoBlocksPlaceholderPresent( allBlocks );

	const isHeaderOrFooter = useMemo( () => {
		const selectedBlock = allBlocks.find( ( { clientId } ) => {
			return clientId === selectedBlockClientId;
		} );

		return selectedBlock?.name === 'core/template-part';
	}, [ allBlocks, selectedBlockClientId ] );

	const { selectedBlockRef } = useContext( SelectedBlockContext );

	const blockPopoverRef = useRef< HTMLDivElement | null >( null );

	const popoverAnchor = useMemo( () => {
		if ( ! selectedBlockRef || ! selectedBlockClientId ) {
			return undefined;
		}

		return {
			getBoundingClientRect() {
				const { top, width, height } =
					selectedBlockRef.getBoundingClientRect();

				const rect = window.document
					.querySelector(
						'.woocommerce-customize-store-assembler > iframe[name="editor-canvas"]'
					)
					?.getBoundingClientRect();

				if ( ! rect ) {
					return new window.DOMRect( 0, 0, 0, 0 );
				}

				return new window.DOMRect(
					rect?.left + 10,
					Math.max( top + 70 + rect.top, 100 ),
					width,
					height
				);
			},
		};
	}, [ selectedBlockRef, selectedBlockClientId ] );

	if (
		! isHomepageSidebarOpen ||
		! selectedBlockClientId ||
		isNoBlocksPlaceholderPresent ||
		isHeaderOrFooter ||
		! popoverAnchor
	) {
		return null;
	}

	return (
		<Popover
			as="div"
			animate={ false }
			className="components-tooltip woocommerce-customize-store_block-toolbar-popover"
			// @ts-expect-error missing type
			variant="unstyled"
			resize={ false }
			flip={ false }
			shift={ true }
			anchor={ popoverAnchor }
			placement="top-start"
			ref={ blockPopoverRef }
		>
			<div className="woocommerce-customize-store-block-toolbar">
				<WPToolbar label="Options">
					<>
						<ToolbarGroup>
							<BlockMover
								clientIds={ [ selectedBlockClientId ] }
								isBlockMoverUpButtonDisabled={
									isBlockMoverUpButtonDisabled
								}
								isBlockMoverDownButtonDisabled={
									isBlockMoverDownButtonDisabled
								}
							/>
						</ToolbarGroup>
						<Shuffle clientId={ selectedBlockClientId } />
						<Delete
							clientId={ selectedBlockClientId }
							currentBlockName={ currentBlock?.name }
							nextBlockClientId={ nextBlock?.clientId }
						/>
					</>
				</WPToolbar>
			</div>
		</Popover>
	);
};
