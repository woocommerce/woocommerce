/**
 * External dependencies
 */
import classnames from 'classnames';
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useObservedViewport } from '@woocommerce/base-hooks';
const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element;
	className?: string;
} ): JSX.Element => {
	const [ observedRef, observedElement, viewWindow ] = useObservedViewport();
	const isSticky = observedElement.height < viewWindow.height;
	return (
		<Sidebar
			ref={ observedRef }
			className={ classnames( 'wc-block-checkout__sidebar', className, {
				'is-sticky': isSticky,
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
