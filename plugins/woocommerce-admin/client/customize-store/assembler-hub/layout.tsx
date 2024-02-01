// Reference: https://github.com/WordPress/gutenberg/tree/v16.4.0/packages/edit-site/src/components/layout/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	useReducedMotion,
	useResizeObserver,
	useViewportMatch,
} from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { useState, useContext, useEffect } from '@wordpress/element';
import {
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';
import {
	privateApis as blockEditorPrivateApis,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-editor';
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
// @ts-ignore No types for this exist yet.
import { EntityProvider } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import useEditedEntityRecord from '@wordpress/edit-site/build-module/components/use-edited-entity-record';

/**
 * Internal dependencies
 */
import { Editor } from './editor';
import Sidebar from './sidebar';
import { SiteHub } from './site-hub';
import { LogoBlockContext } from './logo-block-context';
import ResizableFrame from './resizable-frame';
import { OnboardingTour, useOnboardingTour } from './onboarding-tour';
import { HighlightedBlockContextProvider } from './context/highlighted-block-context';
import { Transitional } from '../transitional';
import { CustomizeStoreContext } from './';
import { recordEvent } from '@woocommerce/tracks';
import { AiOfflineModal } from '~/customize-store/assembler-hub/onboarding-tour/ai-offline-modal';
import { useQuery } from '@woocommerce/navigation';
import { FlowType } from '../types';
import { isOfflineAIFlow } from '../guards';
import { isWooExpress } from '~/utils/is-woo-express';

const { useGlobalStyle } = unlock( blockEditorPrivateApis );

const ANIMATION_DURATION = 0.5;

export const Layout = () => {
	const [ logoBlockIds, setLogoBlockIds ] = useState< Array< string > >( [] );

	const { sendEvent, currentState, context } = useContext(
		CustomizeStoreContext
	);

	const { customizing } = useQuery();

	const [ showAiOfflineModal, setShowAiOfflineModal ] = useState(
		isOfflineAIFlow( context.flowType ) && customizing !== 'true'
	);

	useEffect( () => {
		setShowAiOfflineModal(
			isOfflineAIFlow( context.flowType ) && customizing !== 'true'
		);
	}, [ context.flowType, customizing ] );

	// This ensures the edited entity id and type are initialized properly.
	useInitEditedEntityFromURL();
	const {
		shouldTourBeShown,
		isResizeHandleVisible,
		setShowWelcomeTour,
		onClose,
		...onboardingTourProps
	} = useOnboardingTour();

	const takeTour = () => {
		// Click on "Take a tour" button
		recordEvent( 'customize_your_store_assembler_hub_tour_start' );
		setShowWelcomeTour( false );
		setShowAiOfflineModal( false );
	};

	const skipTour = () => {
		recordEvent( 'customize_your_store_assembler_hub_tour_skip' );
		onClose();
		setShowAiOfflineModal( false );
	};

	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const disableMotion = useReducedMotion();
	const [ canvasResizer, canvasSize ] = useResizeObserver();
	const isEditorLoading = useIsSiteEditorLoading();
	const [ isResizableFrameOversized, setIsResizableFrameOversized ] =
		useState( false );
	const [ backgroundColor ] = useGlobalStyle( 'color.background' );
	const [ gradientValue ] = useGlobalStyle( 'color.gradient' );

	const { record: template } = useEditedEntityRecord();
	const { id: templateId, type: templateType } = template;

	const [ isSurveyOpen, setSurveyOpen ] = useState( false );
	const editor = <Editor isLoading={ isEditorLoading } />;

	if (
		typeof currentState === 'object' &&
		currentState.transitionalScreen === 'transitional'
	) {
		return (
			<EntityProvider kind="root" type="site">
				<EntityProvider
					kind="postType"
					type={ templateType }
					id={ templateId }
				>
					<Transitional
						sendEvent={ sendEvent }
						editor={ editor }
						isWooExpress={ isWooExpress() }
						isSurveyOpen={ isSurveyOpen }
						setSurveyOpen={ setSurveyOpen }
						hasCompleteSurvey={
							!! context?.transitionalScreen?.hasCompleteSurvey
						}
						aiOnline={ context?.flowType === FlowType.AIOnline }
					/>
				</EntityProvider>
			</EntityProvider>
		);
	}

	return (
		<LogoBlockContext.Provider
			value={ {
				logoBlockIds,
				setLogoBlockIds,
			} }
		>
			<HighlightedBlockContextProvider>
				<EntityProvider kind="root" type="site">
					<EntityProvider
						kind="postType"
						type={ templateType }
						id={ templateId }
					>
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
									isTransparent={ false }
									className="edit-site-layout__hub"
								/>
							</motion.div>

							<div className="edit-site-layout__content">
								<NavigableRegion
									ariaLabel={ __(
										'Navigation',
										'woocommerce'
									) }
									className="edit-site-layout__sidebar-region"
								>
									<motion.div
										animate={ { opacity: 1 } }
										transition={ {
											type: 'tween',
											duration:
												// Disable transitiont in mobile to emulate a full page transition.
												disableMotion ||
												isMobileViewport
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
									<div className="edit-site-layout__canvas-container">
										{ canvasResizer }
										{ !! canvasSize.width && (
											<motion.div
												initial={ false }
												layout="position"
												className={ classnames(
													'edit-site-layout__canvas'
												) }
											>
												<ErrorBoundary>
													<ResizableFrame
														isReady={
															! isEditorLoading
														}
														isHandleVisibleByDefault={
															! onboardingTourProps.showWelcomeTour &&
															isResizeHandleVisible
														}
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
														{ editor }
													</ResizableFrame>
												</ErrorBoundary>
											</motion.div>
										) }
									</div>
								) }
							</div>
						</div>
						{ ! isEditorLoading &&
							shouldTourBeShown &&
							( FlowType.AIOnline === context.flowType ||
								FlowType.noAI === context.flowType ) && (
								<OnboardingTour
									skipTour={ skipTour }
									takeTour={ takeTour }
									onClose={ onClose }
									flowType={ context.flowType }
									{ ...onboardingTourProps }
								/>
							) }

						{ ! isEditorLoading && showAiOfflineModal && (
							<AiOfflineModal
								shouldTourBeShown={ shouldTourBeShown }
								skipTour={ skipTour }
								takeTour={ takeTour }
							/>
						) }
					</EntityProvider>
				</EntityProvider>
			</HighlightedBlockContextProvider>
		</LogoBlockContext.Provider>
	);
};
