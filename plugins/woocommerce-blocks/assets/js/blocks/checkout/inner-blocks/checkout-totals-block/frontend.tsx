/**
 * External dependencies
 */
import classnames from 'classnames';
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';
import {
	StoreNoticesContainer,
	Panel,
	FormattedMonetaryAmount,
} from '@woocommerce/blocks-components';
import { useContainerWidthContext } from '@woocommerce/base-context';
import {
	useObservedViewport,
	useStickyState,
	useScrollDirection,
} from '@woocommerce/base-hooks';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useMergeRefs } from '@wordpress/compose';

const SummaryHeader = () => {
	const { cartTotals } = useStoreCart();
	const { total_price: totalPrice } = cartTotals;
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );
	return (
		<div className="wc-block-components-order-summary__header">
			<span className="wc-block-components-order-summary__button-text">
				{ __( 'Order summary', 'woocommerce' ) }
			</span>
			<FormattedMonetaryAmount
				className="wc-block-components-order-summary__value"
				currency={ totalsCurrency }
				value={ totalPrice }
			/>
		</div>
	);
};

const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element;
	className?: string;
} ): JSX.Element => {
	const [ observedRef, observedElement, viewWindow ] =
		useObservedViewport< HTMLDivElement >();
	const [ stickyRef, isSticky ] = useStickyState< HTMLDivElement >();
	const [ scrollDirection ] = useScrollDirection();
	const ref = useMergeRefs( [ stickyRef, observedRef ] );
	const canBeSticky = observedElement.height < viewWindow.height;
	const { isSmall, isMobile } = useContainerWidthContext();
	// Get the body background color to use as the sticky container background color.
	const backgroundColor = useMemo(
		() => getComputedStyle( document.body ).backgroundColor,
		[]
	);
	if ( isSmall || isMobile ) {
		return (
			<>
				<StoreNoticesContainer
					context={ 'woocommerce/checkout-totals-block' }
				/>
				<Panel
					className={ classnames(
						'wc-block-components-order-summary',
						'wc-block-components-order-summary__sticky-container',
						{
							'can-sticky': true,
							'is-scrolling-down':
								scrollDirection === 'down' && isSticky,
						}
					) }
					ref={ ref }
					initialOpen={ false }
					hasBorder={ true }
					title={ <SummaryHeader /> }
					style={ {
						backgroundColor,
						marginLeft: -35,
						marginRight: -35,
						top: -1,
					} }
				>
					<Sidebar
						className={ classnames(
							'wc-block-checkout__sidebar',
							className
						) }
					>
						{ children }
					</Sidebar>
				</Panel>
			</>
		);
	}

	return (
		<Sidebar
			ref={ observedRef }
			className={ classnames( 'wc-block-checkout__sidebar', className, {
				'can-sticky': canBeSticky,
			} ) }
		>
			<StoreNoticesContainer
				context={ 'woocommerce/checkout-totals-block' }
			/>
			{ children }
		</Sidebar>
	);
};

export default FrontendBlock;
