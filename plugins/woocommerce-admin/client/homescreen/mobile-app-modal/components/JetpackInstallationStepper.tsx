/**
 * External dependencies
 */
import React, { useState, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Spinner, Stepper, StepperProps } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	SendMagicLinkButton,
	useJetpackPluginState,
	JetpackPluginStates,
} from './';

export const JetpackInstallationStepper = ( {
	step,
	sendMagicLinkHandler,
}: {
	step: 'first' | 'second';
	sendMagicLinkHandler: () => void;
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
					label: 'Sign into the app',
					description: '',
					content: <div>Second step content.</div>,
				},
			] );
		} else if ( step === 'second' ) {
			// this step is shown on the return callback from the WordPress.com user connection
			const wordpressAccountEmailAddress =
				jetpackConnectionData?.currentUser.wpcomUser.email;
			setStepsToDisplay( [
				{
					key: 'first',
					label: `Connected as ${ wordpressAccountEmailAddress }`,
					description: '',
					content: <></>,
				},
				{
					key: 'second',
					label: 'Sign into the app',
					description: `We’ll send a magic link to ${ wordpressAccountEmailAddress }. Open it on your smartphone or tablet to sign into your store instantly.`,
					content: (
						<SendMagicLinkButton
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
		jetpackConnectionData?.currentUser.wpcomUser.email,
		sendMagicLinkHandler,
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
