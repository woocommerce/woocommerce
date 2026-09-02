<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\VariationGallery\ClassicVariationGalleryAdmin;
use Automattic\WooCommerce\Internal\VariationGallery\LegacyVariationGalleryCompatibility;
use WC_Helper_Product;
use WC_Product_Variation;

/**
 * Tests for ClassicVariationGalleryAdmin.
 */
class ClassicVariationGalleryAdminTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ClassicVariationGalleryAdmin
	 */
	private $sut;

	/**
	 * @var LegacyVariationGalleryCompatibility
	 */
	private $legacy_compat;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut           = new ClassicVariationGalleryAdmin();
		$this->legacy_compat = new LegacyVariationGalleryCompatibility();
		$this->legacy_compat->register();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter(
			'woocommerce_product_variation_get_gallery_image_ids',
			array( $this->legacy_compat, 'maybe_read_legacy_gallery_image_ids' ),
			10
		);
		unset( $_POST['variable_gallery_image_ids'] );
		parent::tearDown();
	}

	/**
	 * @testdox The classic variation gallery field renders legacy images via the runtime fallback.
	 */
	public function test_render_variation_gallery_field_renders_legacy_images() {
		$variation = $this->create_variation();
		$image_ids = array(
			$this->create_attachment( 'Legacy gallery image 1' ),
			$this->create_attachment( 'Legacy gallery image 2' ),
		);

		update_post_meta(
			$variation->get_id(),
			'_wc_additional_variation_images',
			implode( ',', $image_ids )
		);

		ob_start();
		$this->sut->render_variation_gallery_field( 0, array(), get_post( $variation->get_id() ) );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString(
			'value="' . implode( ',', $image_ids ) . '"',
			$output
		);
	}

	/**
	 * @testdox A featured image absent from the gallery gets prepended in the editor view so the first save normalizes storage.
	 */
	public function test_render_variation_gallery_field_prepends_featured_image_when_missing_from_gallery() {
		$variation = $this->create_variation();
		$featured  = $this->create_attachment( 'Featured-only image' );
		$gallery   = $this->create_attachment( 'Gallery-only image' );

		$variation->set_image_id( $featured );
		$variation->set_gallery_image_ids( array( $gallery ) );
		$variation->save();

		ob_start();
		$this->sut->render_variation_gallery_field( 0, array(), get_post( $variation->get_id() ) );
		$output = (string) ob_get_clean();

		// Featured image must be prepended to the list (value) AND get the active class.
		$this->assertStringContainsString( 'value="' . $featured . ',' . $gallery . '"', $output );
		$this->assertMatchesRegularExpression(
			'/<li\s+class="wc-variation-gallery-thumb is-active"\s+data-attachment_id="' . $featured . '"/',
			$output
		);
	}

	/**
	 * @testdox Broken attachments are kept in the rendered list instead of silently dropped.
	 */
	public function test_render_variation_gallery_field_keeps_broken_attachments() {
		$variation = $this->create_variation();
		$missing   = 99999;
		$good      = $this->create_attachment( 'Intact gallery image' );

		$variation->set_gallery_image_ids( array( $missing, $good ) );
		$variation->save();

		ob_start();
		$this->sut->render_variation_gallery_field( 0, array(), get_post( $variation->get_id() ) );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString(
			'data-attachment_id="' . $missing . '"',
			$output
		);
	}

	/**
	 * @testdox Empty variation rows render the hidden gallery input needed for the first save.
	 */
	public function test_render_variation_gallery_field_renders_hidden_input_for_empty_gallery() {
		$variation = $this->create_variation();

		ob_start();
		$this->sut->render_variation_gallery_field( 0, array(), get_post( $variation->get_id() ) );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="variable_gallery_image_ids[0]"', $output );
		$this->assertStringContainsString( 'value=""', $output );
	}

	/**
	 * @testdox Saving an empty variation gallery clears featured + gallery and disables the legacy fallback.
	 */
	public function test_saving_empty_variation_gallery_disables_legacy_fallback() {
		$variation             = $this->create_variation();
		$pre_existing_featured = $this->create_attachment( 'Pre-existing featured image' );
		$image_ids             = array(
			$this->create_attachment( 'Legacy gallery image 1' ),
			$this->create_attachment( 'Legacy gallery image 2' ),
		);

		// Pre-set a featured image so we can assert the empty save also clears it.
		$variation->set_image_id( $pre_existing_featured );
		$variation->save();

		update_post_meta(
			$variation->get_id(),
			'_wc_additional_variation_images',
			implode( ',', $image_ids )
		);

		$_POST['variable_gallery_image_ids'][0] = '';

		$this->sut->persist_variation_gallery_field( $variation, 0 );
		$variation->save();

		$reloaded_variation = wc_get_product( $variation->get_id() );

		$this->assertSame( 0, $reloaded_variation->get_image_id( 'edit' ), 'Featured image should be cleared on empty save.' );
		$this->assertSame( array(), $reloaded_variation->get_gallery_image_ids() );
		$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation->get_id() ) );
	}

	/**
	 * @testdox Saving the unified list assigns position 0 as the featured image and the rest as the gallery (matching parent product semantics).
	 */
	public function test_saving_variation_gallery_splits_featured_from_gallery() {
		$variation = $this->create_variation();
		$image_ids = array(
			$this->create_attachment( 'Core gallery image 1' ),
			$this->create_attachment( 'Core gallery image 2' ),
			$this->create_attachment( 'Core gallery image 3' ),
		);

		// Includes a non-numeric token plus a duplicate of position 0 to verify
		// sanitization survives the split.
		$_POST['variable_gallery_image_ids'][0] = $image_ids[0] . ',not-an-id,' . $image_ids[1] . ',' . $image_ids[2] . ',' . $image_ids[0];

		$this->sut->persist_variation_gallery_field( $variation, 0 );
		$variation->save();

		$reloaded_variation = wc_get_product( $variation->get_id() );

		$this->assertSame(
			$image_ids[0],
			$reloaded_variation->get_image_id( 'edit' ),
			'Position 0 of the unified list should become the featured image.'
		);
		$this->assertSame(
			array( $image_ids[1], $image_ids[2] ),
			array_values( array_map( 'intval', $reloaded_variation->get_gallery_image_ids( 'edit' ) ) ),
			'Subsequent positions should become the gallery, disjoint from featured.'
		);
	}

	/**
	 * Create a variation for testing.
	 *
	 * @return WC_Product_Variation
	 */
	private function create_variation(): WC_Product_Variation {
		$product = WC_Helper_Product::create_variation_product();

		return wc_get_product( $product->get_children()[0] );
	}

	/**
	 * @param string $title Attachment title.
	 * @return int
	 */
	private function create_attachment( string $title ): int {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		update_post_meta( $attachment_id, '_wp_attached_file', sanitize_title( $title ) . '.jpg' );

		return $attachment_id;
	}
}
