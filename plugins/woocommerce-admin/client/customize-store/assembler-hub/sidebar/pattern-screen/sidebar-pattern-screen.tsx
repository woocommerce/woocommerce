/**
 * External dependencies
 */
import { useCallback, useRef, useState } from '@wordpress/element';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { BlockInstance } from '@wordpress/blocks';
import { capitalize } from 'lodash';
import { Spinner } from '@wordpress/components';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

import {
	store as coreStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/core-data';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	__experimentalBlockPatternsList as BlockPatternList,
	store as blockEditorStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { usePatternsByCategory } from '../../hooks/use-patterns';
import './style.scss';
import { useEditorBlocks } from '../../hooks/use-editor-blocks';
import { PATTERN_CATEGORIES } from './categories';

export const SidebarPatternScreen = ( { category }: { category: string } ) => {
	const { patterns, isLoading } = usePatternsByCategory( category );

	const [ patternPagination, setPatternPagination ] = useState( 10 );

	const refElement = useRef< HTMLDivElement >( null );

	const currentTemplate = useSelect(
		( sel ) =>
			// @ts-expect-error No types for this exist yet.
			sel( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	const [ blocks, , onChange ] = useEditorBlocks(
		'wp_template',
		currentTemplate?.id ?? ''
	);

	// @ts-expect-error No types for this exist yet.
	const { selectBlock } = useDispatch( blockEditorStore );

	const { header, footer, allBlocksExceptHeaderFooter } = blocks.reduce(
		( acc, block ) => {
			const blockName = block.name;

			if ( blockName === 'core/template-part' ) {
				if ( acc.header.length === 0 ) {
					return {
						...acc,
						header: [ block ],
					};
				}
				return {
					...acc,
					footer: [ block ],
				};
			}

			return {
				...acc,
				allBlocksExceptHeaderFooter: [
					...acc.allBlocksExceptHeaderFooter,
					block,
				],
			};
		},
		{
			header: [] as BlockInstance[],
			footer: [] as BlockInstance[],
			allBlocksExceptHeaderFooter: [] as BlockInstance[],
		}
	);

	const onClickPattern = useCallback(
		( pattern, selectedBlocks ) => {
			const parsedPattern = unlock(
				select( blockEditorStore )
			).__experimentalGetParsedPattern( pattern.name );

			selectBlock( parsedPattern.blocks[ 0 ].clientId );
			onChange(
				[
					...header,
					...allBlocksExceptHeaderFooter,
					...selectedBlocks,
					...footer,
				],
				{ selection: {} }
			);
		},
		[ selectBlock, onChange, header, allBlocksExceptHeaderFooter, footer ]
	);

	return (
		<div
			className="edit-site-layout__sidebar-extra__pattern"
			onScroll={ ( event ) => {
				const element = event.target as HTMLElement;
				const scrollTop = element.scrollTop;
				const percentage =
					scrollTop / ( element.scrollHeight - element.clientHeight );

				if ( percentage > 0.5 ) {
					setPatternPagination( ( prev ) => prev + 10 );
				}
			} }
		>
			<div className="edit-site-layout__sidebar-extra__pattern__header">
				<h1>
					{ capitalize(
						PATTERN_CATEGORIES[
							category as keyof typeof PATTERN_CATEGORIES
						].label
					) }
				</h1>
			</div>
			<div className="edit-site-layout__sidebar-extra__pattern__description">
				<span>
					{
						PATTERN_CATEGORIES[
							category as keyof typeof PATTERN_CATEGORIES
						].description
					}
				</span>
			</div>
			{ isLoading && (
				<span className="components-placeholder__preview">
					<Spinner />
				</span>
			) }
			{ ! isLoading && (
				<BlockPatternList
					shownPatterns={ patterns.slice( 0, patternPagination ) }
					blockPatterns={ patterns.slice( 0, patternPagination ) }
					onClickPattern={ onClickPattern }
					label={ 'Homepage' }
					orientation="vertical"
					category={ category }
					isDraggable={ false }
					showTitlesAsTooltip={ false }
					ref={ refElement }
				/>
			) }
		</div>
	);
};
