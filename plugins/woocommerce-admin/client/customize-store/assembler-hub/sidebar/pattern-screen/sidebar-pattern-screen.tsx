/**
 * External dependencies
 */
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { useAsyncList } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { BlockInstance } from '@wordpress/blocks';
import { close } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { capitalize } from 'lodash';
import { Button, Spinner } from '@wordpress/components';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	store as coreStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/core-data';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	__experimentalBlockPatternsList as BlockPatternList,
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
import {
	findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate,
	PRODUCT_HERO_PATTERN_BUTTON_STYLE,
} from '../../utils/black-background-pattern-update-button';
import { useIsActiveNewNeutralVariation } from '../../hooks/use-is-active-new-neutral-variation';
import {
	sortPatternsByCategory,
	addIsAddedClassToPatternPreview,
} from './utils';
import { trackEvent } from '~/customize-store/tracking';
import { useInsertPattern } from '../../hooks/use-insert-pattern';

export const SidebarPatternScreen = ( { category }: { category: string } ) => {
	const { patterns, isLoading } = usePatternsByCategory( category );

	const isActiveNewNeutralVariation = useIsActiveNewNeutralVariation();
	const sortedPatterns = useMemo( () => {
		const patternsWithoutThemePatterns = patterns.filter(
			( pattern ) =>
				! pattern.name.includes( THEME_SLUG ) &&
				pattern.source !== 'pattern-directory/theme' &&
				pattern.source !== 'pattern-directory/core'
		);

		const patternWithPatchedProductHeroPattern =
			patternsWithoutThemePatterns.map( ( pattern ) => {
				if (
					pattern.name !==
						'woocommerce-blocks/just-arrived-full-hero' &&
					pattern.name !==
						'woocommerce-blocks/featured-category-cover-image'
				) {
					return pattern;
				}

				if ( ! isActiveNewNeutralVariation ) {
					const blocks =
						findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
							pattern.blocks,
							( patternBlocks: BlockInstance[] ) => {
								patternBlocks.forEach(
									( block: BlockInstance ) =>
										( block.attributes.style = {} )
								);
							}
						);
					return { ...pattern, blocks };
				}

				const blocks =
					findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
						pattern.blocks,
						( patternBlocks: BlockInstance[] ) => {
							patternBlocks.forEach(
								( block ) =>
									( block.attributes.style =
										PRODUCT_HERO_PATTERN_BUTTON_STYLE )
							);
						}
					);

				return { ...pattern, blocks };
			} );

		return sortPatternsByCategory(
			patternWithPatchedProductHeroPattern,
			category as keyof typeof PATTERN_CATEGORIES
		);
	}, [ category, isActiveNewNeutralVariation, patterns ] );

	const asyncSortedPatterns = useAsyncList( sortedPatterns );

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

	const blockToScroll = useRef< string | null >( null );

	const isEditorLoading = useIsSiteEditorLoading();

	const isSpinnerVisible = isLoading || isEditorLoading;

	useEffect( () => {
		if ( isSpinnerVisible || refElement.current === null ) {
			return;
		}

		// We want to add the is-added class to the pattern preview when the pattern is loaded in the editor and for each mutation.
		addIsAddedClassToPatternPreview( refElement.current, blocks );

		const observer = new MutationObserver( () => {
			addIsAddedClassToPatternPreview(
				refElement.current as HTMLElement,
				blocks
			);
		} );

		const previewPatternList = document.querySelector(
			'.woocommerce-customize-store-edit-site-layout__sidebar-extra__pattern .block-editor-block-patterns-list'
		);

		if ( previewPatternList ) {
			observer.observe( previewPatternList, {
				childList: true,
			} );
		}

		return () => {
			observer.disconnect();
		};
	}, [ isLoading, blocks, isSpinnerVisible ] );

	useEffect( () => {
		if ( isEditorLoading ) {
			return;
		}
		const iframe = window.document.querySelector(
			'.woocommerce-customize-store-assembler > iframe[name="editor-canvas"]'
		) as HTMLIFrameElement;

		const blockList = iframe?.contentWindow?.document.body.querySelector(
			'.block-editor-block-list__layout'
		);

		const observer = new MutationObserver( () => {
			if ( blockToScroll.current ) {
				const block = blockList?.querySelector(
					`[id="block-${ blockToScroll.current }"]`
				);

				if ( block ) {
					block.scrollIntoView();
					blockToScroll.current = null;
				}
			}
		} );

		if ( blockList ) {
			observer.observe( blockList, { childList: true } );
		}

		return () => {
			observer.disconnect();
		};
	}, [ isEditorLoading ] );

	const { insertPattern } = useInsertPattern();

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
						trackEvent(
							'customize_your_store_assembler_pattern_sidebar_close'
						);
					} }
					iconSize={ 18 }
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
			{ isSpinnerVisible && (
				<span className="components-placeholder__preview">
					<Spinner />
				</span>
			) }
			{ ! isSpinnerVisible && (
				<BlockPatternList
					shownPatterns={ asyncSortedPatterns.slice(
						0,
						patternPagination
					) }
					blockPatterns={ asyncSortedPatterns.slice(
						0,
						patternPagination
					) }
					onClickPattern={ insertPattern }
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
