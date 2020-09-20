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

/** @typedef {import('react')} React */

/**
 * The Single Product Block.
 *
 * @param {Object} props Incoming props for the component.
 * @param {boolean} props.isLoading
 * @param {Object} props.product
 * @param {React.ReactChildren} props.children
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
