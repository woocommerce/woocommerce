/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	paymentSettingsStore,
	woopaymentsOnboardingStore,
} from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { recordPaymentsEvent } from '~/settings-payments/utils';
import {
	wooPaymentsExtensionSlug,
	wooPaymentsProviderId,
	wooPaymentsSuggestionId,
} from '~/settings-payments/constants';

interface WooPaymentsResetAccountModalProps {
	/**
	 * Indicates if the modal is currently open.
	 */
	isOpen: boolean;
	/**
	 * Callback function to handle modal closure.
	 */
	onClose: () => void;

	/**
	 * Indicates if there is a connected account.
	 */
	hasAccount?: boolean;

	/**
	 * Indicates if the account is a test-drive/sandbox account.
	 */
	isTestMode?: boolean;

	/**
	 * Indicate if the reset flow is embedded (ie inside NOX).
	 */
	isEmbeddedResetFlow?: boolean;

	/**
	 * URL for the reset account API endpoint.
	 */
	resetUrl?: string;
}

/**
 * A modal component that allows users to reset their WooPayments account.
 */
export const WooPaymentsResetAccountModal = ( {
	isOpen,
	onClose,
	hasAccount,
	isTestMode,
	isEmbeddedResetFlow = false,
	resetUrl,
}: WooPaymentsResetAccountModalProps ) => {
	const [ isResettingAccount, setIsResettingAccount ] = useState( false );
	const { invalidateResolutionForStoreSelector: invalidatePaymentGateways } =
		useDispatch( paymentSettingsStore );
	const {
		invalidateResolutionForStoreSelector: invalidateWooPaymentsOnboarding,
	} = useDispatch( woopaymentsOnboardingStore );
	const { createNotice } = useDispatch( 'core/notices' );

	/**
	 * Handles the "Reset Account" action.
	 */
	const handleResetAccount = () => {
		setIsResettingAccount( true );

		if ( ! resetUrl ) {
			recordPaymentsEvent( 'provider_reset_onboarding_failed', {
				provider_id: wooPaymentsProviderId,
				suggestion_id: wooPaymentsSuggestionId,
				provider_extension_slug: wooPaymentsExtensionSlug,
				reason: 'missing_reset_url',
			} );
			createNotice(
				'error',
				__(
					'Failed to reset: missing reset URL. Please refresh and try again.',
					'woocommerce'
				),
				{ isDismissible: true }
			);
			setIsResettingAccount( false );
			return;
		}

		apiFetch( {
			url: resetUrl,
			method: 'POST',
		} )
			.then( () => {
				recordPaymentsEvent( 'provider_reset_onboarding_success', {
					provider_id: wooPaymentsProviderId,
					suggestion_id: wooPaymentsSuggestionId,
					provider_extension_slug: wooPaymentsExtensionSlug,
				} );
				// Refresh the providers store.
				invalidatePaymentGateways( 'getPaymentProviders' );
				// Refresh the WooPayments in-context onboarding store.
				invalidateWooPaymentsOnboarding( 'getOnboardingData' );
			} )
			.catch( () => {
				recordPaymentsEvent( 'provider_reset_onboarding_failed', {
					provider_id: wooPaymentsProviderId,
					suggestion_id: wooPaymentsSuggestionId,
					provider_extension_slug: wooPaymentsExtensionSlug,
					reason: 'error',
				} );
				createNotice(
					'error',
					hasAccount
						? sprintf(
								/* translators: %s: Provider name */
								__(
									'Failed to reset your %s account.',
									'woocommerce'
								),
								'WooPayments'
						  )
						: sprintf(
								/* translators: %s: Provider name */
								__(
									'Failed to reset your %s onboarding.',
									'woocommerce'
								),
								'WooPayments'
						  ),
					{
						isDismissible: true,
					}
				);
			} )
			.finally( () => {
				setIsResettingAccount( false );
				onClose();
			} );
	};

	let title: string;
	let content: string;
	let buttonText: string;
	if ( hasAccount ) {
		title = isTestMode
			? __( 'Reset your test account', 'woocommerce' )
			: __( 'Reset your account', 'woocommerce' );

		content = isTestMode
			? sprintf(
					/* translators: %s: Provider name */
					__(
						'When you reset your test account, all payment data — including your %s account details, test transactions, and payouts history — will be lost. Your order history will remain. This action cannot be undone, but you can create a new test account at any time.',
						'woocommerce'
					),
					'WooPayments'
			  )
			: sprintf(
					/* translators: %s: Provider name */
					__(
						'When you reset your account, all payment data — including your %s account details, test transactions, and payouts history — will be lost. Your order history will remain. This action cannot be undone, but you can create a new test account at any time.',
						'woocommerce'
					),
					'WooPayments'
			  );
		if ( isEmbeddedResetFlow ) {
			// If resetting the account from NOX, override the content.
			content = sprintf(
				/* translators: 1: Provider name, 2: Provider name */
				__(
					'You need to reset your test account to continue onboarding with %1$s. This will create a new test account and reset any existing %2$s account details and test transactions.',
					'woocommerce'
				),
				'WooPayments',
				'WooPayments'
			);
		}

		buttonText = isTestMode
			? __( 'Yes, reset test account', 'woocommerce' )
			: __( 'Yes, reset account', 'woocommerce' );
	} else {
		title = __( 'Reset onboarding', 'woocommerce' );
		content = sprintf(
			/* translators: %s: Provider name */
			__(
				'When you reset the %s onboarding your progress and the provided data will be lost. This action cannot be undone, but you can restart the onboarding any time.',
				'woocommerce'
			),
			'WooPayments'
		);
		buttonText = __( 'Yes, reset onboarding', 'woocommerce' );
	}
	return (
		<>
			{ isOpen && (
				<Modal
					title={ title }
					className="woocommerce-woopayments-modal"
					isDismissible={ true }
					onRequestClose={ onClose }
				>
					<div className="woocommerce-woopayments-modal__content">
						<div className="woocommerce-woopayments-modal__content__item">
							<div>
								<span>{ content }</span>
							</div>
						</div>
						<div className="woocommerce-woopayments-modal__content__item">
							<h3>
								{ __(
									"Are you sure you'd like to continue?",
									'woocommerce'
								) }
							</h3>
						</div>
					</div>
					<div className="woocommerce-woopayments-modal__actions">
						<Button
							className={ isEmbeddedResetFlow ? '' : 'danger' }
							variant={
								isEmbeddedResetFlow ? 'primary' : 'secondary'
							}
							isBusy={ isResettingAccount }
							disabled={ isResettingAccount }
							onClick={ () => {
								recordPaymentsEvent(
									'provider_reset_onboarding_confirmation_click',
									{
										provider_id: wooPaymentsProviderId,
										suggestion_id: wooPaymentsSuggestionId,
										provider_extension_slug:
											wooPaymentsExtensionSlug,
									}
								);
								handleResetAccount();
							} }
						>
							{ buttonText }
						</Button>
					</div>
				</Modal>
			) }
		</>
	);
};
