/**
 * External dependencies
 */
import { getRegisteredInnerBlocks } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import ProductButton from '../blocks/product/button/block';
import ProductImage from '../blocks/product/image/frontend';
import ProductPrice from '../blocks/product/price/block';
import ProductRating from '../blocks/product/rating/block';
import ProductSaleBadge from '../blocks/product/sale-badge/block';
import ProductSummary from '../blocks/product/summary/block';
import ProductTitle from '../blocks/product/title/frontend';
import ProductSku from '../blocks/product/sku/block';
import ProductCategoryList from '../blocks/product/category-list/block';
import ProductTagList from '../blocks/product/tag-list/block';
import ProductStockIndicator from '../blocks/product/stock-indicator/block';
import ProductAddToCart from '../blocks/product/add-to-cart/frontend';

/**
 * Map blocks to components suitable for use on the frontend.
 *
 * @param {string} blockName Name of the parent block. Used to get extension children.
 */
export const getBlockMap = ( blockName ) => ( {
	'woocommerce/product-price': ProductPrice,
	'woocommerce/product-image': ProductImage,
	'woocommerce/product-title': ProductTitle,
	'woocommerce/product-rating': ProductRating,
	'woocommerce/product-button': ProductButton,
	'woocommerce/product-summary': ProductSummary,
	'woocommerce/product-sale-badge': ProductSaleBadge,
	'woocommerce/product-sku': ProductSku,
	'woocommerce/product-category-list': ProductCategoryList,
	'woocommerce/product-tag-list': ProductTagList,
	'woocommerce/product-stock-indicator': ProductStockIndicator,
	'woocommerce/product-add-to-cart': ProductAddToCart,
	...getRegisteredInnerBlocks( blockName ),
} );
