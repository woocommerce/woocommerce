/**
 * External dependencies
 */
import { useCallback, useMemo, useRef } from '@wordpress/element';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { BlockInstance, cloneBlock } from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';

// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

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
import { Pattern } from '~/customize-store/types/pattern';

export const useInsertPattern = () => {
	const isActiveNewNeutralVariation = useIsActiveNewNeutralVariation();

	const currentTemplateId: string | undefined = useSelect(
		( sel ) => sel( coreStore ).getDefaultTemplateId( { slug: 'home' } ),
		[]
	);

	const [ blocks ] = useEditorBlocks(
		'wp_template',
		currentTemplateId || ''
	);

	const insertedPatternRef = useRef< string | null >( null );

	const { insertBlocks } = useDispatch( blockEditorStore );

	const insertableIndex = useMemo( () => {
		return blocks.findLastIndex(
			( block ) => block.name === 'core/template-part'
		);
	}, [ blocks ] );

	const insertPattern = useCallback(
		( pattern: Pattern ) => {
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
				insertedPatternRef.current = updatedBlocks[ 0 ].clientId;
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
				insertedPatternRef.current = updatedBlocks[ 0 ].clientId;
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
		insertedPattern: insertedPatternRef,
	};
};
