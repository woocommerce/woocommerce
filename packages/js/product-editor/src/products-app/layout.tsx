// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/**
 * External dependencies
 */
import { createElement, useState, Fragment } from '@wordpress/element';
import { useViewportMatch, useResizeObserver } from '@wordpress/compose';
import {
	__unstableMotion as motion,
	__unstableAnimatePresence as AnimatePresence,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	EditorSnackbars,
	privateApis as editorPrivateApis,
} from '@wordpress/editor';
import { privateApis as blockEditorPrivateApis } from '@wordpress/block-editor';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import ResizableFrame from '@wordpress/edit-site/build-module/components/resizable-frame';
import ErrorBoundary from '@wordpress/edit-site/build-module/components/error-boundary';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import SidebarContent from './sidebar';

const { NavigableRegion } = unlock( editorPrivateApis );
const { useGlobalStyle } = unlock( blockEditorPrivateApis );

export function Layout( { route } ) {
	const [ fullResizer ] = useResizeObserver();
	const [ canvasResizer, canvasSize ] = useResizeObserver();
	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const [ isResizableFrameOversized, setIsResizableFrameOversized ] =
		useState( false );
	const { canvasMode } = useSelect( ( select ) => {
		const { getCanvasMode } = unlock( select( 'core/edit-site' ) );
		return {
			canvasMode: getCanvasMode(),
		};
	}, [] );
	const [ backgroundColor ] = useGlobalStyle( 'color.background' );
	const [ gradientValue ] = useGlobalStyle( 'color.gradient' );

	const { key: routeKey, areas, widths } = route;

	return (
		<>
			{ fullResizer }
			<div
				className={ classNames( 'edit-site-layout', {
					'is-full-canvas': canvasMode === 'edit',
				} ) }
			>
				<div className="edit-site-layout__content">
					{ /*
						The NavigableRegion must always be rendered and not use
						`inert` otherwise `useNavigateRegions` will fail.
					*/ }
					{ ( ! isMobileViewport || ! areas.mobile ) && (
						<NavigableRegion
							ariaLabel={ __( 'Navigation', 'woocommerce' ) }
							className="edit-site-layout__sidebar-region"
						>
							<AnimatePresence>
								{ canvasMode === 'view' && (
									<motion.div
										initial={ { opacity: 0 } }
										animate={ { opacity: 1 } }
										exit={ { opacity: 0 } }
										transition={ {
											type: 'tween',
											duration:
												// Disable transition in mobile to emulate a full page transition.
												disableMotion ||
												isMobileViewport
													? 0
													: ANIMATION_DURATION,
											ease: 'easeOut',
										} }
										className="edit-site-layout__sidebar"
									>
										<div>SiteHub</div>
										<SidebarContent routeKey={ routeKey }>
											{ areas.sidebar }
										</SidebarContent>
										<div>SaveHub </div>
										<div>SavePanel</div>
									</motion.div>
								) }
							</AnimatePresence>
						</NavigableRegion>
					) }

					<EditorSnackbars />

					{ ! isMobileViewport &&
						areas.content &&
						canvasMode !== 'edit' && (
							<div
								className="edit-site-layout__area"
								style={ {
									maxWidth: widths?.content,
								} }
							>
								{ areas.content }
							</div>
						) }

					{ ! isMobileViewport && areas.edit && (
						<div
							className="edit-site-layout__area"
							style={ {
								maxWidth: widths?.edit,
							} }
						>
							{ areas.edit }
						</div>
					) }

					{ ! isMobileViewport && areas.preview && (
						<div className="edit-site-layout__canvas-container">
							{ canvasResizer }
							{ !! canvasSize.width && (
								<div
									className={ classNames(
										'edit-site-layout__canvas',
										{
											'is-right-aligned':
												isResizableFrameOversized,
										}
									) }
								>
									<ErrorBoundary>
										<ResizableFrame
											isReady={ true }
											isFullWidth={
												canvasMode === 'edit'
											}
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
											{ areas.preview }
										</ResizableFrame>
									</ErrorBoundary>
								</div>
							) }
						</div>
					) }
				</div>
			</div>
		</>
	);
}
