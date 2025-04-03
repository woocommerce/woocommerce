/**
 * External dependencies
 */
import React, { useState, useRef, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Notice, Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './style.scss';

interface StepCheckResponse {
	status: string;
	success: boolean;
}

const TestDriveLoader: React.FunctionComponent< {
	progress: number;
} > = ( { progress } ) => (
	<Loader className="woocommerce-payments-test-account-step__preloader">
		<Loader.Layout className="woocommerce-payments-test-account-step__preloader-layout">
			<Loader.Illustration>
				<img
					src={ `${ WC_ASSET_URL }images/onboarding/test-account-setup.svg` }
					alt="setup"
					style={ { maxWidth: '223px' } }
				/>
			</Loader.Illustration>

			<Loader.Title>
				{ __( 'Finishing payments setup', 'woocommerce' ) }
			</Loader.Title>
			<Loader.ProgressBar progress={ progress ?? 0 } />
			<Loader.Sequence interval={ 0 }>
				{ __(
					"In just a few moments, you'll be ready to test payments on your store.",
					'woocommerce'
				) }
			</Loader.Sequence>
		</Loader.Layout>
	</Loader>
);

const TestAccountStep = () => {
	const { currentStep, navigateToNextStep, closeModal } =
		useOnboardingContext();
	const [ testDriveLoaderProgress, setTestDriveLoaderProgress ] =
		useState( 5 );
	const [ errorMessage, setErrorMessage ] = useState< string | undefined >();
	const [ retryCounter, setRetryCounter ] = useState( 0 );
	const [ testAccountCreationSuccess, setTestAccountCreationSuccess ] =
		useState( false );

	// Create a reference object.
	const loaderProgressRef = useRef( testDriveLoaderProgress );
	loaderProgressRef.current = testDriveLoaderProgress;

	const updateLoaderProgress = ( maxPercent: number, step: number ) => {
		if ( loaderProgressRef.current < maxPercent ) {
			const newProgress = loaderProgressRef.current + step;
			setTestDriveLoaderProgress( newProgress );
		}
	};

	useEffect( () => {
		if (
			currentStep?.status === 'not_started' &&
			! testAccountCreationSuccess
		) {
			// Send a request to the server to start the test account setup.
			apiFetch( {
				url: currentStep?.actions?.init?.href,
				method: 'POST',
			} ).catch( ( response ) => {
				setErrorMessage( response.message );
			} );

			// Create a polling function to check the status of the test account setup.
			const checkTestAccountStatus = () => {
				// Add progress
				updateLoaderProgress( 100, 6 );

				apiFetch( {
					url: currentStep?.actions?.check?.href,
					method: 'POST',
				} ).then( ( response ) => {
					if (
						( response as StepCheckResponse )?.status ===
						'completed'
					) {
						// Set the progress to 100%.
						setTestDriveLoaderProgress( 100 );

						// Set the test account creation success to true after some time to avoid UI re-rendering rapidly.
						setTimeout( () => {
							setTestAccountCreationSuccess( true );
						}, 1000 );
					}
				} );
			};

			// Check the status of the test account setup every 2.5 seconds.
			const interval = setInterval( checkTestAccountStatus, 2500 );
			return () => clearInterval( interval );
		}

		if ( currentStep?.errors?.[ 0 ] ) {
			setErrorMessage( currentStep.errors[ 0 ] );
		}
	}, [
		currentStep?.status,
		currentStep?.errors,
		currentStep?.actions?.init?.href,
		currentStep?.actions?.check?.href,
		retryCounter,
		testAccountCreationSuccess,
	] );

	if ( testAccountCreationSuccess ) {
		return (
			<>
				<WooPaymentsStepHeader onClose={ closeModal } />
				<div className="settings-payments-onboarding-modal__step--content">
					<div className="woocommerce-payments-test-account-step__success_content_container">
						<div className="woocommerce-woopayments-modal__content woocommerce-payments-test-account-step__success_content">
							<h1 className="woocommerce-payments-test-account-step__success_content_title">
								{ __(
									'You’re ready to test payments!',
									'woocommerce'
								) }
							</h1>
							<div className="woocommerce-woopayments-modal__content__item">
								<div className="woocommerce-woopayments-modal__content__item__description">
									<p>
										{ interpolateComponents( {
											mixedString: __(
												"We've created a test account for you so that you can begin {{link}}testing payments on your store{{/link}}.",
												'woocommerce'
											),
											components: {
												link: (
													<Link
														href="https://woocommerce.com/document/woopayments/testing-and-troubleshooting/sandbox-mode/"
														target="_blank"
														rel="noreferrer"
														type="external"
													/>
												),
												break: <br />,
											},
										} ) }
									</p>
								</div>
							</div>
							<div className="woocommerce-payments-test-account-step__success-whats-next">
								<div className="woocommerce-woopayments-modal__content__item">
									<h2>
										{ __( "What's next:", 'woocommerce' ) }
									</h2>
								</div>
								<div className="woocommerce-woopayments-modal__content__item-flex">
									<img
										src={
											WC_ASSET_URL +
											'images/icons/store.svg'
										}
										alt="store icon"
									/>
									<div className="woocommerce-woopayments-modal__content__item-flex__description">
										<h3>
											{ __(
												'Continue your store setup',
												'woocommerce'
											) }
										</h3>
										<div>
											{ __(
												'Finish completing the tasks required to launch your store.',
												'woocommerce'
											) }
										</div>
									</div>
								</div>
								<div className="woocommerce-woopayments-modal__content__item-flex">
									<img
										src={
											WC_ASSET_URL +
											'images/icons/dollar.svg'
										}
										alt="dollar icon"
									/>
									<div className="woocommerce-woopayments-modal__content__item-flex__description">
										<h3>
											{ __(
												'Activate payments',
												'woocommerce'
											) }
										</h3>
										<div>
											<p>
												{ interpolateComponents( {
													mixedString: __(
														'Provide some additional details about your business so you can being accepting real payments. {{link}}Learn more{{/link}}',
														'woocommerce'
													),
													components: {
														link: (
															<Link
																href="https://woocommerce.com/document/woopayments/startup-guide/#sign-up-process"
																target="_blank"
																rel="noreferrer"
																type="external"
															/>
														),
													},
												} ) }
											</p>
										</div>
									</div>
								</div>
							</div>
							<Button
								variant="primary"
								onClick={ () => {
									// Navigate to wc-admin page
									navigateTo( {
										url: getNewPath( {}, '', {
											page: 'wc-admin',
										} ),
									} );
								} }
							>
								{ __( 'Continue store setup', 'woocommerce' ) }
							</Button>
							<div className="woocommerce-payments-test-account-step__success_content_or-divider">
								<hr />
								{ __( 'OR', 'woocommerce' ) }
								<hr />
							</div>

							<Button
								variant="secondary"
								onClick={ () => {
									// This will refresh the steps and move the modal to the next step
									navigateToNextStep();
								} }
							>
								{ __( 'Activate payments', 'woocommerce' ) }
							</Button>
						</div>
					</div>
				</div>
			</>
		);
	}

	return (
		<div className="woocommerce-payments-test-account-step">
			<WooPaymentsStepHeader onClose={ closeModal } />
			{ errorMessage && (
				<Notice
					status="warning"
					isDismissible={ false }
					actions={ [
						{
							label: __( 'Try Again', 'woocommerce' ),
							variant: 'primary',
							onClick: () => {
								// Increment the retry counter to trigger the test account setup again.
								setRetryCounter( ( prev ) => prev + 1 );
								setErrorMessage( undefined );
							},
						},
						{
							label: __( 'Cancel', 'woocommerce' ),
							variant: 'secondary',
							className:
								'woocommerce-payments-test-account-step__error-cancel-button',
							onClick: closeModal,
						},
					] }
					className="woocommerce-payments-test-account-step__error"
				>
					<p className="woocommerce-payments-test-account-step__error-message">
						{ errorMessage }
					</p>
				</Notice>
			) }
			<TestDriveLoader progress={ testDriveLoaderProgress } />
		</div>
	);
};

export default TestAccountStep;
