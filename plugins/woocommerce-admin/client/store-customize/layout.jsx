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
import { __unstableMotion as motion } from '@wordpress/components';
import { parse } from '@wordpress/blocks';

import ResizableFrame from '@wordpress/edit-site/build-module/components/resizable-frame';
import {
	__experimentalBlockPatternsList as BlockPatternList,
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';
import useInitEditedEntityFromURL from '@wordpress/edit-site/build-module/components/sync-state-with-url/use-init-edited-entity-from-url';
import useEditedEntityRecord from '@wordpress/edit-site/build-module/components/use-edited-entity-record';
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
import ErrorBoundary from '@wordpress/edit-site/build-module/components/error-boundary';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import { Editor } from './editor';

const { useGlobalStyle } = unlock( blockEditorPrivateApis );

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
	const [ selectedHeader, setSelectedHeader ] = useState( null );
	const { record: template, isLoaded: hasLoadedTemplate } =
		useEditedEntityRecord();

	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const disableMotion = useReducedMotion();
	const [ canvasResizer, canvasSize ] = useResizeObserver();
	const isEditorLoading = useIsSiteEditorLoading();
	const [ isResizableFrameOversized, setIsResizableFrameOversized ] =
		useState( false );
	const [ backgroundColor ] = useGlobalStyle( 'color.background' );
	const [ gradientValue ] = useGlobalStyle( 'color.gradient' );

	useEffect( () => {
		if ( ! hasLoadedTemplate ) {
			return;
		}
		setBlocks( parse( template.content ) );
	}, [ template, hasLoadedTemplate ] );

	const { headerPatterns } = useHeaderPatterns();

	const onClickHeaderPattern = useCallback( ( _pattern, selectedBlocks ) => {
		setSelectedHeader( {
			...selectedBlocks[ 0 ],
			attributes: {
				...selectedBlocks[ 0 ].attributes,
				slug: 'header',
			},
		} );
	}, [] );
	const renderedBlocks = useMemo( () => {
		if ( selectedHeader ) {
			return [ selectedHeader, ...blocks.slice( 1 ) ];
		}
		return blocks;
	}, [ blocks, selectedHeader ] );

	// For debugging purposes
	window.blocks = blocks;
	window.headerBlocks = headerPatterns;
	window.renderedBlocks = renderedBlocks;

	return (
		<div className={ classnames( 'edit-site-layout' ) }>
			<div className="edit-site-layout__content">
				<div className="edit-site-layout__sidebar-region">
					<motion.div
						animate={ { opacity: 1 } }
						transition={ {
							type: 'tween',
							duration:
								// Disable transition in mobile to emulate a full page transition.
								disableMotion || isMobileViewport
									? 0
									: ANIMATION_DURATION,
							ease: 'easeOut',
						} }
						className="edit-site-layout__sidebar"
					>
						<div className="woocommerce-store-customize__block-pattern-list edit-site-sidebar__content wp-block-navigation__menu-inspector-controls">
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
				</div>

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
								className={ classnames(
									'edit-site-layout__canvas',
									{
										'is-right-aligned':
											isResizableFrameOversized,
									}
								) }
								transition={ {
									type: 'tween',
									duration: disableMotion
										? 0
										: ANIMATION_DURATION,
									ease: 'easeOut',
								} }
							>
								<ErrorBoundary>
									<ResizableFrame
										isReady={ ! isEditorLoading }
										isFullWidth={ false }
										defaultSize={ {
											width:
												canvasSize.width -
												24 /* $canvas-padding */,
											height: canvasSize.height,
										} }
										isOversized={
											isResizableFrameOversized
										}
										setIsOversized={
											setIsResizableFrameOversized
										}
										innerContentStyle={ {
											background:
												gradientValue ??
												backgroundColor,
										} }
									>
										<Editor
											blocks={ renderedBlocks }
											isLoading={ isEditorLoading }
										/>
									</ResizableFrame>
								</ErrorBoundary>
							</motion.div>
						) }
					</div>
				) }
			</div>
		</div>
	);
};
