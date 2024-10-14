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

/**
 * Internal dependencies
 */
import { defaultPlaceOrderButtonLabel } from './constants';
import './style.scss';

export type BlockAttributes = {
	cartPageId: number;
	showReturnToCart: boolean;
	className?: string;
	placeOrderButtonLabel: string;
	showPrice: boolean;
};

const Block = ( {
	cartPageId,
	showReturnToCart,
	className,
	showPrice,
	placeOrderButtonLabel,
}: BlockAttributes ): JSX.Element => {
	const { paymentMethodButtonLabel } = useCheckoutSubmit();

	const label = applyCheckoutFilter( {
		filterName: 'placeOrderButtonLabel',
		defaultValue:
			paymentMethodButtonLabel ||
			placeOrderButtonLabel ||
			defaultPlaceOrderButtonLabel,
	} );

	return (
		<div className={ clsx( 'wc-block-checkout__actions', className ) }>
			<StoreNoticesContainer
				context={ noticeContexts.CHECKOUT_ACTIONS }
			/>
			<div className="wc-block-checkout__actions_row">
				{ showReturnToCart && (
					<ReturnToCartButton
						link={ getSetting( 'page-' + cartPageId, false ) }
					/>
				) }
				<PlaceOrderButton
					label={ label }
					fullWidth={ ! showReturnToCart }
					showPrice={ showPrice }
				/>
			</div>
		</div>
	);
};

export default Block;
