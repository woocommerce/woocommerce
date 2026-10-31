/**
 * External dependencies
 */
import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Button, Notice } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import {
	TESTING_ACCOUNT_STEP_ID,
	LIVE_ACCOUNT_STEP_ID,
} from '~/settings-payments/onboarding/providers/woopayments/steps';
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

	const testAccountStepActions = getStepByKey(
		TESTING_ACCOUNT_STEP_ID
	)?.actions;
	const canCreateTestAccount = testAccountStepActions?.finish?.href;

	return (
		<>
			<WooPaymentsStepHeader onClose={ closeModal } />
			<div className="settings-payments-onboarding-modal__step--content">
				<div className="woocommerce-payments-test-or-live-account-step__success_content_container">
					<div className="woocommerce-woopayments-modal__content woocommerce-payments-test-or-live-account-step__success_content">
						<h1 className="woocommerce-payments-test-or-live-account-step__success_content_title">
							{ __(
								"You're almost there — time to activate payments!",
								'woocommerce'
							) }
						</h1>
						{ currentStep?.errors &&
							currentStep.errors.length > 0 && (
								<Notice
									status="error"
									isDismissible={ false }
									className="woocommerce-payments-test-or-live-account-step__error"
									// Adding role="alert" for explicit screen reader announcement.
									// While @wordpress/components Notice uses speak() internally,
									// role="alert" provides better backwards compatibility with older AT.
									{ ...{ role: 'alert' } }
								>
									<p>
										{ currentStep.errors[ 0 ]?.message ||
											__(
												'Something went wrong. Please try again.',
												'woocommerce'
											) }
									</p>
								</Notice>
							) }
						<div className="woocommerce-payments-test-or-live-account-step__success-whats-next">
							<div className="woocommerce-woopayments-modal__content__item-flex">
								<img
									src={
										WC_ASSET_URL + 'images/icons/dollar.svg'
									}
									alt=""
									role="presentation"
								/>
								<div className="woocommerce-woopayments-modal__content__item-flex__description">
									<h3>
										{ __(
											'Activate payments in two easy steps',
											'woocommerce'
										) }
									</h3>
									<div>
										{ __(
											'Answer a few questions and verify your business details with our payments partner, including owner, address, and bank information.',
											'woocommerce'
										) }
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

									if ( canCreateTestAccount ) {
										// Mark the test account as finished.
										const actionUrl =
											testAccountStepActions?.finish
												?.href;

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
									} else {
										// If no test step is present, start the live account creation process directly.
										const liveAccountStep =
											getStepByKey(
												LIVE_ACCOUNT_STEP_ID
											);

										const liveAccountActionURL =
											liveAccountStep?.actions?.start
												?.href;

										if ( liveAccountActionURL ) {
											apiFetch( {
												url: liveAccountActionURL,
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
									}
								} }
								isBusy={ isContinueButtonLoading }
								disabled={ isContinueButtonLoading }
							>
								{ __( 'Activate payments', 'woocommerce' ) }
							</Button>
							<Link
								className="woocommerce-payments-test-or-live-account-step__learn-more"
								href="https://woocommerce.com/document/woopayments/startup-guide/#sign-up-process"
								target="_blank"
								rel="noreferrer"
								type="external"
								aria-label={ __(
									'Learn more about the WooPayments sign-up process (opens in a new tab)',
									'woocommerce'
								) }
							>
								{ __( 'Learn more', 'woocommerce' ) }
							</Link>
						</div>

						{ canCreateTestAccount && (
							<div className="woocommerce-payments-test-or-live-account-step__success-whats-next">
								<div className="woocommerce-woopayments-modal__content__item-flex">
									<img
										src={
											WC_ASSET_URL +
											'images/icons/post-list.svg'
										}
										alt=""
										role="presentation"
									/>
									<div className="woocommerce-woopayments-modal__content__item-flex__description">
										<h3>
											{ __(
												'Test payments first, activate later',
												'woocommerce'
											) }
										</h3>
										<div>
											<p>
												{ interpolateComponents( {
													mixedString: __(
														"A test account will be created for you to {{link}}test payments on your store{{/link}}. You'll still need to activate payments later to process real transactions.",
														'woocommerce'
													),
													components: {
														link: (
															<Link
																href="https://woocommerce.com/document/woopayments/testing-and-troubleshooting/test-accounts/"
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
									disabled={ isContinueButtonLoading }
									onClick={ () => {
										navigateToNextStep();
									} }
								>
									{ __( 'Test payments', 'woocommerce' ) }
								</Button>
							</div>
						) }
					</div>
				</div>
			</div>
		</>
	);
};

export default TestOrLiveAccountStep;
