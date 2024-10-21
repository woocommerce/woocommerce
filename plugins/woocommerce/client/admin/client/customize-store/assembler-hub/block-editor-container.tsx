/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import {
	store as blockEditorStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';
// @ts-expect-error No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useQuery } from '@woocommerce/navigation';
// @ts-expect-error No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-expect-error No types for this exist yet.
import useSiteEditorSettings from '@wordpress/edit-site/build-module/components/block-editor/use-site-editor-settings';
import { useContext, useEffect, useMemo } from '@wordpress/element';
import { BlockInstance, createBlock } from '@wordpress/blocks';
// @ts-expect-error No types for this exist yet.
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';

/**
 * Internal dependencies
 */
import { CustomizeStoreContext } from './';
import { BlockEditor } from './block-editor';
import { HighlightedBlockContext } from './context/highlighted-block-context';
import { useAddNoBlocksPlaceholder } from './hooks/block-placeholder/use-add-no-blocks-placeholder';
import { useEditorBlocks } from './hooks/use-editor-blocks';
import { useScrollOpacity } from './hooks/use-scroll-opacity';
import {
	PRODUCT_HERO_PATTERN_BUTTON_STYLE,
	findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate,
} from './utils/black-background-pattern-update-button';
import { useIsActiveNewNeutralVariation } from './hooks/use-is-active-new-neutral-variation';

export const BlockEditorContainer = () => {
	const settings = useSiteEditorSettings();

	const currentTemplate:
		| {
				id: string;
		  }
		| undefined = useSelect(
		( select ) =>
			// @ts-expect-error No types for this exist yet.
			select( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	// This is necessary to avoid this issue: https://github.com/woocommerce/woocommerce/issues/45593
	// Related PR: https://github.com/woocommerce/woocommerce/pull/45600
	const { templateType } = useSelect( ( select ) => {
		const { getEditedPostType } = unlock( select( editSiteStore ) );

		return {
			templateType: getEditedPostType(),
		};
	}, [] );

	const [ blocks, , onChange ] = useEditorBlocks(
		templateType,
		currentTemplate?.id ?? ''
	);

	const urlParams = useQuery();
	const { currentState } = useContext( CustomizeStoreContext );

	const scrollDirection =
		urlParams.path === '/customize-store/assembler-hub/footer'
			? 'bottomUp'
			: 'topDown';

	const previewOpacity = useScrollOpacity(
		'.woocommerce-customize-store__block-editor iframe',
		scrollDirection
	);

	const { highlightedBlockClientId } = useContext( HighlightedBlockContext );
	const isHighlighting = highlightedBlockClientId !== null;
	const additionalStyles = isHighlighting
		? `
		.wp-block.preview-opacity {
			opacity: ${ previewOpacity };
		}
	`
		: '';

	const opacityClass = 'preview-opacity';

	const clientIds = blocks.map( ( block ) => block.clientId );

	// @ts-expect-error No types for this exist yet.
	const { updateBlockAttributes } = useDispatch( blockEditorStore );

	const isActiveNewNeutralVariation = useIsActiveNewNeutralVariation();

	useEffect( () => {
		if ( ! isActiveNewNeutralVariation ) {
			findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
				blocks,
				( buttonBlocks: BlockInstance[] ) => {
					const buttonBlockClientIds = buttonBlocks.map(
						( { clientId } ) => clientId
					);

					updateBlockAttributes( buttonBlockClientIds, {
						style: {},
					} );
				}
			);

			return;
		}

		findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
			blocks,
			( buttonBlocks: BlockInstance[] ) => {
				const buttonBlockClientIds = buttonBlocks.map(
					( { clientId } ) => clientId
				);
				updateBlockAttributes( buttonBlockClientIds, {
					style: PRODUCT_HERO_PATTERN_BUTTON_STYLE,
					// This is necessary; otherwise, the style won't be applied on the frontend during the style variation change.
					className: '',
				} );
			}
		);
		// Blocks are not part of the dependencies because we don't want to trigger this effect when the blocks change. This would cause an infinite loop.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ isActiveNewNeutralVariation, updateBlockAttributes ] );

	// @ts-expect-error No types for this exist yet.
	const { insertBlock, removeBlock } = useDispatch( blockEditorStore );

	useAddNoBlocksPlaceholder( {
		blocks,
		createBlock,
		insertBlock,
		removeBlock,
	} );

	useEffect( () => {
		const { blockIdToHighlight, restOfBlockIds } = clientIds.reduce(
			( acc, clientId ) => {
				if (
					! isHighlighting ||
					clientId === highlightedBlockClientId
				) {
					return {
						blockIdToHighlight: clientId,
						restOfBlockIds: acc.restOfBlockIds,
					};
				}

				return {
					blockIdToHighlight: acc.blockIdToHighlight,
					restOfBlockIds: [ ...acc.restOfBlockIds, clientId ],
				};
			},
			{
				blockIdToHighlight: null,
				restOfBlockIds: [],
			} as {
				blockIdToHighlight: string | null;
				restOfBlockIds: string[];
			}
		);

		updateBlockAttributes( blockIdToHighlight, {
			className: '',
		} );

		updateBlockAttributes( restOfBlockIds, {
			className: ` ${ opacityClass }`,
		} );
	}, [
		clientIds,
		highlightedBlockClientId,
		isHighlighting,
		updateBlockAttributes,
	] );

	const isScrollable = useMemo(
		() =>
			// Disable scrollable for transitional screen
			! (
				typeof currentState === 'object' &&
				currentState.transitionalScreen === 'transitional'
			),
		[ currentState ]
	);

	return (
		<BlockEditor
			renderedBlocks={ blocks }
			isScrollable={ isScrollable }
			onChange={ onChange }
			settings={ settings }
			additionalStyles={ additionalStyles }
		/>
	);
};
