/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useCallback, useMemo, useRef } from '@wordpress/element';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { BlockInstance, cloneBlock } from '@wordpress/blocks';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useEditorBlocks } from './use-editor-blocks';
import {
	findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate,
	PRODUCT_HERO_PATTERN_BUTTON_STYLE,
} from '../utils/black-background-pattern-update-button';
import { useIsActiveNewNeutralVariation } from './use-is-active-new-neutral-variation';
import { trackEvent } from '../../tracking';

export const useInsertPattern = () => {
	const isActiveNewNeutralVariation = useIsActiveNewNeutralVariation();

	const currentTemplate = useSelect(
		( sel ) =>
			// @ts-expect-error No types for this exist yet.
			sel( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	const [ blocks ] = useEditorBlocks(
		'wp_template',
		currentTemplate?.id ?? ''
	);

	const blockToScroll = useRef< string | null >( null );

	// @ts-expect-error No types for this exist yet.
	const { insertBlocks } = useDispatch( blockEditorStore );

	const insertableIndex = useMemo( () => {
		return blocks.findLastIndex(
			( block ) => block.name === 'core/template-part'
		);
	}, [ blocks ] );

	const insertPattern = useCallback(
		( pattern ) => {
			const parsedPattern = unlock(
				select( blockEditorStore )
			).__experimentalGetParsedPattern( pattern.name );

			const cloneBlocks = parsedPattern.blocks.map(
				( blockInstance: BlockInstance ) => cloneBlock( blockInstance )
			);

			if ( ! isActiveNewNeutralVariation ) {
				const updatedBlocks =
					findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
						cloneBlocks,
						( patternBlocks: BlockInstance[] ) => {
							patternBlocks.forEach(
								( block: BlockInstance ) =>
									( block.attributes.style = {} )
							);
						}
					);

				insertBlocks(
					updatedBlocks,
					insertableIndex,
					undefined,
					false
				);
				blockToScroll.current = updatedBlocks[ 0 ].clientId;
			} else {
				const updatedBlocks =
					findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
						cloneBlocks,
						( patternBlocks: BlockInstance[] ) => {
							patternBlocks.forEach(
								( block ) =>
									( block.attributes.style =
										PRODUCT_HERO_PATTERN_BUTTON_STYLE )
							);
						}
					);
				insertBlocks(
					updatedBlocks,
					insertableIndex,
					undefined,
					false
				);
				blockToScroll.current = updatedBlocks[ 0 ].clientId;
			}

			trackEvent(
				'customize_your_store_assembler_pattern_sidebar_click',
				{ pattern: pattern.name }
			);
		},
		[ insertBlocks, insertableIndex, isActiveNewNeutralVariation ]
	);

	return {
		insertPattern,
	};
};
