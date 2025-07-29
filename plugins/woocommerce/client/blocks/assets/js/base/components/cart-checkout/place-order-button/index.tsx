/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	useCheckoutSubmit,
	useStoreCart,
} from '@woocommerce/base-context/hooks';
import { check } from '@wordpress/icons';
import { Icon } from '@wordpress/components';
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

	const { cartTotals, cartIsLoading } = useStoreCart();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	const buttonLabel = (
		<div
			className={
				'wc-block-components-checkout-place-order-button__text'
			}
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
				{
					'wc-block-components-checkout-place-order-button--loading':
						waitingForProcessing || waitingForRedirect,
				}
			) }
			onClick={ onSubmit }
			disabled={
				isCalculating ||
				isDisabled ||
				waitingForProcessing ||
				waitingForRedirect ||
				cartIsLoading
			}
		>
			{ waitingForProcessing && <Spinner /> }
			{ waitingForRedirect && (
				<Icon
					className="wc-block-components-checkout-place-order-button__icon"
					icon={ check }
				/>
			) }
			{ buttonLabel }
		</Button>
	);
};

export default PlaceOrderButton;
