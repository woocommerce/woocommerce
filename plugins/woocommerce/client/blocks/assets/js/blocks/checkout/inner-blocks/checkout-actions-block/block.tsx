/**
 * External dependencies
 */
import clsx from 'clsx';
import { getSetting } from '@woocommerce/settings';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutSubmit,
	usePaymentMethodInterface,
} from '@woocommerce/base-context/hooks';
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { CART_URL } from '@woocommerce/block-settings';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';

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
	const { paymentMethodButtonLabel, paymentMethodPlaceOrderButton } =
		useCheckoutSubmit();

	// Get the full payment method interface for custom buttons
	const paymentMethodInterface = usePaymentMethodInterface();

	// Check if a saved payment token is selected
	const activeSavedToken = useSelect( ( select ) => {
		const store = select( paymentStore );
		return store.getActiveSavedToken();
	}, [] );

	// not showing the custom button when a saved token is selected - only when the payment method is selected from the list.
	const CustomButtonComponent = activeSavedToken
		? null
		: paymentMethodPlaceOrderButton;

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
				{ CustomButtonComponent ? (
					<CustomButtonComponent { ...paymentMethodInterface } />
				) : (
					<PlaceOrderButton
						label={ label }
						fullWidth={ ! shouldShowReturnToCart }
						showPrice={ showPrice }
						priceSeparator={ priceSeparator }
					/>
				) }
			</div>
		</div>
	);
};

export default Block;
