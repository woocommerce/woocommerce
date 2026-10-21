/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';
import { CART_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { defaultPlaceOrderButtonLabel } from './constants';
import { CheckoutOrderSummarySlot } from '../checkout-order-summary-block/slotfills';
import TermsAndConditions from '../checkout-terms-block/frontend';
import './style.scss';

export type BlockAttributes = {
	cartPageId: number;
	showReturnToCart: boolean;
	className?: string;
	placeOrderButtonLabel: string;
	priceSeparator: string;
	returnToCartButtonLabel: string;
	showTerms?: boolean;
	termsCheckbox?: boolean;
	termsText?: string;
};

const Block = ( {
	cartPageId,
	showReturnToCart,
	className,
	placeOrderButtonLabel,
	returnToCartButtonLabel,
	priceSeparator,
	showTerms,
	termsCheckbox,
	termsText,
}: BlockAttributes ) => {
	const { paymentMethodButtonLabel, paymentMethodPlaceOrderButton } =
		useCheckoutSubmit();

	const activeSavedToken = useSelect(
		( select ) => select( paymentStore ).getActiveSavedToken(),
		[]
	);

	// not showing the custom button when a saved token is selected - only when the payment method is selected from the list.
	const CustomButtonComponent = activeSavedToken
		? undefined
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

	// A required checkbox goes above the button; consent text goes below it. Extensions can hide the terms
	// with the woocommerce_checkout_show_terms filter.
	const terms =
		showTerms && getSetting( 'checkoutShowTerms', true ) ? (
			<TermsAndConditions
				text={ termsText || '' }
				checkbox={ !! termsCheckbox }
				showSeparator={ false }
				// Matches the Terms block wrapper, which extensions target.
				className="wp-block-woocommerce-checkout-terms-block"
			/>
		) : null;

	return (
		<div className={ clsx( 'wc-block-checkout__actions', className ) }>
			<CheckoutOrderSummarySlot />
			<StoreNoticesContainer
				context={ noticeContexts.CHECKOUT_ACTIONS }
			/>
			{ termsCheckbox && terms }
			<div
				className={ clsx( 'wc-block-checkout__actions_row', {
					'wc-block-checkout__actions_row--justify-flex-end':
						! shouldShowReturnToCart,
					'wc-block-checkout__actions_row--with-return-to-cart':
						shouldShowReturnToCart,
				} ) }
			>
				{ shouldShowReturnToCart && (
					<ReturnToCartButton href={ cartLink }>
						{ returnToCartButtonLabel }
					</ReturnToCartButton>
				) }
				<PlaceOrderButton
					CustomButtonComponent={ CustomButtonComponent }
					label={ label }
					fullWidth
					showPrice={ showPrice }
					priceSeparator={ priceSeparator }
				/>
				{ ! termsCheckbox && terms }
			</div>
		</div>
	);
};

export default Block;
