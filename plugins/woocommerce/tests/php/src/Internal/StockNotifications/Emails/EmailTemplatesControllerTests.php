<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the EmailTemplatesController class.
 */
class EmailTemplatesControllerTests extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var EmailTemplatesController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new EmailTemplatesController();
	}

	/**
	 * @testdox The product image is looked up with an integer attachment ID even when the product has no image.
	 */
	public function test_email_product_image_passes_an_integer_attachment_id_to_wordpress(): void {
		$product = wc_get_product( WC_Helper_Product::create_simple_product()->get_id() );

		$received_id = null;
		add_filter(
			'wp_get_attachment_image_src',
			static function ( $image, $attachment_id ) use ( &$received_id ) {
				if ( null === $received_id ) {
					$received_id = $attachment_id;
				}
				return $image;
			},
			10,
			2
		);

		ob_start();
		$this->sut->email_product_image( $product, null );
		ob_end_clean();

		$this->assertSame( 0, $received_id, 'A product without an image should pass integer zero to WordPress, not an empty string.' );
	}
}
