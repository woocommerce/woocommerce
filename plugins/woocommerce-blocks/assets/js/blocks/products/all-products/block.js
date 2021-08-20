/**
 * External dependencies
 */
import { Component } from 'react';
import PropTypes from 'prop-types';
import { ProductListContainer } from '@woocommerce/base-components/product-list';
import { InnerBlockLayoutContextProvider } from '@woocommerce/shared-context';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { getSetting } from '@woocommerce/settings';

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

		const hideOutOfStockItems = getSetting( 'hideOutOfStockItems', false );

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
				<ProductListContainer
					attributes={ attributes }
					urlParameterSuffix={ urlParameterSuffix }
					hideOutOfStockItems={ hideOutOfStockItems }
				/>
			</InnerBlockLayoutContextProvider>
		);
	}
}

export default Block;
