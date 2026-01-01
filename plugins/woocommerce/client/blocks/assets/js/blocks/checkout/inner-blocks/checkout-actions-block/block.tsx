/**
 * External dependencies
 */
import clsx from 'clsx';
import { getSetting } from '@woocommerce/settings';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { CART_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { defaultPlaceOrderButtonLabel } from './constants';
import { CheckoutOrderSummarySlot } from '../checkout-order-summary-block/slotfills';
import './style.scss';

export type BlockAttributes = {
	cartPageId: number;
	showReturnToCart: boolean;
	className?: string;
	placeOrderButtonLabel: string;
	priceSeparator: string;
	returnToCartButtonLabel: string;
};

const Block = ( {
	cartPageId,
	showReturnToCart,
	className,
	placeOrderButtonLabel,
	returnToCartButtonLabel,
	priceSeparator,
}: BlockAttributes ) => {
	const { paymentMethodButtonLabel } = useCheckoutSubmit();

	const label = applyCheckoutFilter( {
		filterName: 'placeOrderButtonLabel',
		defaultValue:
			paymentMethodButtonLabel ||
			placeOrderButtonLabel ||
			defaultPlaceOrderButtonLabel,
	} );

	const cartHref = getSetting( 'page-' + cartPageId, false );
	const cartLink = cartHref || CART_URL;
	const shouldShowReturnToCart = cartLink && showReturnToCart;

	const showPrice = className?.includes( 'is-style-with-price' ) || false;

	return (
		<div className={ clsx( 'wc-block-checkout__actions', className ) }>
			<CheckoutOrderSummarySlot />
			<StoreNoticesContainer
				context={ noticeContexts.CHECKOUT_ACTIONS }
			/>
			<div
				className={ clsx( 'wc-block-checkout__actions_row', {
					'wc-block-checkout__actions_row--justify-flex-end':
						! shouldShowReturnToCart,
				} ) }
			>
				{ shouldShowReturnToCart && (
					<ReturnToCartButton href={ cartLink }>
						{ returnToCartButtonLabel }
					</ReturnToCartButton>
				) }
				<PlaceOrderButton
					label={ label }
					fullWidth={ ! shouldShowReturnToCart }
					showPrice={ showPrice }
					priceSeparator={ priceSeparator }
				/>
			</div>
		</div>
	);
};

export default Block;
