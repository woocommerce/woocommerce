/**
 * External dependencies
 */
import clsx from 'clsx';
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useObservedViewport } from '@woocommerce/base-hooks';
import { useContainerWidthContext } from '@woocommerce/base-context';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element;
	className?: string;
} ): JSX.Element => {
	const [ observedRef, observedElement, viewWindow ] =
		useObservedViewport< HTMLDivElement >();
	const isSticky = observedElement.height < viewWindow.height;
	const { isLarge } = useContainerWidthContext();

	return (
		<Sidebar
			ref={ observedRef }
			className={ clsx( 'wc-block-checkout__sidebar', className, {
				'is-sticky': isSticky,
				'is-large': isLarge,
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
