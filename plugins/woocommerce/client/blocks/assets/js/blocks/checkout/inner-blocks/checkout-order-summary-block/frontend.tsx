/**
 * External dependencies
 */
import { TotalsFooterItem } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useSettledOrderSummaryValue,
} from '@woocommerce/base-context/hooks';
import { startLayoutChange } from '@woocommerce/base-utils/layout-transition';
import { __ } from '@wordpress/i18n';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import {
	createPortal,
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import clsx from 'clsx';
import RollingPrice from '@woocommerce/base-components/rolling-price';
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
	const { cartTotals, cartIsLoading } =
		useSettledOrderSummaryValue( useStoreCart() );
	// The stable content container moves between these hosts without remounting its portal children.
	const [ inlineHost, setInlineHost ] = useState< HTMLDivElement | null >(
		null
	);
	// The shopper's toggle runs as a layout transition: the content wipes in or out and the checkout below slides.
	// Where one can't run, opening fades the content in and closing applies at once.
	const [ isFadingIn, setIsFadingIn ] = useState( false );
	const {
		isOpen,
		hasContainerWidth,
		isLarge,
		closeSummary,
		ariaControlsId,
		toggleProps,
	} = useOrderSummaryToggle( ( toggle, wasOpen ) =>
		setIsFadingIn(
			! startLayoutChange( toggle, {
				element: () => inlineHost,
				exit: wasOpen,
			} )
		)
	);
	const [ titleElement, setTitleElement ] = useState< HTMLDivElement | null >(
		null
	);
	const [ checkoutActionsHost, setCheckoutActionsHost ] =
		useState< HTMLDivElement | null >( null );
	// This wrapper is observed to detect proximity and preserve scroll position when the container moves.
	const [ checkoutActionsAnchor, setCheckoutActionsAnchor ] =
		useState< HTMLDivElement | null >( null );
	const [ isContentReady, setIsContentReady ] = useState( false );
	const checkoutActionsTopBeforeMove = useRef< number | null >( null );
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
			! checkoutActionsAnchor ||
			typeof window.IntersectionObserver !== 'function'
		) {
			return;
		}

		let isTitleVisible = true;
		let areCheckoutActionsNear = false;
		const observer = new window.IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.target === titleElement ) {
						isTitleVisible = entry.isIntersecting;
					} else if ( entry.target === checkoutActionsAnchor ) {
						areCheckoutActionsNear = entry.isIntersecting;
					}
				} );

				if ( ! isTitleVisible && areCheckoutActionsNear ) {
					checkoutActionsTopBeforeMove.current =
						checkoutActionsAnchor.getBoundingClientRect().top;
					closeSummary();
				}
			},
			{ rootMargin: '0px 0px 25% 0px' }
		);

		observer.observe( titleElement );
		observer.observe( checkoutActionsAnchor );

		return () => observer.disconnect();
	}, [
		checkoutActionsAnchor,
		closeSummary,
		hasContainerWidth,
		isLarge,
		isOpen,
		titleElement,
	] );

	// Attach the empty container before mounting portal children. Later moves
	// preserve the same mounted extension component instances.
	useLayoutEffect( () => {
		const destination = showInline ? inlineHost : checkoutActionsHost;

		if ( destination ) {
			if ( contentContainer.parentElement !== destination ) {
				const isMove = contentContainer.parentElement !== null;
				destination.appendChild( contentContainer );
				// Moving the content restarts its CSS animations, such as the items' entrance, so skip them to their
				// end. Looping ones, such as a skeleton's shimmer, carry on.
				if ( isMove && typeof CSSAnimation !== 'undefined' ) {
					contentContainer
						.getAnimations( { subtree: true } )
						.filter(
							( animation ) =>
								animation instanceof CSSAnimation &&
								animation.effect?.getComputedTiming()
									.iterations !== Infinity
						)
						.forEach( ( animation ) => animation.finish() );
				}
			}
			setIsContentReady( true );

			if (
				! showInline &&
				checkoutActionsAnchor &&
				checkoutActionsTopBeforeMove.current !== null
			) {
				const offset =
					checkoutActionsAnchor.getBoundingClientRect().top -
					checkoutActionsTopBeforeMove.current;
				checkoutActionsTopBeforeMove.current = null;
				window.scrollBy( 0, offset );
			}
		}
	}, [
		checkoutActionsAnchor,
		checkoutActionsHost,
		contentContainer,
		inlineHost,
		showInline,
	] );

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
					<RollingPrice
						currency={ totalsCurrency }
						value={ totalPrice }
						className="wc-block-components-checkout-order-summary__title-price"
						animate={ ! cartIsLoading }
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
							'is-entering': isOpen && isFadingIn,
						}
					) }
					id={ ariaControlsId }
				/>
			</div>
			<CheckoutOrderSummaryFill>
				<div
					ref={ setCheckoutActionsAnchor }
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
						ref={ setCheckoutActionsHost }
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
