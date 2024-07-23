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
	separatorText?: string;
	fullWidth?: boolean | undefined;
	showPrice?: boolean | undefined;
}

const PlaceOrderButton = ( {
	label,
	separatorText = '·',
	fullWidth = false,
	showPrice = false,
}: PlaceOrderButton ): JSX.Element => {
	const {
		onSubmit,
		isCalculating,
		isDisabled,
		waitingForProcessing,
		waitingForRedirect,
	} = useCheckoutSubmit();

	// Merchant deleted the separator, leaving the · placeholder. It won't be undefined because it has a default value.
	if ( separatorText === '' ) {
		separatorText = '·';
	}

	const { cartTotals } = useStoreCart();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	const buttonLabel = (
		<div
			// Hide this from screen readers while the checkout is processing. The text will not be removed from the
			// DOM, it will just be hidden with CSS to maintain the button's size while the spinner appears.
			aria-hidden={ waitingForProcessing || waitingForRedirect }
			className={ clsx(
				'wc-block-components-checkout-place-order-button__text',
				{
					'wc-block-components-checkout-place-order-button__text--visually-hidden':
						waitingForProcessing || waitingForRedirect,
				}
			) }
		>
			{ label }
			{ showPrice && (
				<>
					<div className="wc-block-components-checkout-place-order-button__separator">
						{ separatorText }
					</div>
					<div className="wc-block-components-checkout-place-order-button__price">
						<FormattedMonetaryAmount
							value={ cartTotals.total_price }
							currency={ totalsCurrency }
						/>
					</div>
				</>
			) }
		</div>
	);

	return (
		<Button
			className={ clsx(
				'wc-block-components-checkout-place-order-button',
				{
					'wc-block-components-checkout-place-order-button--full-width':
						fullWidth,
				},
				{ 'wc-block-components-button--loading': waitingForProcessing }
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
			{ waitingForRedirect && <Icon icon={ check } /> }
			{ buttonLabel }
		</Button>
	);
};

export default PlaceOrderButton;
