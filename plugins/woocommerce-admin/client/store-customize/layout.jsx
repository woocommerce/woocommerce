// Ref: https://github.com/WordPress/gutenberg/blob/release/16.0/packages/edit-site/src/components/layout/index.js

/**
 * External dependencies
 */
import classnames from 'classnames';
import { useEffect, useCallback, useState, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	useReducedMotion,
	useResizeObserver,
	useViewportMatch,
} from '@wordpress/compose';
import {
	Spinner,
	__unstableMotion as motion,
	__unstableAnimatePresence as AnimatePresence,
} from '@wordpress/components';
import { parse } from '@wordpress/blocks';

import ResizableFrame from '@wordpress/edit-site/build-module/components/resizable-frame';
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';
import { GlobalStylesProvider } from '@wordpress/edit-site/build-module/components/global-styles/global-styles-provider';
import useInitEditedEntityFromURL from '@wordpress/edit-site/build-module/components/sync-state-with-url/use-init-edited-entity-from-url';
import useEditedEntityRecord from '@wordpress/edit-site/build-module/components/use-edited-entity-record';
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
/**
 * Internal dependencies
 */
import { Editor } from './editor';

const ANIMATION_DURATION = 0.5;

const useHeaderPatterns = () => {
	const { blockPatterns } = useSelect(
		( select ) => ( {
			blockPatterns: select( coreStore ).getBlockPatterns(),
		} ),
		[]
	);

	const headerPatterns = useMemo(
		() =>
			[ ...( blockPatterns || [] ) ]
				.filter( ( pattern ) =>
					pattern.categories?.includes( 'header' )
				)
				.map( ( pattern ) => ( {
					...pattern,
					blocks: parse( pattern.content, {
						__unstableSkipMigrationLogs: true,
					} ),
				} ) ),
		[ blockPatterns ]
	);

	return {
		headerPatterns,
	};
};

export const Layout = () => {
	// This ensures the edited entity id and type are initialized properly.
	useInitEditedEntityFromURL();

	const [ blocks, setBlocks ] = useState( [] );
	const { record: template, isLoaded: hasLoadedTemplate } =
		useEditedEntityRecord();

	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const disableMotion = useReducedMotion();
	const [ canvasResizer, canvasSize ] = useResizeObserver();
	const isEditorLoading = useIsSiteEditorLoading();

	useEffect( () => {
		if ( ! hasLoadedTemplate ) {
			return;
		}
		setBlocks( parse( template.content ) );
	}, [ template, hasLoadedTemplate ] );

	const { headerPatterns } = useHeaderPatterns();

	const onClickHeaderPattern = useCallback(
		( _pattern, selectedBlocks ) => {
			const newHeaderBlock = {
				...selectedBlocks[ 0 ],
				attributes: {
					...selectedBlocks[ 0 ].attributes,
					slug: 'header',
				},
			};

			setBlocks( [
				...blocks.map( ( block ) => {
					if ( block.attributes?.slug === 'header' ) {
						return newHeaderBlock;
					}
					return block;
				} ),
			] );
		},
		[ blocks ]
	);

	if ( ! hasLoadedTemplate ) {
		return <Spinner />;
	}

	// For debugging purposes
	window.blocks = blocks;
	window.headerBlocks = headerPatterns;

	return (
		<GlobalStylesProvider>
			<div className={ classnames( 'edit-site-layout' ) }>
				<div className="edit-site-layout__content">
					<AnimatePresence initial={ false }>
						<motion.div
							initial={ {
								opacity: 0,
							} }
							animate={ {
								opacity: 1,
							} }
							exit={ {
								opacity: 0,
							} }
							transition={ {
								type: 'tween',
								duration: ANIMATION_DURATION,
								ease: 'easeOut',
							} }
							className="edit-site-layout__sidebar"
						>
							<div className="woocommerce-store-customize__block-pattern-list">
								<BlockPatternList
									shownPatterns={ headerPatterns }
									blockPatterns={ headerPatterns }
									onClickPattern={ onClickHeaderPattern }
									label={ 'Headers' }
									orientation="vertical"
									category={ 'header' }
									isDraggable={ false }
									showTitlesAsTooltip={ true }
								/>
							</div>
						</motion.div>
					</AnimatePresence>
					{ ! isMobileViewport && (
						<div
							className={ classnames(
								'edit-site-layout__canvas-container'
							) }
						>
							{ canvasResizer }
							{ !! canvasSize.width && (
								<motion.div
									whileHover={ {
										scale: 1.005,
										transition: {
											duration: disableMotion ? 0 : 0.5,
											ease: 'easeOut',
										},
									} }
									initial={ false }
									layout="position"
									className="edit-site-layout__canvas"
									transition={ {
										type: 'tween',
										duration: disableMotion
											? 0
											: ANIMATION_DURATION,
										ease: 'easeOut',
									} }
								>
									<ResizableFrame
										isReady={ ! isEditorLoading }
										isFullWidth={ false }
										oversizedClassName="edit-site-layout__resizable-frame-oversized"
									>
										<Editor
											blocks={ blocks }
											isLoading={ isEditorLoading }
										/>
									</ResizableFrame>
								</motion.div>
							) }
						</div>
					) }
				</div>
			</div>
		</GlobalStylesProvider>
	);
};
