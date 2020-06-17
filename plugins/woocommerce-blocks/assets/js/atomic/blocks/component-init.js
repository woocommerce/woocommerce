/**
 * External dependencies
 */
import { registerBlockComponent } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import ProductButton from './product/button/block';
import ProductImage from './product/image/frontend';
import ProductPrice from './product/price/block';
import ProductRating from './product/rating/block';
import ProductSaleBadge from './product/sale-badge/block';
import ProductSummary from './product/summary/block';
import ProductTitle from './product/title/frontend';
import ProductSku from './product/sku/block';
import ProductCategoryList from './product/category-list/block';
import ProductTagList from './product/tag-list/block';
import ProductStockIndicator from './product/stock-indicator/block';
import ProductAddToCart from './product/add-to-cart/frontend';

registerBlockComponent( {
	blockName: 'woocommerce/product-price',
	component: ProductPrice,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-image',
	component: ProductImage,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-title',
	component: ProductTitle,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-rating',
	component: ProductRating,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-button',
	component: ProductButton,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-summary',
	component: ProductSummary,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-sale-badge',
	component: ProductSaleBadge,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-sku',
	component: ProductSku,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-category-list',
	component: ProductCategoryList,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-tag-list',
	component: ProductTagList,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-stock-indicator',
	component: ProductStockIndicator,
} );

registerBlockComponent( {
	blockName: 'woocommerce/product-add-to-cart',
	component: ProductAddToCart,
} );
