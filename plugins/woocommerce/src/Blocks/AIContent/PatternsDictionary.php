<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\AIContent;

/**
 * Patterns Dictionary class.
 */
class PatternsDictionary {
	/**
	 * Returns the patterns' dictionary.
	 *
	 * @return array[]
	 */
	public static function get() {
		return [
			[
				'name'          => 'Banner',
				'slug'          => 'woocommerce-blocks/banner',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Up to 60% off', 'woocommerce' ),
							'ai_prompt' => __( 'A four words title advertising the sale', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Holiday Sale', 'woocommerce' ),
							'ai_prompt' => __( 'A two words label with the sale name', 'woocommerce' ),
						],
						[
							'default'   => __( 'Get your favorite vinyl at record-breaking prices.', 'woocommerce' ),
							'ai_prompt' => __( 'The main description of the sale with at least 65 characters', 'woocommerce' ),
						],
					],
					'buttons'      => [
						[
							'default'   => __( 'Shop vinyl records', 'woocommerce' ),
							'ai_prompt' => __( 'A 3 words button text to go to the sale page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Discount Banner',
				'slug'    => 'woocommerce-blocks/discount-banner',
				'content' => [
					'descriptions' => [
						[
							'default'   => __( 'Select products', 'woocommerce' ),
							'ai_prompt' => __( 'A two words description of the products on sale', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Discount Banner with Image',
				'slug'          => 'woocommerce-blocks/discount-banner-with-image',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'descriptions' => [
						[
							'default'   => __( 'Select products', 'woocommerce' ),
							'ai_prompt' => __( 'A two words description of the products on sale', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Featured Category Focus',
				'slug'          => 'woocommerce-blocks/featured-category-focus',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles'  => [
						[
							'default'   => __( 'Black and white high-quality prints', 'woocommerce' ),
							'ai_prompt' => __( 'The four words title of the featured category related to the following image description: [image.0]', 'woocommerce' ),
						],
					],
					'buttons' => [
						[
							'default'   => __( 'Shop prints', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the featured category', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Featured Category Triple',
				'slug'          => 'woocommerce-blocks/featured-category-triple',
				'images_total'  => 3,
				'images_format' => 'portrait',
				'content'       => [
					'titles' => [
						[
							'default'   => __( 'Home decor', 'woocommerce' ),
							'ai_prompt' => __( 'A one-word graphic title that encapsulates the essence of the business, inspired by the following image description: [image.0] and the nature of the business. The title should reflect the key elements and characteristics of the business, as portrayed in the image', 'woocommerce' ),
						],
						[
							'default'   => __( 'Retro photography', 'woocommerce' ),
							'ai_prompt' => __( 'A two-words graphic title that encapsulates the essence of the business, inspired by the following image description: [image.1] and the nature of the business. The title should reflect the key elements and characteristics of the business, as portrayed in the image', 'woocommerce' ),
						],
						[
							'default'   => __( 'Handmade gifts', 'woocommerce' ),
							'ai_prompt' => __( 'A two-words graphic title that encapsulates the essence of the business, inspired by the following image description: [image.2] and the nature of the business. The title should reflect the key elements and characteristics of the business, as portrayed in the image', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Featured Products: Fresh & Tasty',
				'slug'          => 'woocommerce-blocks/featured-products-fresh-and-tasty',
				'images_total'  => 4,
				'images_format' => 'portrait',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Fresh & tasty goods', 'woocommerce' ),
							'ai_prompt' => __( 'The title of the featured products with at least 20 characters', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Sweet Organic Lemons', 'woocommerce' ),
							'ai_prompt' => __( 'The three words description of the featured product related to the following image description: [image.0]', 'woocommerce' ),
						],
						[
							'default'   => __( 'Fresh Organic Tomatoes', 'woocommerce' ),
							'ai_prompt' => __( 'The three words description of the featured product related to the following image description: [image.1]', 'woocommerce' ),
						],
						[
							'default'   => __( 'Fresh Lettuce (Washed)', 'woocommerce' ),
							'ai_prompt' => __( 'The three words description of the featured product related to the following image description: [image.2]', 'woocommerce' ),
						],
						[
							'default'   => __( 'Russet Organic Potatoes', 'woocommerce' ),
							'ai_prompt' => __( 'The three words description of the featured product related to the following image description: [image.3]', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Hero Product 3 Split',
				'slug'          => 'woocommerce-blocks/hero-product-3-split',
				'images_total'  => 1,
				'images_format' => 'portrait',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Timeless elegance', 'woocommerce' ),
							'ai_prompt' => __( 'Write a two words title for advertising the store', 'woocommerce' ),
						],
						[
							'default'   => __( 'Durable glass', 'woocommerce' ),
							'ai_prompt' => __( 'Write a two words title for advertising the store', 'woocommerce' ),
						],
						[
							'default'   => __( 'Versatile charm', 'woocommerce' ),
							'ai_prompt' => __( 'Write a two words title for advertising the store', 'woocommerce' ),
						],
						[
							'default'   => __( 'New: Retro Glass Jug', 'woocommerce' ),
							'ai_prompt' => __( 'Write a title with less than 20 characters for advertising the store', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Elevate your table with a 330ml Retro Glass Jug, blending classic design and durable hardened glass.', 'woocommerce' ),
							'ai_prompt' => __( 'Write a text with approximately 130 characters, to describe a product the business is selling', 'woocommerce' ),
						],
						[
							'default'   => __( 'Crafted from resilient thick glass, this jug ensures lasting quality, making it perfect for everyday use with a touch of vintage charm.', 'woocommerce' ),
							'ai_prompt' => __( 'Write a text with approximately 130 characters, to describe a product the business is selling', 'woocommerce' ),
						],
						[
							'default'   => __( "The Retro Glass Jug's classic silhouette effortlessly complements any setting, making it the ideal choice for serving beverages with style and flair.", 'woocommerce' ),
							'ai_prompt' => __( 'Write a long text, with at least 130 characters, to describe a product the business is selling', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Hero Product Chessboard',
				'slug'          => 'woocommerce-blocks/hero-product-chessboard',
				'images_total'  => 2,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Quality Materials', 'woocommerce' ),
							'ai_prompt' => __( 'A two words title describing the first displayed product feature', 'woocommerce' ),
						],
						[
							'default'   => __( 'Unique design', 'woocommerce' ),
							'ai_prompt' => __( 'A two words title describing the second displayed product feature', 'woocommerce' ),
						],
						[
							'default'   => __( 'Make your house feel like home', 'woocommerce' ),
							'ai_prompt' => __( 'A two words title describing the fourth displayed product feature', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'We use only the highest-quality materials in our products, ensuring that they look great and last for years to come.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the product feature with at least 115 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'From bold prints to intricate details, our products are a perfect combination of style and function.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the product feature with at least 115 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'Add a touch of charm and coziness this holiday season with a wide selection of hand-picked decorations — from minimalist vases to designer furniture.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the product feature with at least 115 characters', 'woocommerce' ),
						],
					],
					'buttons'      => [
						[
							'default'   => __( 'Shop home decor', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the product page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Hero Product Split',
				'slug'          => 'woocommerce-blocks/hero-product-split',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles' => [
						[
							'default'   => __( 'Keep dry with 50% off rain jackets', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the product the store is selling with at least 35 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Just Arrived Full Hero',
				'slug'          => 'woocommerce-blocks/just-arrived-full-hero',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Sound like no other', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the displayed product collection with at least 10 characters', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Experience your music like never before with our latest generation of hi-fidelity headphones.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the product collection with at least 35 characters', 'woocommerce' ),
						],
					],
					'buttons'      => [
						[
							'default'   => __( 'Shop now', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the product collection page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Product Collection Banner',
				'slug'          => 'woocommerce-blocks/product-collection-banner',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Brand New for the Holidays', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the displayed product collection with at least 25 characters related to the following image description: [image.0]', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Check out our brand new collection of holiday products and find the right gift for anyone.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the product collection with at least 90 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Product Collections Featured Collection',
				'slug'    => 'woocommerce-blocks/product-collections-featured-collection',
				'content' => [
					'titles' => [
						[
							'default'   => "This week's popular products",
							'ai_prompt' => __( 'An impact phrase that advertises the displayed product collection with at least 30 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Product Collections Featured Collections',
				'slug'          => 'woocommerce-blocks/product-collections-featured-collections',
				'images_total'  => 4,
				'images_format' => 'landscape',
				'content'       => [
					'titles'  => [
						[
							'default'   => __( 'Tech gifts under $100', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the product collection with at least 20 characters related to the following image descriptions: [image.0], [image.1]', 'woocommerce' ),
						],
						[
							'default'   => __( 'For the gamers', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the product collection with at least 15 characters related to the following image descriptions: [image.2], [image.3]', 'woocommerce' ),
						],
					],
					'buttons' => [
						[
							'default'   => __( 'Shop tech', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the product collection page', 'woocommerce' ),
						],
						[
							'default'   => __( 'Shop games', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the product collection page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Product Collections Newest Arrivals',
				'slug'    => 'woocommerce-blocks/product-collections-newest-arrivals',
				'content' => [
					'titles'  => [
						[
							'default'   => __( 'Our newest arrivals', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the displayed product collection with at least 20 characters', 'woocommerce' ),
						],
					],
					'buttons' => [
						[
							'default'   => __( 'More new products', 'woocommerce' ),
							'ai_prompt' => __( 'The button text to go to the product collection page with at least 15 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Product Collection 4 Columns',
				'slug'    => 'woocommerce-blocks/product-collection-4-columns',
				'content' => [
					'titles' => [
						[
							'default'   => __( 'Staff picks', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the displayed product collection with at least 20 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Product Collection 5 Columns',
				'slug'    => 'woocommerce-blocks/product-collection-5-columns',
				'content' => [
					'titles' => [
						[
							'default'   => __( 'Our latest and greatest', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase with that advertises the product collection with at least 20 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Product Gallery',
				'slug'    => 'woocommerce-blocks/product-query-product-gallery',
				'content' => [
					'titles' => [
						[
							'default'   => __( 'Bestsellers', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the featured products with at least 10 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Featured Products 2 Columns',
				'slug'    => 'woocommerce-blocks/featured-products-2-cols',
				'content' => [
					'titles'       => [
						[
							'default'   => __( 'Fan favorites', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the featured products with at least 10 characters', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Get ready to start the season right. All the fan favorites in one place at the best price.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the featured products with at least 90 characters', 'woocommerce' ),
						],
					],
					'buttons'      => [
						[
							'default'   => __( 'Shop All', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the featured products page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Product Hero 2 Column 2 Row',
				'slug'          => 'woocommerce-blocks/product-hero-2-col-2-row',
				'images_total'  => 2,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'The Eden Jacket', 'woocommerce' ),
							'ai_prompt' => __( 'A three words title that advertises a product related to the following image description: [image.0]', 'woocommerce' ),
						],
						[
							'default'   => __( '100% Woolen', 'woocommerce' ),
							'ai_prompt' => __( 'A two words title that advertises a product feature', 'woocommerce' ),
						],
						[
							'default'   => __( 'Fits your wardrobe', 'woocommerce' ),
							'ai_prompt' => __( 'A three words title that advertises a product feature', 'woocommerce' ),
						],
						[
							'default'   => __( 'Versatile', 'woocommerce' ),
							'ai_prompt' => __( 'An one word title that advertises a product feature', 'woocommerce' ),
						],
						[
							'default'   => __( 'Normal Fit', 'woocommerce' ),
							'ai_prompt' => __( 'A two words title that advertises a product feature', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Perfect for any look featuring a mid-rise, relax fitting silhouette.', 'woocommerce' ),
							'ai_prompt' => __( 'The description of a product with at least 65 characters related to the following image: [image.0]', 'woocommerce' ),
						],
						[
							'default'   => __( 'Reflect your fashionable style.', 'woocommerce' ),
							'ai_prompt' => __( 'The description of a product feature with at least 30 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'Half tuck into your pants or layer over.', 'woocommerce' ),
							'ai_prompt' => __( 'The description of a product feature with at least 30 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'Button-down front for any type of mood or look.', 'woocommerce' ),
							'ai_prompt' => __( 'The description of a product feature with at least 30 characters', 'woocommerce' ),
						],
						[
							'default'   => __( '42% Cupro 34% Linen 24% Viscose', 'woocommerce' ),
							'ai_prompt' => __( 'The description of a product feature with at least 30 characters', 'woocommerce' ),
						],
					],
					'buttons'      => [
						[
							'default'   => __( 'View product', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the product page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Shop by Price',
				'slug'    => 'woocommerce-blocks/shop-by-price',
				'content' => [
					'titles' => [
						[
							'default'   => __( 'Outdoor Furniture & Accessories', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the first product collection with at least 30 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'Summer Dinning', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the second product collection with at least 20 characters', 'woocommerce' ),
						],
						[
							'default'   => "Women's Styles",
							'ai_prompt' => __( 'An impact phrase that advertises the third product collection with at least 20 characters', 'woocommerce' ),
						],
						[
							'default'   => "Kids' Styles",
							'ai_prompt' => __( 'An impact phrase that advertises the fourth product collection with at least 20 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Small Discount Banner with Image',
				'slug'          => 'woocommerce-blocks/small-discount-banner-with-image',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles' => [
						[
							'default'   => __( 'Chairs', 'woocommerce' ),
							'ai_prompt' => __( 'A single word that advertises the product and is related to the following image description: [image.0]', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Social: Follow us on social media',
				'slug'          => 'woocommerce-blocks/social-follow-us-in-social-media',
				'images_total'  => 4,
				'images_format' => 'landscape',
				'content'       => [
					'titles' => [
						[
							'default'   => __( 'Stay in the loop', 'woocommerce' ),
							'ai_prompt' => __( 'A phrase that advertises the social media accounts of the store with at least 25 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Alternating Image and Text',
				'slug'          => 'woocommerce-blocks/alt-image-and-text',
				'images_total'  => 2,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Our products', 'woocommerce' ),
							'ai_prompt' => __( 'A two words impact phrase that advertises the products', 'woocommerce' ),
						],
						[
							'default'   => __( 'Sustainable blends, stylish accessories', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the products with at least 40 characters and related to the following image description: [image.0]', 'woocommerce' ),
						],
						[
							'default'   => __( 'About us', 'woocommerce' ),
							'ai_prompt' => __( 'A two words impact phrase that advertises the brand', 'woocommerce' ),
						],
						[
							'default'   => __( 'Committed to a greener lifestyle', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the brand with at least 50 characters related to the following image description: [image.1]', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Indulge in the finest organic coffee beans, teas, and hand-picked accessories, all locally sourced and sustainable for a mindful lifestyle.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the products with at least 180 characters', 'woocommerce' ),
						],
						[
							'default'   => "Our passion is crafting mindful moments with locally sourced, organic, and sustainable products. We're more than a store; we're your path to a community-driven, eco-friendly lifestyle that embraces premium quality.",
							'ai_prompt' => __( 'A description of the products with at least 180 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'Locally sourced ingredients', 'woocommerce' ),
							'ai_prompt' => __( 'A three word description of the products', 'woocommerce' ),
						],
						[
							'default'   => __( 'Premium organic blends', 'woocommerce' ),
							'ai_prompt' => __( 'A three word description of the products', 'woocommerce' ),
						],
						[
							'default'   => __( 'Hand-picked accessories', 'woocommerce' ),
							'ai_prompt' => __( 'A three word description of the products', 'woocommerce' ),
						],
						[
							'default'   => __( 'Sustainable business practices', 'woocommerce' ),
							'ai_prompt' => __( 'A three word description of the products', 'woocommerce' ),
						],
					],
					'buttons'      => [
						[
							'default'   => __( 'Meet us', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the product page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Testimonials 3 Columns',
				'slug'    => 'woocommerce-blocks/testimonials-3-columns',
				'content' => [
					'titles'       => [
						[
							'default'   => __( 'Eclectic finds, ethical delights', 'woocommerce' ),
							'ai_prompt' => __( 'Write a short title advertising a testimonial from a customer', 'woocommerce' ),
						],
						[
							'default'   => __( 'Sip, Shop, Savor', 'woocommerce' ),
							'ai_prompt' => __( 'Write a short title advertising a testimonial from a customer', 'woocommerce' ),
						],
						[
							'default'   => __( 'LOCAL LOVE', 'woocommerce' ),
							'ai_prompt' => __( 'Write a short title advertising a testimonial from a customer', 'woocommerce' ),
						],
						[
							'default'   => __( 'What our customers say', 'woocommerce' ),
							'ai_prompt' => __( 'Write just 4 words to advertise testimonials from customers', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Transformed my daily routine with unique, eco-friendly treasures. Exceptional quality and service. Proud to support a store that aligns with my values.', 'woocommerce' ),
							'ai_prompt' => __( 'Write the testimonial from a customer with approximately 150 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'The organic coffee beans are a revelation. Each sip feels like a journey. Beautifully crafted accessories add a touch of elegance to my home.', 'woocommerce' ),
							'ai_prompt' => __( 'Write the testimonial from a customer with approximately 150 characters', 'woocommerce' ),
						],
						[
							'default'   => __( 'From sustainably sourced teas to chic vases, this store is a treasure trove. Love knowing my purchases contribute to a greener planet.', 'woocommerce' ),
							'ai_prompt' => __( 'Write the testimonial from a customer with approximately 150 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Testimonials Single',
				'slug'          => 'woocommerce-blocks/testimonials-single',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'A ‘brewtiful’ experience :-)', 'woocommerce' ),
							'ai_prompt' => __( 'A two words title that advertises the testimonial', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'Exceptional flavors, sustainable choices. The carefully curated collection of coffee pots and accessories turned my kitchen into a haven of style and taste.', 'woocommerce' ),
							'ai_prompt' => __( 'A description of the testimonial with at least 225 characters', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'          => 'Featured Category Cover Image',
				'slug'          => 'woocommerce-blocks/featured-category-cover-image',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'content'       => [
					'titles'       => [
						[
							'default'   => __( 'Sit back and relax', 'woocommerce' ),
							'ai_prompt' => __( 'A description for a product with at least 20 characters', 'woocommerce' ),
						],
					],
					'descriptions' => [
						[
							'default'   => __( 'With a wide range of designer chairs to elevate your living space.', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the products with at least 55 characters', 'woocommerce' ),
						],
					],
					'buttons'      => [
						[
							'default'   => __( 'Shop chairs', 'woocommerce' ),
							'ai_prompt' => __( 'A two words button text to go to the shop page', 'woocommerce' ),
						],
					],
				],
			],
			[
				'name'    => 'Product Collection: Featured Products 5 Columns',
				'slug'    => 'woocommerce-blocks/product-collection-featured-products-5-columns',
				'content' => [
					'titles' => [
						[
							'default'   => __( 'Shop new arrivals', 'woocommerce' ),
							'ai_prompt' => __( 'An impact phrase that advertises the newest additions to the store with at least 20 characters', 'woocommerce' ),
						],
					],
				],
			],
		];
	}
}
