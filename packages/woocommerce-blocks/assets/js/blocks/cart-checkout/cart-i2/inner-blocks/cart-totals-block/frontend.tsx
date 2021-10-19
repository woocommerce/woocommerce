/**
 * External dependencies
 */
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';

/**
 * Internal dependencies
 */
import './style.scss';

const FrontendBlock = ( {
	children,
}: {
	children: JSX.Element;
} ): JSX.Element => {
	return <Sidebar className="wc-block-cart__sidebar">{ children }</Sidebar>;
};

export default FrontendBlock;
