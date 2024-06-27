/**
 * External dependencies
 */
import { useCallback, useMemo, useRef, useState } from '@wordpress/element';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { BlockInstance, cloneBlock } from '@wordpress/blocks';
import { close } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { capitalize } from 'lodash';
import { Button, Spinner } from '@wordpress/components';
import {
	unlock,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/edit-site/build-module/lock-unlock';

// eslint-disable-next-line @woocommerce/dependency-group
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
import { THEME_SLUG } from '~/customize-store/data/constants';
import { Pattern } from '~/customize-store/types/pattern';

/**
 * Sorts patterns by category. For 'intro' and 'about' categories
 * priorizied DotCom Patterns. For intro category, it also prioritizes the "centered-content-with-image-below" pattern.
 * For other categories, it simply sorts patterns to prioritize Woo Patterns.
 */
const sortByCategory =
	( category: keyof typeof PATTERN_CATEGORIES ) =>
	( patterns: Pattern[] ) => {
		const prefix = 'woocommerce-blocks';
		if ( category === 'intro' || category === 'about' ) {
			return patterns.sort( ( a, b ) => {
				if (
					a.name ===
					'woocommerce-blocks/centered-content-with-image-below'
				) {
					return -1;
				}

				if (
					b.name ===
					'woocommerce-blocks/centered-content-with-image-below'
				) {
					return 1;
				}

				if (
					a.name.includes( prefix ) &&
					! b.name.includes( prefix )
				) {
					return 1;
				}
				if (
					! a.name.includes( prefix ) &&
					b.name.includes( prefix )
				) {
					return -1;
				}
				return 0;
			} );
		}

		return patterns.sort( ( a, b ) => {
			if ( a.name.includes( prefix ) && ! b.name.includes( prefix ) ) {
				return -1;
			}
			if ( ! a.name.includes( prefix ) && b.name.includes( prefix ) ) {
				return 1;
			}
			return 0;
		} );
	};

export const SidebarPatternScreen = ( { category }: { category: string } ) => {
	const { patterns, isLoading } = usePatternsByCategory( category );

	const sortedPatterns = useMemo( () => {
		const patternsWithoutThemePatterns = patterns.filter(
			( pattern ) => ! pattern.name.includes( THEME_SLUG )
		);

		return sortByCategory( category as keyof typeof PATTERN_CATEGORIES )(
			patternsWithoutThemePatterns
		);
	}, [ category, patterns ] );

	const [ patternPagination, setPatternPagination ] = useState( 10 );

	const refElement = useRef< HTMLDivElement >( null );

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

	// @ts-expect-error No types for this exist yet.
	const { selectBlock, insertBlocks } = useDispatch( blockEditorStore );

	const insertableIndex = useMemo( () => {
		return blocks.findLastIndex(
			( block ) => block.name === 'core/template-part'
		);
	}, [ blocks ] );

	const onClickPattern = useCallback(
		( pattern ) => {
			const parsedPattern = unlock(
				select( blockEditorStore )
			).__experimentalGetParsedPattern( pattern.name );

			const cloneBlocks = parsedPattern.blocks.map(
				( blockInstance: BlockInstance ) => cloneBlock( blockInstance )
			);

			insertBlocks( cloneBlocks, insertableIndex );

			selectBlock( cloneBlocks.blocks[ 0 ].clientId, -1 );
		},
		[ insertBlocks, insertableIndex, selectBlock ]
	);

	return (
		<div
			className="woocommerce-customize-store-edit-site-layout__sidebar-extra__pattern"
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
			<div className="woocommerce-customize-store-edit-site-layout__sidebar-extra__pattern__header">
				<h1>
					{ capitalize(
						PATTERN_CATEGORIES[
							category as keyof typeof PATTERN_CATEGORIES
						].label
					) }
				</h1>
				<Button
					onClick={ () => {
						const homepageUrl = getNewPath(
							{ customizing: true },
							`/customize-store/assembler-hub/homepage`,
							{}
						);

						navigateTo( { url: homepageUrl } );
					} }
					iconSize={ 22 }
					icon={ close }
					label={ __( 'Close', 'woocommerce' ) }
				/>
			</div>
			<div className="woocommerce-customize-store-edit-site-layout__sidebar-extra__pattern__description">
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
					shownPatterns={ sortedPatterns.slice(
						0,
						patternPagination
					) }
					blockPatterns={ sortedPatterns.slice(
						0,
						patternPagination
					) }
					onClickPattern={ onClickPattern }
					label={ 'Homepage' }
					orientation="vertical"
					category={ category }
					isDraggable={ false }
					showTitlesAsTooltip={ true }
					ref={ refElement }
				/>
			) }
		</div>
	);
};
