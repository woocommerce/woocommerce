/**
 * External dependencies
 */
import React, { useState, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { Stepper, StepperProps } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { SendMagicLinkStates } from './';
import { getAdminSetting } from '~/utils/admin-settings';
import { MobileAppInstallationInfo } from '../components/MobileAppInstallationInfo';
import { MobileAppLoginInfo } from '../components/MobileAppLoginInfo';
import { JetpackAlreadyInstalledPage } from '../pages';

export const MobileAppLoginStepper = ( {
	step,
	isJetpackPluginInstalled,
	wordpressAccountEmailAddress,
	isRetryingMagicLinkSend,
	completeInstallationStepHandler,
	sendMagicLinkHandler,
	sendMagicLinkStatus,
}: {
	step: 'first' | 'second';
	isJetpackPluginInstalled: boolean;
	wordpressAccountEmailAddress: string | undefined;
	isRetryingMagicLinkSend: boolean;
	completeInstallationStepHandler: () => void;
	sendMagicLinkHandler: () => void;
	sendMagicLinkStatus: SendMagicLinkStates;
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
						'Scan the code below to download or upgrade the app, or visit woocommerce.com/mobile from your mobile device.',
						'woocommerce'
					),
					content: (
						<>
							<MobileAppInstallationInfo />
							<Button
								className="install-jetpack-button"
								onClick={ () => {
									// TODO: track event `recordEvent`
									completeInstallationStepHandler();
								} }
							>
								<div
									style={ {
										visibility: 'visible',
									} }
									className="install-jetpack-button-contents"
								>
									<div className="jetpack-icon" />
									<div className="install-jetpack-button-text">
										{ __(
											'App is installed',
											'woocommerce'
										) }
									</div>
								</div>
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
			] );
		} else if ( step === 'second' ) {
			if (
				isJetpackPluginInstalled &&
				wordpressAccountEmailAddress !== undefined
			) {
				setStepsToDisplay( [
					{
						key: 'first',
						label: __( 'App installed', 'woocommerce' ),
						description: '',
						content: <></>,
					},
					{
						key: 'second',
						label: 'Sign into the app',
						description: sprintf(
							/* translators: Reflecting to the user that the magic link has been sent to their WordPress account email address */
							__(
								'We’ll send a magic link to %s. Open it on your smartphone or tablet to sign into your store instantly.',
								'woocommerce'
							),
							wordpressAccountEmailAddress
						),
						content: (
							<JetpackAlreadyInstalledPage
								wordpressAccountEmailAddress={
									wordpressAccountEmailAddress
								}
								isRetryingMagicLinkSend={
									isRetryingMagicLinkSend
								}
								sendMagicLinkStatus={ sendMagicLinkStatus }
								sendMagicLinkHandler={ sendMagicLinkHandler }
							/>
						),
					},
				] );
			} else {
				const siteUrl: string | undefined =
					getAdminSetting( 'siteUrl' );
				const username = getAdminSetting( 'currentUserData' ).username;
				setStepsToDisplay( [
					{
						key: 'first',
						label: __( 'App installed', 'woocommerce' ),
						description: '',
						content: <></>,
					},
					{
						key: 'second',
						label: 'Sign into the app',
						description: __(
							'Scan the QR code below and enter the wp-admin password in the app.',
							'woocommerce'
						),
						content: (
							<MobileAppLoginInfo
								siteUrl={ siteUrl }
								username={ username }
							/>
						),
					},
				] );
			}
		}
	}, [
		step,
		completeInstallationStepHandler,
		sendMagicLinkHandler,
		sendMagicLinkStatus,
	] );

	return (
		<div className="jetpack-stepper-wrapper">
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
