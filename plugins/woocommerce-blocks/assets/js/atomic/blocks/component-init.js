/**
 * External dependencies
 */
import { registerBlockComponent } from '@woocommerce/blocks-registry';
import { lazy } from '@wordpress/element';
import { WC_BLOCKS_BUILD_URL } from '@woocommerce/block-settings';

// Modify webpack publicPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = WC_BLOCKS_BUILD_URL;

registerBlockComponent( {
	blockName: 'woocommerce/product-price',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/price" */ './product/price/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-image',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/image" */ './product/image/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-title',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/title" */ './product/title/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-rating',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/rating" */ './product/rating/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-button',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/button" */ './product/button/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-summary',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/summary" */ './product/summary/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-sale-badge',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/sale-badge" */ './product/sale-badge/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-sku',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/sku" */ './product/sku/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-category-list',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/category-list" */ './product/category-list/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-tag-list',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/tag-list" */ './product/tag-list/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-stock-indicator',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/stock-indicator" */ './product/stock-indicator/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-add-to-cart',
	component: lazy( () =>
		import(
			/* webpackChunkName: "atomic-block-components/add-to-cart" */ './product/add-to-cart/frontend'
		)
	),
} );
