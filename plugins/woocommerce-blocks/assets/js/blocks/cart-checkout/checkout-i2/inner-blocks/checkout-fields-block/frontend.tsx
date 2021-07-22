/**
 * External dependencies
 */
import { Main } from '@woocommerce/base-components/sidebar-layout';

const FrontendBlock = ( {
	children,
}: {
	children: JSX.Element;
} ): JSX.Element => {
	return (
		<Main className="wc-block-checkout__main">
			<form className="wc-block-components-form wc-block-checkout__form">
				{ children }
			</form>
		</Main>
	);
};

export default FrontendBlock;
