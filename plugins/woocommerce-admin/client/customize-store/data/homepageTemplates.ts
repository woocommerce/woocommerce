export const HOMEPAGE_TEMPLATES = {
	template1: {
		blocks: [
			// Header
			'woocommerce-blocks/header-centered-menu',

			// Body
			'woocommerce-blocks/hero-product-split',
			'woocommerce-blocks/product-collection-5-columns',
			'woocommerce-blocks/hero-product-3-split',
			'woocommerce-blocks/product-collection-3-columns',
			'woocommerce-blocks/testimonials-3-columns',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/social-follow-us-in-social-media',

			// Footer
			'woocommerce-blocks/footer-with-3-menus',
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
			// Header
			'woocommerce-blocks/header-essential',

			// Body
			'woocommerce-blocks/hero-product-split',
			'woocommerce-blocks/product-collection-4-columns',
			'woocommerce-blocks/hero-product-chessboard',
			'woocommerce-blocks/product-collection-5-columns',
			'woocommerce-blocks/testimonials-3-columns',

			// Footer
			'woocommerce-blocks/footer-large',
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
			// Header
			'woocommerce-blocks/header-centered-menu',

			// Body
			'woocommerce-blocks/hero-product-split',
			'woocommerce-blocks/product-collection-featured-products-5-columns',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/product-query-product-gallery',

			// Footer
			'woocommerce-blocks/footer-with-3-menus',
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
