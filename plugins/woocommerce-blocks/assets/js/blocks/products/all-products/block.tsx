/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { ProductListContainer } from '@woocommerce/base-components/product-list';
import { InnerBlockLayoutContextProvider } from '@woocommerce/shared-context';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { Attributes as ProductListAttributes } from 'assets/js/base/components/product-list/types';

interface BlockProps {
	attributes: {
		isPreview?: boolean;
	} & ProductListAttributes;
	urlParameterSuffix?: string;
}

/**
 * The All Products Block.
 */
class Block extends Component< BlockProps > {
	render() {
		const { attributes, urlParameterSuffix } = this.props;

		if ( attributes.isPreview ) {
			return gridBlockPreview;
		}

		/**
		 * Todo classes
		 *
		 * wp-block-{$this->block_name},
		 * wc-block-{$this->block_name},
		 */
		return (
			<InnerBlockLayoutContextProvider
				parentName="woocommerce/all-products"
				parentClassName="wc-block-grid"
			>
				<StoreNoticesContainer context={ 'wc/all-products' } />
				<ProductListContainer
					attributes={ attributes }
					urlParameterSuffix={ urlParameterSuffix }
				/>
			</InnerBlockLayoutContextProvider>
		);
	}
}

export default Block;
