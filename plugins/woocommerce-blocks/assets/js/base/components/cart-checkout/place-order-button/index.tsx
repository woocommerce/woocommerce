/**
 * External dependencies
 */
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { Icon, check } from '@wordpress/icons';
import Button from '@woocommerce/base-components/button';

const PlaceOrderButton = (): JSX.Element => {
	const {
		submitButtonText,
		onSubmit,
		isCalculating,
		isDisabled,
		waitingForProcessing,
		waitingForRedirect,
	} = useCheckoutSubmit();

	return (
		<Button
			className="wc-block-components-checkout-place-order-button"
			onClick={ onSubmit }
			disabled={
				isCalculating ||
				isDisabled ||
				waitingForProcessing ||
				waitingForRedirect
			}
			showSpinner={ waitingForProcessing }
		>
			{ waitingForRedirect ? <Icon icon={ check } /> : submitButtonText }
		</Button>
	);
};

export default PlaceOrderButton;
