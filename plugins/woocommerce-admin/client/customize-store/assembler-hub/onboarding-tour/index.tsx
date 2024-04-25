/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { TourKit, TourKitTypes } from '@woocommerce/components';

/**
 * Internal dependencies
 */
export * from './use-onboarding-tour';
import { FlowType } from '~/customize-store/types';
import { trackEvent } from '~/customize-store/tracking';

type OnboardingTourProps = {
	onClose: () => void;
	skipTour: () => void;
	takeTour: () => void;
	showWelcomeTour: boolean;
	setIsResizeHandleVisible: ( isVisible: boolean ) => void;
	flowType: FlowType.AIOnline | FlowType.noAI;
};

const getLabels = ( flowType: FlowType.AIOnline | FlowType.noAI ) => {
	switch ( flowType ) {
		case FlowType.AIOnline:
			return {
				heading: __(
					'Welcome to your AI-generated store!',
					'woocommerce'
				),
				descriptions: {
					desktop: __(
						'This is where you can start customizing the look and feel of your store, including adding your logo, and changing colors and layouts. Take a quick tour to discover what’s possible.',
						'woocommerce'
					),
				},
			};
		case FlowType.noAI:
			return {
				heading: __(
					"Discover what's possible with the store designer",
					'woocommerce'
				),
				descriptions: {
					desktop: __(
						"Start designing your store, including adding your logo, changing color schemes, and choosing layouts. To help you get started, we've added some layouts for you to customize. Take a quick tour to discover what's possible.",
						'woocommerce'
					),
				},
			};
	}
};

export const OnboardingTour = ( {
	onClose,
	skipTour,
	takeTour,
	flowType,
	showWelcomeTour,
	setIsResizeHandleVisible,
}: OnboardingTourProps ) => {
	const [ placement, setPlacement ] =
		useState< TourKitTypes.WooConfig[ 'placement' ] >( 'left' );

	const { heading, descriptions } = getLabels( flowType );

	if ( showWelcomeTour ) {
		return (
			<TourKit
				config={ {
					options: {
						effects: {
							arrowIndicator: false,
							overlay: false,
							liveResize: {
								rootElementSelector: '#adminmenuback',
								resize: true,
							},
						},
						portalParentElement:
							document.getElementById( 'wpbody' ),
						popperModifiers: [
							{
								name: 'bottom-left',
								enabled: true,
								phase: 'beforeWrite',
								requires: [ 'computeStyles' ],
								fn: ( { state } ) => {
									state.styles.popper.top = 'auto';
									state.styles.popper.left = 'auto';
									state.styles.popper.bottom = '16px';
									state.styles.popper.transform =
										'translate3d(16px, 0px, 0px)';
								},
							},
						],
						classNames: [
							'woocommerce-customize-store-tour-kit',
							'woocommerce-customize-store-welcome-tourkit',
						],
					},
					steps: [
						{
							meta: {
								name: 'welcome-tour',
								primaryButton: {
									text: __( 'Take a tour', 'woocommerce' ),
								},
								descriptions,
								heading,
								skipButton: {
									isVisible: true,
								},
							},
							referenceElements: {
								desktop: '#adminmenuback',
							},
						},
					],
					closeHandler: ( _steps, _currentStepIndex, source ) => {
						if ( source === 'done-btn' ) {
							// Click on "Take a tour" button
							takeTour();
						} else {
							skipTour();
						}
					},
				} }
			/>
		);
	}

	return (
		<TourKit
			config={ {
				placement,
				options: {
					effects: {
						spotlight: {
							interactivity: {
								enabled: true,
								rootElementSelector: '#wpwrap',
							},
						},
						arrowIndicator: true,
						autoScroll: {
							behavior: 'auto',
							block: 'center',
						},
						liveResize: {
							mutation: true,
							resize: true,
							rootElementSelector: '#wpwrap',
						},
					},
					callbacks: {
						onPreviousStep: () => {
							setPlacement( 'left' );
							setIsResizeHandleVisible( true );
						},
						onNextStep: () => {
							setPlacement( 'right-start' );
							setIsResizeHandleVisible( false );
						},
					},
					popperModifiers: [
						{
							name: 'right-start',
							enabled: true,
							phase: 'beforeWrite',
							requires: [ 'computeStyles' ],
							fn: ( { state } ) => {
								state.styles.arrow.transform =
									'translate3d(0px, 96px, 0)';
							},
						},
						{
							name: 'offset',
							options: {
								offset: ( {
									// eslint-disable-next-line @typescript-eslint/no-shadow
									placement,
								}: {
									placement: TourKitTypes.WooConfig[ 'placement' ];
									[ key: string ]: unknown;
								} ) => {
									if ( placement === 'left' ) {
										return [ 0, 20 ];
									}
									return [ 52, 16 ];
								},
							},
						},
					],
					classNames: 'woocommerce-customize-store-tour-kit',
				},
				steps: [
					{
						referenceElements: {
							desktop: `.edit-site-layout__canvas-container`,
						},
						meta: {
							name: 'view-changes-real-time',
							heading: __(
								'View your changes in real time',
								'woocommerce'
							),
							descriptions: {
								desktop: __(
									'Any changes you make to the layout and style will appear here in real time — perfect for testing different looks before you make it live. You can also resize this area to check how your store looks on mobile.',
									'woocommerce'
								),
							},
						},
					},
					{
						referenceElements: {
							desktop: `.edit-site-layout__sidebar-region`,
						},
						meta: {
							name: 'make-your-store-your-own',
							heading: __(
								'Make your store your own',
								'woocommerce'
							),
							descriptions: {
								desktop: __(
									"Customize the style and layout of your store to fit your brand! Add your logo, change the font and colors, and try out different page layouts. You'll be able to edit the text and images later via the Editor.",
									'woocommerce'
								),
							},
							secondaryButton: {
								text: __( 'Previous', 'woocommerce' ),
							},
						},
					},
				],
				closeHandler: ( _steps, _currentStepIndex, source ) => {
					if ( source === 'done-btn' ) {
						trackEvent(
							'customize_your_store_assembler_hub_tour_complete'
						);
					} else {
						trackEvent(
							'customize_your_store_assembler_hub_tour_close'
						);
					}

					onClose();
				},
			} }
		></TourKit>
	);
};
