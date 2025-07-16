/**
 * External dependencies
 */
import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import { TESTING_ACCOUNT_STEP_ID } from '~/settings-payments/onboarding/providers/woopayments/steps';
import './style.scss';

const TestOrLiveAccountStep = () => {
	const {
		closeModal,
		currentStep,
		sessionEntryPoint,
		navigateToNextStep,
		refreshStoreData,
		getStepByKey,
	} = useOnboardingContext();
	const [ isContinueButtonLoading, setIsContinueButtonLoading ] =
		useState( false );

	return (
		<>
			<WooPaymentsStepHeader onClose={ closeModal } />
			<div className="settings-payments-onboarding-modal__step--content">
				<div className="woocommerce-payments-test-account-step__success_content_container">
					<div className="woocommerce-woopayments-modal__content woocommerce-payments-test-account-step__success_content">
						<h1 className="woocommerce-payments-test-account-step__success_content_title">
							{ __( "You're almost there!", 'woocommerce' ) }
						</h1>
						<div className="woocommerce-woopayments-modal__content__item">
							<div className="woocommerce-woopayments-modal__content__item__description">
								<p>
									{ __(
										'Activate payments to start accepting real orders and processing transactions.',
										'woocommerce'
									) }
								</p>
							</div>
						</div>
						<div className="woocommerce-payments-test-account-step__success-whats-next">
							<div className="woocommerce-woopayments-modal__content__item-flex">
								<img
									src={
										WC_ASSET_URL + 'images/icons/dollar.svg'
									}
									alt="dollar icon"
								/>
								<div className="woocommerce-woopayments-modal__content__item-flex__description">
									<h3>
										{ __(
											'Activate real payments',
											'woocommerce'
										) }
									</h3>
									<div>
										{ interpolateComponents( {
											mixedString: __(
												'Provide additional details about your business so you can begin accepting real payments. {{link}}Learn more{{/link}}',
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
									</div>
								</div>
							</div>
							<Button
								variant="primary"
								onClick={ () => {
									setIsContinueButtonLoading( true );

									recordPaymentsOnboardingEvent(
										'woopayments_onboarding_modal_click',
										{
											step: currentStep?.id || 'unknown',
											action: 'activate_payments',
											source: sessionEntryPoint,
										}
									);

									const testAccountStep = getStepByKey(
										TESTING_ACCOUNT_STEP_ID
									);

									const actionUrl =
										testAccountStep?.actions?.finish?.href;

									if ( actionUrl ) {
										apiFetch( {
											url: actionUrl,
											method: 'POST',
										} )
											.then( () => {
												setIsContinueButtonLoading(
													false
												);

												refreshStoreData();
											} )
											.catch( () => {
												// Handle any errors that occur during the process.
												setIsContinueButtonLoading(
													false
												);
											} );
									}
								} }
								isBusy={ isContinueButtonLoading }
								disabled={ isContinueButtonLoading }
							>
								{ __( 'Activate payments', 'woocommerce' ) }
							</Button>

							<div className="woocommerce-payments-test-account-step__success_content_or-divider">
								<hr />
								{ __( 'OR', 'woocommerce' ) }
								<hr />
							</div>

							<div className="woocommerce-woopayments-modal__content__item-flex">
								<img
									src={
										WC_ASSET_URL +
										'images/icons/post-list.svg'
									}
									alt="list icon"
								/>
								<div className="woocommerce-woopayments-modal__content__item-flex__description">
									<h3>
										{ __(
											'Create a test account',
											'woocommerce'
										) }
									</h3>
									<div>
										<p>
											{ interpolateComponents( {
												mixedString: __(
													"We'll create a test account for you so that you can begin {{link}}testing payments on your store{{/link}}. You'll need to complete setup later to accept real payments.",
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
												},
											} ) }
										</p>
									</div>
								</div>
							</div>
							<Button
								variant="secondary"
								isBusy={ isContinueButtonLoading }
								disabled={ isContinueButtonLoading }
								onClick={ () => {
									navigateToNextStep();
								} }
							>
								{ __( 'Create a test account', 'woocommerce' ) }
							</Button>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};

export default TestOrLiveAccountStep;
