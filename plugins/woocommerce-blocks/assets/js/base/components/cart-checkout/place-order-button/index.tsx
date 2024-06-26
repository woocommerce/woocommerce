/**
 * External dependencies
 */
import clsx from 'clsx';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { Icon, check } from '@wordpress/icons';
import Button from '@woocommerce/base-components/button';

interface PlaceOrderButton {
	label: string;
	fullWidth?: boolean | undefined;
	showPrice?: boolean | undefined;
}

const PlaceOrderButton = ( {
	label,
	fullWidth = false,
	showPrice = true,
}: PlaceOrderButton ): JSX.Element => {
	const {
		onSubmit,
		isCalculating,
		isDisabled,
		waitingForProcessing,
		waitingForRedirect,
	} = useCheckoutSubmit();

	const buttonLabel = `${ label } - ${ showPrice ? '€0.00' : '' }`;

	return (
		<Button
			className={ clsx(
				'wc-block-components-checkout-place-order-button',
				{
					'wc-block-components-checkout-place-order-button--full-width':
						fullWidth,
				},
				{
					'wc-block-components-checkout-place-order-button--with-price':
						showPrice,
				}
			) }
			onClick={ onSubmit }
			disabled={
				isCalculating ||
				isDisabled ||
				waitingForProcessing ||
				waitingForRedirect
			}
			showSpinner={ waitingForProcessing }
		>
			{ waitingForRedirect ? <Icon icon={ check } /> : buttonLabel }
		</Button>
	);
};

export default PlaceOrderButton;
