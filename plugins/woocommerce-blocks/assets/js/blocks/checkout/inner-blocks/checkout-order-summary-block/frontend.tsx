/**
 * External dependencies
 */
import { TotalsFooterItem } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import type { Currency, CartResponseTotals } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { useEffect, useId, useState } from '@wordpress/element';
import clsx from 'clsx';
/**
 * Internal dependencies
 */
import { OrderMetaSlotFill, CheckoutOrderSummaryFill } from './slotfills';
import { useContainerWidthContext } from '../../../../base/context';
import { FormattedMonetaryAmount } from '../../../../../../packages/components';
const CheckoutOrderSummary = ( {
	className,
	children,
	totalsCurrency,
	cartTotals,
}: {
	className?: string;
	children: JSX.Element | JSX.Element[];
	totalsCurrency: Currency;
	cartTotals: CartResponseTotals;
} ) => {
	return <>{ children }</>;
};

const FrontendBlock = ( {
	children,
	className = '',
}: {
	children: JSX.Element | JSX.Element[];
	className?: string;
} ): JSX.Element | null => {
	const { cartTotals } = useStoreCart();
	const { isSmall, isMobile } = useContainerWidthContext();
	const [ isOpen, setIsOpen ] = useState( false );

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );
	const totalPrice = parseInt( cartTotals.total_price, 10 );
	const ariaControlsId = useId();

	const orderSummaryProps =
		isSmall || isMobile
			? {
					role: 'button',
					onClick: () => setIsOpen( ! isOpen ),
					'aria-expanded': isOpen,
					'aria-controls': ariaControlsId,
					tabIndex: 0,
					onKeyDown: ( event: React.KeyboardEvent ) => {
						if ( event.key === 'Enter' || event.key === ' ' ) {
							setIsOpen( ! isOpen );
						}
					},
			  }
			: {};

	// Render once here and once in the fill. The fill can be slotted once elsewhere.
	return (
		<>
			<div className={ className }>
				<CheckoutOrderSummary
					totalsCurrency={ totalsCurrency }
					cartTotals={ cartTotals }
				>
					<div
						className={ clsx(
							'wc-block-components-checkout-order-summary__title',
							{
								'is-open': isOpen,
							}
						) }
						{ ...orderSummaryProps }
					>
						<p
							className="wc-block-components-checkout-order-summary__title-text"
							role="heading"
						>
							{ __( 'Order summary', 'woocommerce' ) }
						</p>
						{ ( isSmall || isMobile ) && (
							<>
								<FormattedMonetaryAmount
									currency={ totalsCurrency }
									value={ totalPrice }
								/>

								<Icon
									icon={ isOpen ? chevronUp : chevronDown }
								/>
							</>
						) }
					</div>
					<div
						className={ clsx(
							'wc-block-components-checkout-order-summary__content',
							{
								'is-open': isOpen,
							}
						) }
						id={ ariaControlsId }
					>
						{ children }
						<div className="wc-block-components-totals-wrapper">
							<TotalsFooterItem
								currency={ totalsCurrency }
								values={ cartTotals }
							/>
						</div>
						<OrderMetaSlotFill />
					</div>
				</CheckoutOrderSummary>
			</div>
			<CheckoutOrderSummaryFill>
				<CheckoutOrderSummary
					className={ className }
					totalsCurrency={ totalsCurrency }
					cartTotals={ cartTotals }
				>
					<>
						{ children }
						<div className="wc-block-components-totals-wrapper">
							<TotalsFooterItem
								currency={ totalsCurrency }
								values={ cartTotals }
							/>
						</div>
						<OrderMetaSlotFill />
					</>
				</CheckoutOrderSummary>
			</CheckoutOrderSummaryFill>
		</>
	);
};

export default FrontendBlock;
