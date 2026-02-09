/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	useCheckoutSubmit,
	usePaymentMethodInterface,
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
import { useValidateCheckout } from '@woocommerce/blocks-checkout';
import type { CustomPlaceOrderButtonComponent } from '@woocommerce/types';
import { useEditorContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';

interface PlaceOrderButtonProps {
	label: React.ReactNode;
	fullWidth?: boolean;
	showPrice?: boolean;
	priceSeparator?: string;
	// eslint-disable-next-line @typescript-eslint/naming-convention
	CustomButtonComponent?: CustomPlaceOrderButtonComponent;
}

const PlaceOrderButton = ( {
	label,
	fullWidth = false,
	showPrice = false,
	priceSeparator = '·',
	CustomButtonComponent,
}: PlaceOrderButtonProps ): JSX.Element => {
	const {
		onSubmit,
		isCalculating,
		isDisabled,
		waitingForProcessing,
		waitingForRedirect,
	} = useCheckoutSubmit();

	const paymentMethodInterface = usePaymentMethodInterface();
	const validateCheckout = useValidateCheckout();
	const { isEditor, isPreview = false } = useEditorContext();

	const { cartTotals, cartIsLoading } = useStoreCart();

	// when provided, the `CustomButtonComponent` should take precedence over the default button.
	if ( CustomButtonComponent ) {
		return (
			<CustomButtonComponent
				waitingForProcessing={ waitingForProcessing }
				waitingForRedirect={ waitingForRedirect }
				disabled={
					isCalculating ||
					isDisabled ||
					waitingForProcessing ||
					waitingForRedirect ||
					cartIsLoading
				}
				isEditor={ isEditor }
				isPreview={ isPreview }
				validate={ validateCheckout }
				{ ...paymentMethodInterface }
			/>
		);
	}

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
								currency={ getCurrencyFromPriceResponse(
									cartTotals
								) }
							/>
						</div>
					</>
				) }
			</div>
		</Button>
	);
};

export default PlaceOrderButton;
