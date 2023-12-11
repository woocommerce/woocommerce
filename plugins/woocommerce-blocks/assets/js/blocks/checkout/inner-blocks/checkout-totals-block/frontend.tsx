/**
 * External dependencies
 */
import classnames from 'classnames';
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element;
	className?: string;
} ): JSX.Element => {
	return (
		<Sidebar
			className={ classnames( 'wc-block-checkout__sidebar', className ) }
		>
			<StoreNoticesContainer
				context={ 'woocommerce/checkout-totals-block' }
			/>
			{ children }
		</Sidebar>
	);
};

export default FrontendBlock;
