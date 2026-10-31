<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StructuredData;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\StructuredData\OpenGraph;
use WC_Helper_Product;
use WC_Product;
use WC_Unit_Test_Case;
use WP_HTML_Tag_Processor;

/**
 * Tests for product Open Graph metadata.
 */
class OpenGraphTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OpenGraph
	 */
	private $sut;

	/**
	 * Product whose page is being rendered.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Request URI before navigating to the product.
	 *
	 * @var string|null
	 */
	private $request_uri;

	/**
	 * Set up a published product with a featured image.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut         = new OpenGraph();
		$this->request_uri = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Preserve request state changed by go_to().

		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'blogname', 'Test store' );
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_price_num_decimals', 2 );

		$image_id = self::factory()->attachment->create(
			array(
				'file'           => 'product.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$this->assertIsInt( $image_id, 'The featured image fixture should be created successfully.' );
		wp_update_attachment_metadata(
			$image_id,
			array(
				'file'   => 'product.jpg',
				'width'  => 1200,
				'height' => 800,
			)
		);
		update_post_meta( $image_id, '_wp_attachment_image_alt', 'Product photo' );

		$this->product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'              => 'Coffee & "cream"',
				'status'            => ProductStatus::PUBLISH,
				'short_description' => '<p>A <strong>smooth</strong> blend.</p>',
				'image_id'          => $image_id,
			)
		);
		$this->go_to( $this->product->get_permalink() );
	}

	/**
	 * Restore request state that WordPress does not reset.
	 */
	public function tearDown(): void {
		try {
			if ( null === $this->request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $this->request_uri;
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should add missing product metadata and fill empty tags without duplicates.
	 */
	public function test_adds_missing_and_fills_empty_tags(): void {
		$tags = $this->render_meta_tags( '<meta property="og:description" content=" ">' );

		$this->assertSame( array( 'Coffee & "cream"' ), $tags['og:title'], 'The product title should survive HTML escaping.' );
		$this->assertSame( array( 'product' ), $tags['og:type'], 'Missing Open Graph type should be added.' );
		$this->assertSame( array( $this->product->get_permalink() ), $tags['og:url'], 'The product permalink should be used.' );
		$this->assertSame( array( 'A smooth blend.' ), $tags['og:description'], 'The empty description should be filled once with plain text.' );
		$this->assertSame( array( 'Test store' ), $tags['og:site_name'], 'The store name should be added.' );
		$this->assertSame( array( wp_get_attachment_url( (int) $this->product->get_image_id() ) ), $tags['og:image'], 'The featured image should be added.' );
		$this->assertSame( array( '1200' ), $tags['og:image:width'], 'Image width should describe the featured image.' );
		$this->assertSame( array( '800' ), $tags['og:image:height'], 'Image height should describe the featured image.' );
		$this->assertSame( array( 'Product photo' ), $tags['og:image:alt'], 'Image alternative text should be added.' );
		$this->assertSame( array( 'image/jpeg' ), $tags['og:image:type'], 'Image MIME type should be added.' );
		$this->assertSame( array( 'summary_large_image' ), $tags['twitter:card'], 'Products with an image should use a large image card.' );
		$this->assertSame( array( '10.00' ), $tags['product:price:amount'], 'The product price should be added.' );
		$this->assertSame( array( 'USD' ), $tags['product:price:currency'], 'The store currency should be added.' );
	}

	/**
	 * @testdox Should collect up to six distinct valid product image URLs.
	 */
	public function test_collects_six_distinct_valid_images(): void {
		$invalid_id = self::factory()->attachment->create(
			array(
				'file'           => 'document.pdf',
				'post_mime_type' => 'application/pdf',
			)
		);
		$image_ids  = array();
		for ( $i = 1; $i <= 7; $i++ ) {
			$image_ids[] = self::factory()->attachment->create(
				array(
					'file'           => "gallery-$i.jpg",
					'post_mime_type' => 'image/jpeg',
				)
			);
		}
		$duplicate_id = self::factory()->attachment->create(
			array(
				'file'           => 'gallery-1.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$this->product->set_image_id( $invalid_id );
		$this->product->set_gallery_image_ids( array_merge( array( $image_ids[0], $duplicate_id ), array_slice( $image_ids, 1 ) ) );
		$this->product->save();

		$tags = $this->render_meta_tags( '' );

		$this->assertSame(
			array_map( 'wp_get_attachment_url', array_slice( $image_ids, 0, 6 ) ),
			$tags['og:image'],
			'Invalid and duplicate images should not consume the six-image limit.'
		);
	}

	/**
	 * @testdox Should render sale prices and sale dates in product metadata.
	 */
	public function test_sale_price_and_dates(): void {
		$start = time() - DAY_IN_SECONDS;
		$end   = time() + DAY_IN_SECONDS;
		$this->product->set_regular_price( '10' );
		$this->product->set_sale_price( '8' );
		$this->product->set_date_on_sale_from( $start );
		$this->product->set_date_on_sale_to( $end );
		$this->product->save();

		$tags = $this->render_meta_tags( '' );
		$this->assertSame( array( '8.00' ), $tags['product:sale_price:amount'] );
		$this->assertSame( array( 'USD' ), $tags['product:sale_price:currency'] );
		$this->assertSame( array( '8.00' ), $tags['og:price:amount'] );
		$this->assertSame( array( '10.00' ), $tags['og:price:standard_amount'] );
		$this->assertSame( array( $this->product->get_date_on_sale_from()->format( DATE_ATOM ) ), $tags['product:sale_price_dates:start'] );
		$this->assertSame( array( $this->product->get_date_on_sale_to()->format( DATE_ATOM ) ), $tags['product:sale_price_dates:end'] );
		$this->assertSame( $tags['product:sale_price_dates:start'], $tags['og:price:start_date'] );
		$this->assertSame( $tags['product:sale_price_dates:end'], $tags['og:price:end_date'] );
	}

	/**
	 * @testdox Should render the corresponding availability for each stock status.
	 * @testWith ["instock", "in stock", "instock"]
	 *           ["outofstock", "out of stock", "out of stock"]
	 *           ["onbackorder", "available for order", "backorder"]
	 *
	 * @param string $status Product stock status.
	 * @param string $product_availability Product availability value.
	 * @param string $og_availability Open Graph availability value.
	 */
	public function test_stock_availability( string $status, string $product_availability, string $og_availability ): void {
		$this->product->set_stock_status( $status );
		$this->product->save();
		$tags = $this->render_meta_tags( '' );

		$this->assertSame( array( $product_availability ), $tags['product:availability'] );
		$this->assertSame( array( $og_availability ), $tags['og:availability'] );
	}

	/**
	 * @testdox Should omit prices for variable and grouped products.
	 * @testWith ["create_variation_product"]
	 *           ["create_grouped_product"]
	 *
	 * @param string $factory Product fixture method.
	 */
	public function test_omits_price_for_products_without_a_single_price( string $factory ): void {
		$product = WC_Helper_Product::$factory();
		$this->go_to( $product->get_permalink() );
		$tags = $this->render_meta_tags( '' );

		$this->assertSame( array( $product->get_name() ), $tags['og:title'] );
		$this->assertArrayNotHasKey( 'product:price:amount', $tags );
		$this->assertArrayNotHasKey( 'og:price:amount', $tags );
	}

	/**
	 * @testdox Should replace an existing Open Graph type with product without adding a duplicate.
	 */
	public function test_overrides_existing_type(): void {
		$tags = $this->render_meta_tags( '<meta property="og:type" content="article">' );

		$this->assertSame( array( 'product' ), $tags['og:type'], 'The existing type should be replaced with a single product tag.' );
	}

	/**
	 * @testdox Should preserve existing metadata without mixing in featured image details.
	 */
	public function test_preserves_existing_metadata(): void {
		$tags = $this->render_meta_tags(
			'<meta property="og:title" content="SEO title">
			<meta property="og:description" content="SEO description">
			<meta property="og:image" content="https://example.org/seo.jpg">
			<meta property="og:image:width" content="600">
			<meta name="twitter:card" content="summary">
			<meta property="product:price:amount" content="25.00">
			<meta property="product:price:currency" content="EUR">'
		);

		$this->assertSame( array( 'SEO title' ), $tags['og:title'], 'An existing title should be preserved without duplication.' );
		$this->assertSame( array( 'SEO description' ), $tags['og:description'], 'An existing description should be preserved without duplication.' );
		$this->assertSame( array( 'https://example.org/seo.jpg' ), $tags['og:image'], 'An existing image should be preserved without duplication.' );
		$this->assertSame( array( '600' ), $tags['og:image:width'], 'Existing image dimensions should be preserved.' );
		$this->assertArrayNotHasKey( 'og:image:height', $tags, 'The featured image height should not describe another provider\'s image.' );
		$this->assertArrayNotHasKey( 'og:image:alt', $tags, 'The featured image alternative text should not describe another provider\'s image.' );
		$this->assertArrayNotHasKey( 'og:image:type', $tags, 'The featured image MIME type should not describe another provider\'s image.' );
		$this->assertSame( array( 'summary' ), $tags['twitter:card'], 'An existing Twitter card should be preserved.' );
		$this->assertSame( array( 'product' ), $tags['og:type'], 'Missing metadata should still be added alongside existing tags.' );
		$this->assertSame( array( '25.00' ), $tags['product:price:amount'], 'An existing price should be preserved without duplication.' );
		$this->assertSame( array( 'EUR' ), $tags['product:price:currency'], 'An existing currency should be preserved without duplication.' );
	}

	/**
	 * @testdox Should leave both brand aliases to a provider that supplies either one.
	 * @testWith ["product:brand", "og:brand"]
	 *           ["og:brand", "product:brand"]
	 *
	 * @param string $provider_property Provider's brand property.
	 * @param string $other_property Other brand property.
	 */
	public function test_existing_brand_suppresses_fallback_aliases( string $provider_property, string $other_property ): void {
		$brand = wp_insert_term( 'Store brand', 'product_brand' );
		$this->assertIsArray( $brand );
		$this->product->set_brand_ids( array( $brand['term_id'] ) );
		$this->product->save();

		$tags = $this->render_meta_tags( '' );
		$this->assertSame( array( 'Store brand' ), $tags['product:brand'] ?? array() );
		$this->assertSame( array( 'Store brand' ), $tags['og:brand'] ?? array() );

		$tags = $this->render_meta_tags( '<meta property="' . $provider_property . '" content="Provider brand">' );
		$this->assertSame( array( 'Provider brand' ), $tags[ $provider_property ] ?? array() );
		$this->assertArrayNotHasKey( $other_property, $tags );
	}

	/**
	 * @testdox Should allow disabling Open Graph output without changing existing metadata.
	 */
	public function test_enable_filter_disables_output(): void {
		add_filter( 'woocommerce_enable_open_graph', '__return_false' );

		$tags = $this->render_meta_tags( '<meta property="og:type" content="article">' );

		$this->assertCount( 1, $tags, 'No metadata should be added when Open Graph output is disabled.' );
		$this->assertSame( array( 'article' ), $tags['og:type'], 'Existing metadata should remain unchanged when output is disabled.' );
	}

	/**
	 * @testdox Should pass the product to the tags filter and honor added, changed, and removed tags.
	 */
	public function test_tags_filter_customizes_output(): void {
		add_filter(
			'woocommerce_open_graph_tags',
			function ( $tags, $context ) {
				$this->assertInstanceOf( WC_Product::class, $context, 'The filter context should be a product.' );
				$this->assertSame( $this->product->get_id(), $context->get_id(), 'The filter should receive the queried product.' );

				foreach ( $tags as $index => $group ) {
					if ( isset( $group['og:description'] ) || isset( $group['og:type'] ) ) {
						unset( $tags[ $index ] );
					} elseif ( isset( $group['og:title'] ) ) {
						$tags[ $index ]['og:title'] = 'Custom & "title"';
					} elseif ( isset( $group['twitter:card'] ) ) {
						$tags[ $index ]['twitter:card'] = 'summary';
					}
				}
				$tags[] = array( 'twitter:title' => 'Twitter title' );

				return $tags;
			},
			10,
			2
		);

		$tags = $this->render_meta_tags( '<meta property="og:type" content="article">' );

		$this->assertSame( array( 'Custom & "title"' ), $tags['og:title'], 'The filtered title should be rendered and escaped correctly.' );
		$this->assertSame( array( 'Twitter title' ), $tags['twitter:title'], 'New tags supplied by the filter should be rendered.' );
		$this->assertSame( array( 'summary' ), $tags['twitter:card'], 'The filtered Twitter card should be kept even when the product has an image.' );
		$this->assertArrayNotHasKey( 'og:description', $tags, 'Removed tags should not be rendered.' );
		$this->assertSame( array( 'article' ), $tags['og:type'], 'Removing the type candidate should preserve the existing provider\'s type.' );
	}

	/**
	 * @testdox Should render product metadata supplied through the tags filter.
	 */
	public function test_tags_filter_adds_product_metadata(): void {
		add_filter(
			'woocommerce_open_graph_tags',
			static function ( $tags ) {
				foreach ( $tags as $index => $group ) {
					unset( $group['product:price:amount'], $group['product:price:currency'] );
					if ( $group ) {
						$tags[ $index ] = $group;
					} else {
						unset( $tags[ $index ] );
					}
				}
				$tags[] = array( 'product:price:amount' => '19.99' );
				$tags[] = array( 'product:price:currency' => 'USD' );

				return $tags;
			}
		);

		$tags = $this->render_meta_tags( '' );

		$this->assertSame( array( '19.99' ), $tags['product:price:amount'] ?? array(), 'The filtered product price should be rendered once.' );
		$this->assertSame( array( 'USD' ), $tags['product:price:currency'] ?? array(), 'The filtered product currency should be rendered once.' );
	}

	/**
	 * Process a product document through the output filter and collect its metadata.
	 *
	 * @param string $head Existing head markup.
	 * @return array<string, array<string|bool|null>> Metadata values grouped by property to retain duplicates.
	 */
	private function render_meta_tags( string $head ): array {
		$this->sut->handle_template_redirect();
		$html      = apply_filters( 'wp_template_enhancement_output_buffer', '<!DOCTYPE html><html><head>' . $head . '</head><body></body></html>' );
		$processor = new WP_HTML_Tag_Processor( $html );
		$tags      = array();

		while ( $processor->next_tag( array( 'tag_name' => 'META' ) ) ) {
			$property = $processor->get_attribute( 'property' ) ?? $processor->get_attribute( 'name' );
			if ( is_string( $property ) ) {
				$tags[ $property ][] = $processor->get_attribute( 'content' );
			}
		}

		return $tags;
	}
}
