/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	useCheckoutSubmit,
	useStoreCart,
} from '@woocommerce/base-context/hooks';
import { Icon, check } from '@wordpress/icons';
import Button from '@woocommerce/base-components/button';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	FormattedMonetaryAmount,
	Spinner,
} from '@woocommerce/blocks-components';

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
	const { cartTotals } = useStoreCart();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	const buttonLabel = (
		<>
			{ label }
			{ showPrice && (
				<div className="wc-block-components-checkout-place-order-button__separator">
					·
				</div>
			) }
			{ showPrice && (
				<div className="wc-block-components-checkout-place-order-button__price">
					<FormattedMonetaryAmount
						value={ cartTotals.total_price }
						currency={ totalsCurrency }
					/>
				</div>
			) }
		</>
	);

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
		>
			{ waitingForProcessing && <Spinner /> }
			{ ! waitingForProcessing &&
				( waitingForRedirect ? <Icon icon={ check } /> : buttonLabel ) }
		</Button>
	);
};

export default PlaceOrderButton;
