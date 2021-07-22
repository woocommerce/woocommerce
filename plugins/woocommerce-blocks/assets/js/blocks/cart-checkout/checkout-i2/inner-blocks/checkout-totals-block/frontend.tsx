/**
 * External dependencies
 */
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';

const FrontendBlock = ( {
	children,
}: {
	children: JSX.Element;
} ): JSX.Element => {
	return (
		<Sidebar className="wc-block-checkout__sidebar">{ children }</Sidebar>
	);
};

export default FrontendBlock;
