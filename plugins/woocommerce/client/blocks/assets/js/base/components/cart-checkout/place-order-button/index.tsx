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

interface PlaceOrderButtonProps {
	label: string;
	fullWidth?: boolean;
	showPrice?: boolean;
	priceSeparator?: string;
}

const PlaceOrderButton = ( {
	label,
	fullWidth = false,
	showPrice = false,
	priceSeparator = '·',
}: PlaceOrderButtonProps ): JSX.Element => {
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
					<style>
						{ `.wp-block-woocommerce-checkout-actions-block {
							.wc-block-components-checkout-place-order-button__separator {
								&::after {
									content: "${ priceSeparator }";
								}
							}
						}` }
					</style>
					<div className="wc-block-components-checkout-place-order-button__separator" />
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
				{ 'wc-blocks-components-button--loading': waitingForProcessing }
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
			{ waitingForProcessing && <Spinner /> }
			{ waitingForRedirect && <Icon icon={ check } /> }
			{ buttonLabel }
		</Button>
	);
};

export default PlaceOrderButton;
