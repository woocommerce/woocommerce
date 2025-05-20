/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { PaymentIncentive } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getWooPaymentsSetupLiveAccountLink } from '~/settings-payments/utils';

interface ActivatePaymentsButtonProps {
	/**
	 * Callback used when an incentive is accepted.
	 *
	 * @param id Incentive ID.
	 */
	acceptIncentive: ( id: string ) => void;
	/**
	 * The text of the button.
	 */
	buttonText?: string;
	/**
	 * Incentive data. If provided, the incentive will be accepted when the button is clicked.
	 */
	incentive?: PaymentIncentive | null;
	/**
	 * ID of the plugin that is being installed.
	 */
	installingPlugin: string | null;
	/**
	 * Function to set the onboarding modal open.
	 */
	setOnboardingModalOpen: ( isOnboardingModalOpen: boolean ) => void;
	/**
	 * The onboarding type for the gateway.
	 */
	onboardingType?: string;
}

/**
 * A button component that initiates the payment activation process.
 * If incentive data is provided, it will trigger the `acceptIncentive` callback with the incentive ID before redirecting to setup live payments link.
 */
export const ActivatePaymentsButton = ( {
	acceptIncentive,
	installingPlugin,
	buttonText = __( 'Activate payments', 'woocommerce' ),
	incentive = null,
	setOnboardingModalOpen,
	onboardingType,
}: ActivatePaymentsButtonProps ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );

	const activatePayments = () => {
		setIsUpdating( true );

		if ( incentive ) {
			acceptIncentive( incentive.promo_id );
		}

		if ( onboardingType === 'native_in_context' ) {
			// Open the onboarding modal.
			setOnboardingModalOpen( true );
		} else {
			window.location.href = getWooPaymentsSetupLiveAccountLink();
		}
	};

	return (
		<Button
			variant={ 'primary' }
			isBusy={ isUpdating }
			disabled={ isUpdating || !! installingPlugin }
			onClick={ activatePayments }
		>
			{ buttonText }
		</Button>
	);
};
