/**
 * External dependencies
 */
import { TotalsFooterItem } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { __ } from '@wordpress/i18n';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import {
	createPortal,
	useEffect,
	useLayoutEffect,
	useState,
} from '@wordpress/element';
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
	const {
		isOpen,
		hasContainerWidth,
		isLarge,
		closeSummary,
		ariaControlsId,
		toggleProps,
	} = useOrderSummaryToggle();
	const [ titleElement, setTitleElement ] = useState< HTMLDivElement | null >(
		null
	);
	const [ inlineHost, setInlineHost ] = useState< HTMLDivElement | null >(
		null
	);
	const [ actionAreaHost, setActionAreaHost ] =
		useState< HTMLDivElement | null >( null );
	const [ actionAreaAnchor, setActionAreaAnchor ] =
		useState< HTMLDivElement | null >( null );
	const [ isContentReady, setIsContentReady ] = useState( false );
	const [ contentContainer ] = useState( () => {
		const container = document.createElement( 'div' );
		container.className =
			'wc-block-components-checkout-order-summary__content-container';
		return container;
	} );

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );
	const totalPrice = parseInt( cartTotals.total_price, 10 );
	const showInline = ! hasContainerWidth || isLarge || isOpen;

	useEffect( () => {
		if (
			! hasContainerWidth ||
			isLarge ||
			! isOpen ||
			! titleElement ||
			! actionAreaAnchor ||
			typeof window.IntersectionObserver !== 'function'
		) {
			return;
		}

		let isTitleVisible = true;
		let isActionAreaNear = false;
		const observer = new window.IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.target === titleElement ) {
						isTitleVisible = entry.isIntersecting;
					} else if ( entry.target === actionAreaAnchor ) {
						isActionAreaNear = entry.isIntersecting;
					}
				} );

				if ( ! isTitleVisible && isActionAreaNear ) {
					closeSummary();
				}
			},
			{ rootMargin: '0px 0px 25% 0px' }
		);

		observer.observe( titleElement );
		observer.observe( actionAreaAnchor );

		return () => observer.disconnect();
	}, [
		actionAreaAnchor,
		closeSummary,
		hasContainerWidth,
		isLarge,
		isOpen,
		titleElement,
	] );

	// Attach the empty container before mounting portal children. Later moves
	// preserve the same mounted extension component instances.
	useLayoutEffect( () => {
		const destination = showInline ? inlineHost : actionAreaHost;

		if ( destination ) {
			destination.appendChild( contentContainer );
			setIsContentReady( true );
		}
	}, [ actionAreaHost, contentContainer, inlineHost, showInline ] );

	useLayoutEffect( () => {
		return () => contentContainer.remove();
	}, [ contentContainer ] );

	return (
		<>
			<div className={ className }>
				<div
					ref={ setTitleElement }
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
					ref={ setInlineHost }
					className={ clsx(
						'wc-block-components-checkout-order-summary__content',
						{
							'is-open': isOpen,
						}
					) }
					id={ ariaControlsId }
				/>
			</div>
			<CheckoutOrderSummaryFill>
				<div
					ref={ setActionAreaAnchor }
					aria-hidden={ showInline }
					className={ clsx(
						className,
						'checkout-order-summary-block-fill-wrapper',
						{
							'is-content-inline': showInline,
						}
					) }
				>
					<FormStepHeading>
						<>{ __( 'Order summary', 'woocommerce' ) }</>
					</FormStepHeading>
					<div
						ref={ setActionAreaHost }
						className="checkout-order-summary-block-fill"
					/>
				</div>
			</CheckoutOrderSummaryFill>
			{ isContentReady &&
				createPortal(
					<>
						{ children }
						<div className="wc-block-components-totals-wrapper">
							<TotalsFooterItem
								currency={ totalsCurrency }
								values={ cartTotals }
							/>
						</div>
						<OrderMetaSlotFill />
					</>,
					contentContainer
				) }
		</>
	);
};

export default FrontendBlock;
