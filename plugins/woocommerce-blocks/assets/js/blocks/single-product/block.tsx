/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { withProduct } from '@woocommerce/block-hocs';
import {
	InnerBlockLayoutContextProvider,
	ProductDataContextProvider,
} from '@woocommerce/shared-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-checkout';
import { useStoreEvents } from '@woocommerce/base-context/hooks';
import { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { BLOCK_NAME } from './constants';

interface BlockProps {
	isLoading: boolean;
	product: ProductResponseItem;
	children: JSX.Element | JSX.Element[];
}

const Block = ( { isLoading, product, children }: BlockProps ) => {
	const { dispatchStoreEvent } = useStoreEvents();
	const className = 'wc-block-single-product wc-block-layout';
	const noticeContext = `woocommerce/single-product/${ product?.id || 0 }`;

	useEffect( () => {
		dispatchStoreEvent( 'product-render', {
			product,
			listName: BLOCK_NAME,
		} );
	}, [ product, dispatchStoreEvent ] );

	return (
		<InnerBlockLayoutContextProvider
			parentName={ BLOCK_NAME }
			parentClassName={ className }
		>
			<ProductDataContextProvider
				product={ product }
				isLoading={ isLoading }
			>
				<StoreNoticesContainer context={ noticeContext } />
				<div className={ className }>{ children }</div>
			</ProductDataContextProvider>
		</InnerBlockLayoutContextProvider>
	);
};

export default withProduct( Block );
