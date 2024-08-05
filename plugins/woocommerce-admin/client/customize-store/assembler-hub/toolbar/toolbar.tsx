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
import { isFullComposabilityFeatureAndAPIAvailable } from '../utils/is-full-composability-enabled';

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

	// Note: This feature is only available when the full composability feature flag is enabled.
	const isEligableForZoomOutFeature =
		isFullComposabilityFeatureAndAPIAvailable();

	const popoverAnchor = useMemo( () => {
		if ( ! selectedBlockRef || ! selectedBlockClientId ) {
			return undefined;
		}

		return {
			getBoundingClientRect() {
				const { top, width, height } =
					selectedBlockRef.getBoundingClientRect();

				const iframe = window.document.querySelector(
					'.woocommerce-customize-store-assembler > .block-editor-iframe__container iframe[name="editor-canvas"]'
				);
				const iframeHtmlElement =
					// @ts-expect-error missing type
					iframe?.contentDocument?.documentElement;
				const iframeRect = iframe?.getBoundingClientRect();
				const iframeHtmlElementRect =
					iframeHtmlElement?.getBoundingClientRect();

				const isZoomedOut =
					isEligableForZoomOutFeature &&
					iframeHtmlElement?.classList.contains( 'is-zoomed-out' );

				if ( ! iframeRect ) {
					return new window.DOMRect( 0, 0, 0, 0 );
				}

				// Here we need to account for when the iframe is zoomed out as the width changes.
				const rectLeft =
					isZoomedOut && iframeHtmlElementRect
						? iframeRect?.left + iframeHtmlElementRect.left + 60
						: iframeRect?.left + 10;

				// Here we need to account for when the zoom out feature is eligible because a toolbar is added to the top of the iframe.
				const rectTop = isEligableForZoomOutFeature
					? Math.max( top + 70 + iframeRect.top, 140 )
					: Math.max( top + 70 + iframeRect.top, 100 );

				return new window.DOMRect( rectLeft, rectTop, width, height );
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
