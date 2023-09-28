// TODO: It might be better to create an API endpoint to get the templates.
export const HOMEPAGE_TEMPLATES = {
	template1: {
		blocks: [
			// Header
			'woocommerce-blocks/header-centered-menu-with-search',

			// Body
			'a8c/cover-image-with-left-aligned-call-to-action',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/featured-products-fresh-and-tasty',
			'woocommerce-blocks/featured-category-triple',
			'a8c/3-column-testimonials',
			'a8c/quotes-2',
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
			'woocommerce-blocks/featured-products-fresh-and-tasty',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/featured-products-fresh-and-tasty',
			'a8c/three-columns-with-images-and-text',
			'woocommerce-blocks/testimonials-3-columns',
			'a8c/subscription',

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
			'woocommerce-blocks/header-centered-menu-with-search',

			// Body
			'a8c/call-to-action-7',
			'a8c/3-column-testimonials',
			'woocommerce-blocks/featured-products-fresh-and-tasty',
			'woocommerce-blocks/featured-category-cover-image',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/social-follow-us-in-social-media',

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
	template4: {
		blocks: [
			// Header
			'woocommerce-blocks/header-essential',

			// Body
			'woocommerce-blocks/featured-products-fresh-and-tasty',
			'woocommerce-blocks/testimonials-single',
			'woocommerce-blocks/hero-product-3-split',
			'a8c/contact-8',

			// Footer
			'woocommerce-blocks/footer-simple-menu-and-cart', // This is supposed to be the "Footer with Newsletter Subscription Form"
		],
		metadata: {
			businessType: [ 'e-commerce', 'small-medium-business' ],
			contentFocus: [ 'products' ],
			audience: [ 'focused' ],
			design: [ 'sleek' ],
			features: [ 'single-testimonial', 'contact', 'call-to-action' ],
			complexity: 'medium',
		},
	},
	template5: {
		blocks: [
			// Header
			'woocommerce-blocks/header-essential',

			// Body
			'a8c/about-me-4',
			'a8c/product-feature-with-buy-button',
			'woocommerce-blocks/featured-products-fresh-and-tasty',
			'a8c/subscription',
			'woocommerce-blocks/testimonials-3-columns',
			'a8c/contact-with-map-on-the-left',

			// Footer
			'woocommerce-blocks/footer-simple-menu-and-cart',
		],
		metadata: {
			businessType: [ 'e-commerce', 'small-medium-business' ],
			contentFocus: [ 'products', 'featured-product' ],
			audience: [ 'focused' ],
			design: [ 'sleek' ],
			features: [ 'testimonial' ],
			complexity: 'medium',
		},
	},
	template6: {
		blocks: [
			// Header
			'woocommerce-blocks/header-minimal',

			// Body
			'a8c/heading-and-video',
			'a8c/3-column-testimonials',
			'woocommerce-blocks/product-hero',
			'a8c/quotes-2',
			'a8c/product-feature-with-buy-button',
			'a8c/simple-two-column-layout',
			'woocommerce-blocks/social-follow-us-in-social-media',

			// Footer
			'woocommerce-blocks/footer-simple-menu-and-cart',
		],
		metadata: {
			businessType: [ 'e-commerce', 'creative', 'small-medium-business' ],
			contentFocus: [ 'services', 'storytelling', 'video' ],
			audience: [ 'focused' ],
			design: [ 'modern' ],
			features: [ 'social-media', 'video' ],
			complexity: 'high',
		},
	},
};
