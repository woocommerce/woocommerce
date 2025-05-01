/**
 * External dependencies
 */
import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import { useStepperContext } from '../components/stepper';
import { disableWooPaymentsTestMode } from '~/settings-payments/utils';

const ActivatePayments: React.FC = () => {
	const { nextStep } = useStepperContext();
	const [ isContinueButtonLoading, setIsContinueButtonLoading ] =
		useState( false );

	const handleContinue = () => {
		// Set the continue button loading state to true.
		setIsContinueButtonLoading( true );

		// Disable test mode and redirect to the live account setup link.
		disableWooPaymentsTestMode()
			.then( () => {
				// Set the continue button loading state to false.
				setIsContinueButtonLoading( false );
				// Navigate to the live account setup.
				return nextStep();
			} )
			.catch( () => {
				// Handle any errors that occur during the process.
				setIsContinueButtonLoading( false );
			} );
	};

	return (
		<>
			<h1 className="stepper__heading">
				{ __( 'Start accepting real payments', 'woocommerce' ) }
			</h1>
			<p className="stepper__subheading">
				{ interpolateComponents( {
					mixedString: __(
						'You are currently testing payments on your store. To activate real payments, you will need to provide some additional details about your business. {{link}}Learn more{{/link}}.',
						'woocommerce'
					),
					components: {
						link: (
							<Link
								href="https://woocommerce.com/document/woopayments/startup-guide/#sign-up-process"
								target="_blank"
								type="external"
							/>
						),
					},
				} ) }
			</p>
			<div className="stepper__content">
				<Button
					variant="primary"
					className="stepper__cta"
					onClick={ handleContinue }
					isBusy={ isContinueButtonLoading }
					disabled={ isContinueButtonLoading }
				>
					{ __( 'Activate payments', 'woocommerce' ) }
				</Button>
			</div>
		</>
	);
};

export default ActivatePayments;
