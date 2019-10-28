/**
 * External dependencies
 */
import { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ProductListContainer from '@woocommerce/base-components/product-list/container';
import { InnerBlockParentNameProvider } from '@woocommerce/base-context/inner-block-parent-name-context';
import { ProductLayoutContextProvider } from '@woocommerce/base-context/product-layout-context';

const layoutStyleContext = {
	layoutStyleClassPrefix: 'wc-block-grid',
};

/**
 * The All Products Block. @todo
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
		/**
		 * Todo classes
		 *
		 * wp-block-{$this->block_name},
		 * wc-block-{$this->block_name},
		 */
		return (
			<InnerBlockParentNameProvider value="woocommerce/all-products">
				<ProductLayoutContextProvider value={ layoutStyleContext }>
					<ProductListContainer
						attributes={ attributes }
						urlParameterSuffix={ urlParameterSuffix }
					/>
				</ProductLayoutContextProvider>
			</InnerBlockParentNameProvider>
		);
	}
}

export default Block;
