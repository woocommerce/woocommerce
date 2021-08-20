/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { Icon, done } from '@woocommerce/icons';
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
			{ waitingForRedirect ? (
				<Icon
					srcElement={ done }
					alt={ __( 'Done', 'woo-gutenberg-products-block' ) }
				/>
			) : (
				submitButtonText
			) }
		</Button>
	);
};

export default PlaceOrderButton;
