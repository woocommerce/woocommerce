/**
 * External dependencies
 */
import { TotalsFooterItem } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { __ } from '@wordpress/i18n';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import clsx from 'clsx';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
/**
 * Internal dependencies
 */
import { OrderMetaSlotFill, CheckoutOrderSummaryFill } from './slotfills';
import { FormStepHeading } from '../../form-step';
import { useOrderSummaryToggle } from './use-order-summary-toggle';

const FrontendBlock = ( {
	children,
	className = '',
}: {
	children: JSX.Element | JSX.Element[];
	className?: string;
} ): JSX.Element | null => {
	const { cartTotals } = useStoreCart();
	const { isOpen, isLarge, ariaControlsId, toggleProps } =
		useOrderSummaryToggle();

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );
	const totalPrice = parseInt( cartTotals.total_price, 10 );

	// Render the summary once here in the block and once in the fill, so the
	// fill can be slotted elsewhere. Below the large breakpoint both render and
	// the CSS decides which one is visible.
	return (
		<>
			<div className={ className }>
				<div
					className={ clsx(
						'wc-block-components-checkout-order-summary__title',
						{
							'is-open': isOpen,
						}
					) }
					{ ...toggleProps }
				>
					<p
						className="wc-block-components-checkout-order-summary__title-text"
						role="heading"
						aria-level={ 2 }
					>
						{ __( 'Order summary', 'woocommerce' ) }
					</p>
					<FormattedMonetaryAmount
						currency={ totalsCurrency }
						value={ totalPrice }
						className="wc-block-components-checkout-order-summary__title-price"
					/>
					<span className="wc-block-components-checkout-order-summary__title-icon">
						<Icon icon={ isOpen ? chevronUp : chevronDown } />
					</span>
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
			</div>
			{ /* Render a second instance of the order summary in a different location for smaller screens.
			On large containers the CSS hides this fill, so rendering it while the
			width is still unknown is safe. */ }
			{ ! isLarge && (
				<CheckoutOrderSummaryFill>
					<div
						className={ `${ className } checkout-order-summary-block-fill-wrapper` }
					>
						<FormStepHeading>
							<>{ __( 'Order summary', 'woocommerce' ) }</>
						</FormStepHeading>
						<div className="checkout-order-summary-block-fill">
							{ children }
							<div className="wc-block-components-totals-wrapper">
								<TotalsFooterItem
									currency={ totalsCurrency }
									values={ cartTotals }
								/>
							</div>
							<OrderMetaSlotFill />
						</div>
					</div>
				</CheckoutOrderSummaryFill>
			) }
		</>
	);
};

export default FrontendBlock;
