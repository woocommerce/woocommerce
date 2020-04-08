/**
 * External dependencies
 */
import { Component } from 'react';
import PropTypes from 'prop-types';
import ProductListContainer from '@woocommerce/base-components/product-list/container';
import {
	InnerBlockConfigurationProvider,
	ProductLayoutContextProvider,
} from '@woocommerce/base-context';
import { gridBlockPreview } from '@woocommerce/resource-previews';

const layoutContextConfig = {
	layoutStyleClassPrefix: 'wc-block-grid',
};

const parentBlockConfig = { parentName: 'woocommerce/all-products' };

/**
 * The All Products Block.
 */
class Block extends Component {
	static propTypes = {
		/**
		 * The attributes for this block.
		 */
		attributes: PropTypes.object.isRequired,
	};

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
			<InnerBlockConfigurationProvider value={ parentBlockConfig }>
				<ProductLayoutContextProvider value={ layoutContextConfig }>
					<ProductListContainer
						attributes={ attributes }
						urlParameterSuffix={ urlParameterSuffix }
					/>
				</ProductLayoutContextProvider>
			</InnerBlockConfigurationProvider>
		);
	}
}

export default Block;
