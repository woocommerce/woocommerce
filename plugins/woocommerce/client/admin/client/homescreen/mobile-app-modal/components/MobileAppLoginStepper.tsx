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
import { SendMagicLinkButton, SendMagicLinkStates } from './';
import { getAdminSetting } from '~/utils/admin-settings';
import { MobileAppInstallationInfo } from '../components/MobileAppInstallationInfo';
import { MobileAppLoginInfo } from '../components/MobileAppLoginInfo';

export const MobileAppLoginStepper = ( {
	step,
	isJetpackPluginInstalled,
	wordpressAccountEmailAddress,
	completeInstallationStepHandler,
	sendMagicLinkHandler,
	sendMagicLinkStatus,
}: {
	step: 'first' | 'second';
	isJetpackPluginInstalled: boolean;
	wordpressAccountEmailAddress: string | undefined;
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
							<SendMagicLinkButton
								isFetching={
									sendMagicLinkStatus ===
									SendMagicLinkStates.FETCHING
								}
								onClickHandler={ sendMagicLinkHandler }
							/>
						),
					},
				] );
			} else {
				const siteUrl: string = getAdminSetting( 'siteUrl' );
				const username = getAdminSetting( 'currentUserData' ).username;
				const loginUrl = `woocommerce://app-login?siteUrl=${ encodeURIComponent(
					siteUrl
				) }&username=${ encodeURIComponent( username ) }`;
				const description = loginUrl
					? __(
							'Scan the QR code below and enter the wp-admin password in the app.',
							'woocommerce'
					  )
					: __(
							'Follow the instructions in the app to sign in.',
							'woocommerce'
					  );
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
						description,
						content: <MobileAppLoginInfo loginUrl={ loginUrl } />,
					},
				] );
			}
		}
	}, [
		step,
		isJetpackPluginInstalled,
		wordpressAccountEmailAddress,
		completeInstallationStepHandler,
		sendMagicLinkHandler,
		sendMagicLinkStatus,
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
