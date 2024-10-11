/**
 * Internal dependencies
 */
import { isWooExpress } from '~/utils/is-woo-express';
import { THEME_SLUG } from './constants';

const introPatternWooExpress = 'woocommerce-blocks/hero-product-split';
export const headerTemplateId = `${ THEME_SLUG }//header`;
export const footerTemplateId = `${ THEME_SLUG }//footer`;

export const HEADER_TEMPLATES = {
	template1: {
		blocks: [ 'woocommerce-blocks/header-centered-menu' ],
	},
	template2: {
		blocks: [ 'woocommerce-blocks/header-essential' ],
	},
	template3: {
		blocks: [ 'woocommerce-blocks/header-centered-menu' ],
	},
};

export const FOOTER_TEMPLATES = {
	template1: {
		blocks: [ 'woocommerce-blocks/footer-with-3-menus' ],
	},
	template2: {
		blocks: [ 'woocommerce-blocks/footer-large' ],
	},
	template3: {
		blocks: [ 'woocommerce-blocks/footer-with-3-menus' ],
	},
};

export const HOMEPAGE_TEMPLATES = {
	template1: {
		blocks: [
			// Body
			isWooExpress()
				? introPatternWooExpress
				: 'woocommerce-blocks/just-arrived-full-hero',
			'woocommerce-blocks/product-collection-5-columns',
			'woocommerce-blocks/hero-product-3-split',
			'woocommerce-blocks/product-collection-3-columns',
			'woocommerce-blocks/testimonials-3-columns',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/social-follow-us-in-social-media',
		],
		metadata: {
			businessType: [ 'e-commerce', 'large-business' ],
			contentFocus: [ 'featured products' ],
			audience: [ 'general' ],
			design: [ 'contemporary' ],
			features: [
				'fullwidth-image-banner',
				'testimonials',
				'social-media',
				'search',
			],
			complexity: 'high',
		},
	},
	template2: {
		blocks: [
			// Body
			isWooExpress()
				? introPatternWooExpress
				: 'woocommerce-blocks/featured-category-cover-image',
			'woocommerce-blocks/product-collection-4-columns',
			'woocommerce-blocks/hero-product-chessboard',
			'woocommerce-blocks/product-collection-5-columns',
			'woocommerce-blocks/testimonials-3-columns',
		],
		metadata: {
			businessType: [ 'e-commerce', 'subscription', 'large-business' ],
			contentFocus: [ 'catalog' ],
			audience: [ 'general' ],
			design: [ 'contemporary' ],
			features: [ 'small-banner', 'testimonials', 'newsletter' ],
			complexity: 'high',
		},
	},
	template3: {
		blocks: [
			// Body
			'woocommerce-blocks/hero-product-split',
			'woocommerce-blocks/product-collection-featured-products-5-columns',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/product-query-product-gallery',
		],
		metadata: {
			businessType: [ 'subscription', 'large-business' ],
			contentFocus: [ 'catalog', 'call-to-action' ],
			audience: [ 'general' ],
			design: [ 'contemporary' ],
			features: [ 'small-banner', 'social-media' ],
			complexity: 'high',
		},
	},
};
