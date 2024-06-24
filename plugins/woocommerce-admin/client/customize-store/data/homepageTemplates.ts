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
};

export const FOOTER_TEMPLATES = {
	template1: {
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
};
