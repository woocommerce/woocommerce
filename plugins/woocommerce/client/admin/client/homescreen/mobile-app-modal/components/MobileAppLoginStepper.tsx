/**
 * External dependencies
 */
import React, { useState, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Stepper, StepperProps, Link } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SendMagicLinkStates } from './';
import { MobileAppInstallationInfo } from '../components/MobileAppInstallationInfo';
import { QRDirectLoginCode } from '../components/QRDirectLoginCode';
import type { QRLoginConsumedSnapshot } from '../components/QRDirectLoginCode';
import { SendMagicLinkButton } from '../components/SendMagicLinkButton';
import { QRLoginSuccessStep } from '../components/QRLoginSuccessStep';

export const MobileAppLoginStepper = ( {
	step,
	isJetpackPluginInstalled,
	wordpressAccountEmailAddress,
	signInResult,
	completeInstallationStepHandler,
	sendMagicLinkHandler,
	sendMagicLinkStatus,
	onSignedIn,
}: {
	step: 'first' | 'second' | 'third';
	isJetpackPluginInstalled: boolean;
	wordpressAccountEmailAddress: string | undefined;
	/**
	 * Snapshot of the consumed QR login. Provided when the parent has
	 * advanced to step `'third'`; rendered by `QRLoginSuccessStep`.
	 */
	signInResult: QRLoginConsumedSnapshot | null;
	completeInstallationStepHandler: () => void;
	sendMagicLinkHandler: () => void;
	sendMagicLinkStatus: SendMagicLinkStates;
	/**
	 * Fires once the QR component reports a successful exchange. The parent
	 * uses this to record `signInResult` and advance the stepper to the
	 * third step.
	 */
	onSignedIn: ( snapshot: QRLoginConsumedSnapshot ) => void;
} ) => {
	const [ stepsToDisplay, setStepsToDisplay ] = useState<
		StepperProps[ 'steps' ] | undefined
	>( undefined );
	// we need to generate one set of steps for the first step, and another set for the second step
	// because the texts are different after progressing from the first step to the second step
	useEffect( () => {
		if ( step === 'first' ) {
			setStepsToDisplay( [
				{
					key: 'first',
					label: __( 'Install the mobile app', 'woocommerce' ),
					description: __(
						'Scan the code below to download or upgrade the app, or visit woo.com/mobile from your mobile device.',
						'woocommerce'
					),
					content: (
						<>
							<MobileAppInstallationInfo />
							<Button
								variant="primary"
								className="install-app-button"
								onClick={ () => {
									completeInstallationStepHandler();
								} }
							>
								{ __( 'App is installed', 'woocommerce' ) }
							</Button>
						</>
					),
				},
				{
					key: 'second',
					label: __( 'Sign into the app', 'woocommerce' ),
					description: '',
					content: <></>,
				},
				{
					key: 'third',
					label: __( 'Signed in', 'woocommerce' ),
					description: '',
					content: <></>,
				},
			] );
		} else if ( step === 'second' ) {
			const hasLinkedWordPressAccount =
				isJetpackPluginInstalled &&
				wordpressAccountEmailAddress !== undefined;
			setStepsToDisplay( [
				{
					key: 'first',
					label: __( 'App installed', 'woocommerce' ),
					description: '',
					content: <></>,
				},
				{
					key: 'second',
					label: __( 'Sign into the app', 'woocommerce' ),
					description: __(
						'Scan the QR code below with your phone to sign in instantly — no password needed.',
						'woocommerce'
					),
					content: (
						<>
							<QRDirectLoginCode
								onConsumed={ onSignedIn }
								suppressInlinePanels
							/>
							{ hasLinkedWordPressAccount && (
								<div className="mobile-app-login-magic-link-secondary">
									<p className="mobile-app-login-magic-link-secondary__label">
										{ __(
											'Or get a WordPress.com sign-in link by email:',
											'woocommerce'
										) }
									</p>
									<SendMagicLinkButton
										onClickHandler={ sendMagicLinkHandler }
										isFetching={
											sendMagicLinkStatus ===
											SendMagicLinkStates.FETCHING
										}
									/>
								</div>
							) }
							<div className="mobile-app-login-faq">
								{ interpolateComponents( {
									mixedString: __(
										'Any troubles signing in? Check out the {{link}}FAQ{{/link}}.',
										'woocommerce'
									),
									components: {
										link: (
											<Link
												href="https://woocommerce.com/document/android-ios-apps-login-help-faq/"
												target="_blank"
												type="external"
												onClick={ () => {
													recordEvent(
														'onboarding_app_login_faq_click'
													);
												} }
											/>
										),
									},
								} ) }
							</div>
						</>
					),
				},
				{
					key: 'third',
					label: __( 'Signed in', 'woocommerce' ),
					description: '',
					content: <></>,
				},
			] );
		} else if ( step === 'third' ) {
			setStepsToDisplay( [
				{
					key: 'first',
					label: __( 'App installed', 'woocommerce' ),
					description: '',
					content: <></>,
				},
				{
					key: 'second',
					label: __( 'Sign-in complete', 'woocommerce' ),
					description: '',
					content: <></>,
				},
				{
					key: 'third',
					label: __( 'Signed in successfully', 'woocommerce' ),
					description: '',
					content: (
						<QRLoginSuccessStep
							apUuid={ signInResult?.apUuid ?? null }
							deviceInfo={ signInResult?.deviceInfo ?? null }
						/>
					),
				},
			] );
		}
	}, [
		step,
		isJetpackPluginInstalled,
		wordpressAccountEmailAddress,
		signInResult,
		completeInstallationStepHandler,
		sendMagicLinkHandler,
		sendMagicLinkStatus,
		onSignedIn,
	] );

	return (
		<div className="login-stepper-wrapper">
			{ stepsToDisplay && (
				<Stepper
					isVertical={ true }
					currentStep={ step }
					steps={ stepsToDisplay }
				/>
			) }
		</div>
	);
};
