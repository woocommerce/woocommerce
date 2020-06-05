/**
 * External dependencies
 */
import { withProduct } from '@woocommerce/block-hocs';
import {
	InnerBlockLayoutContextProvider,
	ProductDataContextProvider,
} from '@woocommerce/shared-context';

/**
 * Internal dependencies
 */
import { BLOCK_NAME } from './constants';

/**
 * The Single Product Block.
 */
const Block = ( { isLoading, product, children } ) => {
	const className = 'wc-block-single-product';

	return (
		<InnerBlockLayoutContextProvider
			parentName={ BLOCK_NAME }
			parentClassName={ className }
			isLoading={ isLoading }
		>
			<ProductDataContextProvider product={ product }>
				<div className={ className }>{ children }</div>
			</ProductDataContextProvider>
		</InnerBlockLayoutContextProvider>
	);
};

export default withProduct( Block );
