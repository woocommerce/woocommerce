// Reference: https://github.com/WordPress/gutenberg/tree/v16.4.0/packages/edit-site/src/components/layout/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import {
	useReducedMotion,
	useResizeObserver,
	useViewportMatch,
} from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import {
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';
import {
	privateApis as blockEditorPrivateApis,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import ResizableFrame from '@wordpress/edit-site/build-module/components/resizable-frame';
// @ts-ignore No types for this exist yet.
import useInitEditedEntityFromURL from '@wordpress/edit-site/build-module/components/sync-state-with-url/use-init-edited-entity-from-url';
// @ts-ignore No types for this exist yet.
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
// @ts-ignore No types for this exist yet.
import ErrorBoundary from '@wordpress/edit-site/build-module/components/error-boundary';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { NavigableRegion } from '@wordpress/interface';
/**
 * Internal dependencies
 */
import { Editor } from './editor';
import Sidebar from './sidebar';
import { SiteHub } from './site-hub';

const { useGlobalStyle } = unlock( blockEditorPrivateApis );

const ANIMATION_DURATION = 0.5;

export const Layout = () => {
	// This ensures the edited entity id and type are initialized properly.
	useInitEditedEntityFromURL();

	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const disableMotion = useReducedMotion();
	const [ canvasResizer, canvasSize ] = useResizeObserver();
	const isEditorLoading = useIsSiteEditorLoading();
	const [ isResizableFrameOversized, setIsResizableFrameOversized ] =
		useState( false );
	const [ backgroundColor ] = useGlobalStyle( 'color.background' );
	const [ gradientValue ] = useGlobalStyle( 'color.gradient' );

	return (
		<div className={ classnames( 'edit-site-layout' ) }>
			<motion.div
				className="edit-site-layout__header-container"
				animate={ 'view' }
			>
				<SiteHub
					as={ motion.div }
					variants={ {
						view: { x: 0 },
					} }
					isTransparent={ isResizableFrameOversized }
					className="edit-site-layout__hub"
				/>
			</motion.div>

			<div className="edit-site-layout__content">
				<NavigableRegion
					ariaLabel={ __( 'Navigation', 'woocommerce' ) }
					className="edit-site-layout__sidebar-region"
				>
					<motion.div
						animate={ { opacity: 1 } }
						transition={ {
							type: 'tween',
							duration:
								// Disable transitiont in mobile to emulate a full page transition.
								disableMotion || isMobileViewport
									? 0
									: ANIMATION_DURATION,
							ease: 'easeOut',
						} }
						className="edit-site-layout__sidebar"
					>
						<Sidebar />
					</motion.div>
				</NavigableRegion>

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
										<Editor isLoading={ isEditorLoading } />
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
