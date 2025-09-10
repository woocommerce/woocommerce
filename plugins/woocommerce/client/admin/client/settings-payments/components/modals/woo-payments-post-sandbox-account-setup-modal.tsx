/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import interpolateComponents from '@automattic/interpolate-components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsSetupLiveAccountLink,
	recordPaymentsEvent,
} from '~/settings-payments/utils';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import {
	wooPaymentsExtensionSlug,
	wooPaymentsProviderId,
	wooPaymentsSuggestionId,
} from '~/settings-payments/constants';

interface WooPaymentsReadyToTestModalProps {
	/**
	 * Indicates if the modal is currently open.
	 */
	isOpen: boolean;
	/**
	 *  Indicates if the developer mode is on.
	 */
	devMode: boolean;
	/**
	 * Callback function to handle modal closure.
	 */
	onClose: () => void;
}

/**
 * A modal component displayed when a sandbox account is ready for testing payments. It provides
 * options to continue setting up the store or to activate payments.
 * Supports dev mode: makes modal shorter and activate payments button is not available in this case.
 */
export const WooPaymentsPostSandboxAccountSetupModal = ( {
	isOpen,
	devMode,
	onClose,
}: WooPaymentsReadyToTestModalProps ) => {
	const [ isActivatingPayments, setIsActivatingPayments ] = useState( false );
	const [ isContinuingStoreSetup, setIsContinuingStoreSetup ] =
		useState( false );

	/**
	 * Handles the "Activate Payments" action.
	 * Redirects the user to the WooPayments setup live account link.
	 */
	const handleActivatePayments = () => {
		// Record the event when the user clicks on the "Activate Payments" button.
		recordPaymentsEvent( 'switch_to_live_account_click', {
			provider_id: wooPaymentsProviderId,
			suggestion_id: wooPaymentsSuggestionId,
			provider_extension_slug: wooPaymentsExtensionSlug,
		} );

		setIsActivatingPayments( true );

		window.location.href = getWooPaymentsSetupLiveAccountLink();
	};

	/**
	 * Handles the "Continue Store Setup" action.
	 * Redirects the user to the WooCommerce admin store setup page.
	 */
	const handleContinueStoreSetup = () => {
		// Record the event when the user clicks on the "Continue Store Setup" button.
		recordPaymentsEvent( 'continue_store_setup_click', {
			provider_id: wooPaymentsProviderId,
			suggestion_id: wooPaymentsSuggestionId,
			provider_extension_slug: wooPaymentsExtensionSlug,
		} );

		setIsContinuingStoreSetup( true );

		window.location.href = getAdminLink( 'admin.php?page=wc-admin' );
	};

	return (
		<>
			{ isOpen && (
				<Modal
					title={ __(
						"You're ready to test payments!",
						'woocommerce'
					) }
					className="woocommerce-woopayments-modal"
					isDismissible={ true }
					onRequestClose={ onClose }
				>
					<div className="woocommerce-woopayments-modal__content">
						<div className="woocommerce-woopayments-modal__content__item">
							<div className="woocommerce-woopayments-modal__content__item__description">
								<p>
									{ interpolateComponents( {
										mixedString: __(
											"We've created a test account for you so that you can begin testing payments on your store. {{break/}}Not sure what to test? Take a look at {{link}}how to test payments{{/link}}.",
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
											break: <br />,
										},
									} ) }
								</p>
							</div>
						</div>
						<div className="woocommerce-woopayments-modal__content__item">
							<h2>{ __( "What's next:", 'woocommerce' ) }</h2>
						</div>
						<div className="woocommerce-woopayments-modal__content__item-flex">
							<img
								src={ WC_ASSET_URL + 'images/icons/store.svg' }
								alt=""
								role="presentation"
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
						{ ! devMode && (
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
											'Activate payments',
											'woocommerce'
										) }
									</h3>
									<div>
										<p>
											{ interpolateComponents( {
												mixedString: __(
													'Provide some additional details about your business so you can begin accepting real payments. {{link}}Learn more{{/link}}',
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
						) }
					</div>
					<div className="woocommerce-woopayments-modal__actions">
						<Button
							variant="primary"
							isBusy={ isContinuingStoreSetup }
							disabled={ isContinuingStoreSetup }
							onClick={ handleContinueStoreSetup }
						>
							{ __( 'Continue store setup', 'woocommerce' ) }
						</Button>
						{ ! devMode && (
							<Button
								variant="secondary"
								isBusy={ isActivatingPayments }
								disabled={ isActivatingPayments }
								onClick={ handleActivatePayments }
							>
								{ __( 'Activate payments', 'woocommerce' ) }
							</Button>
						) }
					</div>
				</Modal>
			) }
		</>
	);
};
