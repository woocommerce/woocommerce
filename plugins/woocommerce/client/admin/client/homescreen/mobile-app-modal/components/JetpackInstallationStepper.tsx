/**
 * External dependencies
 */
import React, { useState, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { Spinner, Stepper, StepperProps } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	SendMagicLinkButton,
	useJetpackPluginState,
	JetpackPluginStates,
	SendMagicLinkStates,
} from './';

export const JetpackInstallationStepper = ( {
	step,
	sendMagicLinkHandler,
	sendMagicLinkStatus,
}: {
	step: 'first' | 'second';
	sendMagicLinkHandler: () => void;
	sendMagicLinkStatus: SendMagicLinkStates;
} ) => {
	const { installHandler, jetpackConnectionData, state } =
		useJetpackPluginState();

	const [ isWaitingForRedirect, setIsWaitingForRedirect ] = useState( false );

	const [ stepsToDisplay, setStepsToDisplay ] = useState<
		StepperProps[ 'steps' ] | undefined
	>( undefined );
	// we need to generate one set of steps for the first step, and another set for the second step
	// because the texts are different after progressing from the first step to the second step
	useEffect( () => {
		const isInstalling =
			state === JetpackPluginStates.INSTALLING || isWaitingForRedirect;
		if ( step === 'first' ) {
			setStepsToDisplay( [
				{
					key: 'first',
					label: __( 'Connect to Jetpack', 'woocommerce' ),
					description: __(
						'To get started, install Jetpack - our free tool required to sync your store with the WooCommerce mobile app',
						'woocommerce'
					),
					content: (
						<>
							<Button
								className="install-jetpack-button"
								onClick={ () => {
									setIsWaitingForRedirect( true );
									recordEvent(
										'magic_prompt_install_connect_click'
									);
									installHandler();
								} }
							>
								{ isInstalling && (
									<Spinner className="install-jetpack-spinner" />
								) }
								<div
									style={ {
										visibility: isInstalling
											? 'hidden'
											: 'visible',
									} }
									className="install-jetpack-button-contents"
								>
									<div className="jetpack-icon" />
									<div className="install-jetpack-button-text">
										{ __(
											'Install and Connect',
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
			// this step is shown on the return callback from the WordPress.com user connection
			const wordpressAccountEmailAddress =
				jetpackConnectionData?.currentUser?.wpcomUser?.email;
			setStepsToDisplay( [
				{
					key: 'first',
					label: sprintf(
						/* translators: Reflecting to the user what their WordPress account email address is */
						__( 'Connected as %s', 'woocommerce' ),
						wordpressAccountEmailAddress
					),
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
		}
	}, [
		step,
		installHandler,
		state,
		isWaitingForRedirect,
		jetpackConnectionData?.currentUser?.wpcomUser?.email,
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
