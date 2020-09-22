/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCheckoutSubmit } from '@woocommerce/base-hooks';
import { Icon, done } from '@woocommerce/icons';
import Button from '@woocommerce/base-components/button';

const PlaceOrderButton = () => {
	const {
		submitButtonText,
		onSubmit,
		isCalculating,
		waitingForProcessing,
		waitingForRedirect,
	} = useCheckoutSubmit();

	return (
		<Button
			className="wc-block-components-checkout-place-order-button"
			onClick={ onSubmit }
			disabled={
				isCalculating || waitingForProcessing || waitingForRedirect
			}
			showSpinner={ waitingForProcessing }
		>
			{ waitingForRedirect ? (
				<Icon
					srcElement={ done }
					alt={ __( 'Done', 'woocommerce' ) }
				/>
			) : (
				submitButtonText
			) }
		</Button>
	);
};

export default PlaceOrderButton;
