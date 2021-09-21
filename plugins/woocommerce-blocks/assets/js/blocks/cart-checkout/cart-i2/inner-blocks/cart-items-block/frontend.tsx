/**
 * External dependencies
 */
import { Main } from '@woocommerce/base-components/sidebar-layout';

const FrontendBlock = ( {
	children,
}: {
	children: JSX.Element;
} ): JSX.Element => {
	return <Main className="wc-block-cart__main">{ children }</Main>;
};

export default FrontendBlock;
