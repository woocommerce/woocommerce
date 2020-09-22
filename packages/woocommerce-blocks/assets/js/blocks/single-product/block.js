/**
 * External dependencies
 */
import { withProduct } from '@woocommerce/block-hocs';
import {
	InnerBlockLayoutContextProvider,
	ProductDataContextProvider,
} from '@woocommerce/shared-context';
import { StoreNoticesProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { BLOCK_NAME } from './constants';

/**
 * The Single Product Block.
 */
const Block = ( { isLoading, product, children } ) => {
	const className = 'wc-block-single-product wc-block-layout';
	const noticeContext = `woocommerce/single-product/${ product?.id || 0 }`;

	return (
		<InnerBlockLayoutContextProvider
			parentName={ BLOCK_NAME }
			parentClassName={ className }
		>
			<ProductDataContextProvider
				product={ product }
				isLoading={ isLoading }
			>
				<StoreNoticesProvider context={ noticeContext }>
					<div className={ className }>{ children }</div>
				</StoreNoticesProvider>
			</ProductDataContextProvider>
		</InnerBlockLayoutContextProvider>
	);
};

export default withProduct( Block );
