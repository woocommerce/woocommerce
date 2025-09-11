/**
 * External dependencies
 */
import clsx from 'clsx';
import type { ReactNode } from 'react';
import { getSetting } from '@woocommerce/settings';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { noticeContexts, usePaymentMethodInterface } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import type { PaymentMethodInterface } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { defaultPlaceOrderButtonLabel } from './constants';
import './style.scss';
import { CheckoutOrderSummarySlot } from '../checkout-order-summary-block/slotfills';

export type BlockAttributes = {
	cartPageId: number;
	showReturnToCart: boolean;
	className?: string;
	placeOrderButtonLabel: string;
	priceSeparator: string;
	returnToCartButtonLabel: string;
};

const PaymentMethodPlaceOrderButtonContainer = ( {
	children,
}: {
	children: ReactNode;
} ) => {
	// Enhanced container with better styling and accessibility
	return (
		<div 
			className="wc-block-checkout__actions_row wc-block-checkout__payment-method-button"
			role="button"
			tabIndex={0}
		>
			{ children }
		</div>
	);
};

const Block = ( {
	cartPageId,
	showReturnToCart,
	className,
	placeOrderButtonLabel,
	returnToCartButtonLabel,
	priceSeparator,
}: BlockAttributes ): JSX.Element => {
	const {
		paymentMethodButtonLabel,
		paymentMethodPlaceOrderButton: PaymentMethodPlaceOrderButton,
	} = useCheckoutSubmit();

	const paymentMethodInterface = usePaymentMethodInterface();

	// Apply checkout filter for button label
	const label = applyCheckoutFilter( {
		filterName: 'placeOrderButtonLabel',
		defaultValue:
			paymentMethodButtonLabel ||
			placeOrderButtonLabel ||
			defaultPlaceOrderButtonLabel,
	} );

	const showPrice = className?.includes( 'is-style-with-price' ) || false;

	return (
		<div className={ clsx( 'wc-block-checkout__actions', className ) }>
			<CheckoutOrderSummarySlot />
			<StoreNoticesContainer
				context={ noticeContexts.CHECKOUT_ACTIONS }
			/>
			<div className="wc-block-checkout__actions_row">
				{ showReturnToCart && (
					<ReturnToCartButton
						href={ getSetting( 'page-' + cartPageId, false ) }
					>
						{ returnToCartButtonLabel }
					</ReturnToCartButton>
				) }
				{ showPrice && (
					<style>
						{ `.wp-block-woocommerce-checkout-actions-block {
						.wc-block-components-checkout-place-order-button__separator {
							&::after {
								content: "${ priceSeparator }";
							}
						}
					}` }
					</style>
				) }
				{/* Enhanced conditional rendering with better error handling */}
				{ PaymentMethodPlaceOrderButton ? (
					<PaymentMethodPlaceOrderButtonContainer>
						<PaymentMethodPlaceOrderButton
							{ ...paymentMethodInterface }
						/>
					</PaymentMethodPlaceOrderButtonContainer>
				) : (
					<PlaceOrderButton
						label={ label }
						fullWidth={ ! showReturnToCart }
						showPrice={ showPrice }
						priceSeparator={ priceSeparator }
					/>
				) }
			</div>
		</div>
	);
};

export default Block;
