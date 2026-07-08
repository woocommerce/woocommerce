<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductGalleryLargeImage;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductGalleryLargeImage block type.
 */
class ProductGalleryLargeImageTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should replace only the selected product image with video markup.
	 */
	public function test_replaces_only_selected_product_image_with_video_markup(): void {
		$image_html = '<figure><a href="https://example.com/product" onclick="return false;" style="display:block"><img class="target" src="https://example.com/target.jpg" style="object-fit:cover;" alt="Target image"><span><img class="inside-link" src="https://example.com/inside-link.jpg" alt="Inside link image"></span></a><img class="outside-link" src="https://example.com/outside-link.jpg" alt="Outside link image"></figure>';
		$media      = array(
			'alt'       => 'Product video',
			'id'        => 123,
			'video_src' => 'https://example.com/product-video.mp4',
		);

		$result = $this->replace_product_image_with_video( $image_html, $media );

		$this->assertSame( 1, substr_count( $result, '<video ' ), 'Only one video should be inserted.' );
		$this->assertSame( 2, substr_count( $result, '<img ' ), 'Unmarked images should remain as images.' );
		$this->assertStringContainsString( 'src="https://example.com/product-video.mp4"', $result );
		$this->assertStringNotContainsString( 'src="https://example.com/target.jpg"', $result );
		$this->assertStringContainsString( 'src="https://example.com/inside-link.jpg"', $result );
		$this->assertStringContainsString( 'src="https://example.com/outside-link.jpg"', $result );
		$this->assertStringNotContainsString( 'data-wc-product-gallery-video-placeholder', $result );
	}

	/**
	 * Replace product image markup with product gallery video markup.
	 *
	 * @param string $image_html Product Image HTML.
	 * @param array  $media      Video media data.
	 * @return string
	 */
	private function replace_product_image_with_video( string $image_html, array $media ): string {
		$reflection = new \ReflectionClass( ProductGalleryLargeImage::class );
		$sut        = $reflection->newInstanceWithoutConstructor();
		$method     = $reflection->getMethod( 'replace_product_image_with_video' );

		$method->setAccessible( true );

		return (string) $method->invoke( $sut, $image_html, $media, array() );
	}
}
