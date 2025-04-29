/**
 * External dependencies
 */
import { TotalsFooterItem } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { __ } from '@wordpress/i18n';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { useId, useState } from '@wordpress/element';
import clsx from 'clsx';
/**
 * Internal dependencies
 */
import { OrderMetaSlotFill, CheckoutOrderSummaryFill } from './slotfills';
import { useContainerWidthContext } from '../../../../base/context';
import { FormattedMonetaryAmount } from '../../../../../../packages/components';
import { FormStepHeading } from '../../form-step';

const FrontendBlock = ( {
	children,
	className = '',
}: {
	children: JSX.Element | JSX.Element[];
	className?: string;
} ): JSX.Element | null => {
	const { cartTotals } = useStoreCart();
	const { isLarge } = useContainerWidthContext();
	const [ isOpen, setIsOpen ] = useState( false );

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );
	const totalPrice = parseInt( cartTotals.total_price, 10 );
	const ariaControlsId = useId();

	const orderSummaryProps = ! isLarge
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

	// Render the summary once here in the block and once in the fill. The fill can be slotted once elsewhere. The fill is only
	// rendered on small and mobile screens.
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
					{ ...orderSummaryProps }
				>
					<p
						className="wc-block-components-checkout-order-summary__title-text"
						role="heading"
					>
						{ __( 'Order summary', 'woocommerce' ) }
					</p>
					{ ! isLarge && (
						<>
							<FormattedMonetaryAmount
								currency={ totalsCurrency }
								value={ totalPrice }
							/>

							<Icon
								className="wc-block-components-checkout-order-summary__title-icon"
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
			</div>

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
