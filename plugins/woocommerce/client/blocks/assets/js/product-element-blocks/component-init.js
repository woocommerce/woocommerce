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
			/* webpackChunkName: "product-price" */ './price/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-image',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-image" */ './image/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-title',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-title" */ './title/frontend'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-rating',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-rating" */ './rating/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-rating-stars',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-rating-stars" */ './rating-stars/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-rating-counter',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-rating-counter" */ './rating-counter/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-average-rating',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-average-rating" */ './average-rating/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-button',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-button" */ './button/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-summary',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-summary" */ './summary/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-sale-badge',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-sale-badge" */ './sale-badge/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-sku',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-sku" */ './sku/block'
		)
	),
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-stock-indicator',
	component: lazy( () =>
		import(
			/* webpackChunkName: "product-stock-indicator" */ './stock-indicator/block'
		)
	),
} );
