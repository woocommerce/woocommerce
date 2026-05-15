<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\StoreApi\Schemas\V1\ImageAttachmentSchema;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * ImageAttachmentSchemaTest.
 */
class ImageAttachmentSchemaTest extends TestCase {
	/**
	 * The system under test.
	 *
	 * @var ImageAttachmentSchema
	 */
	private $sut;

	/**
	 * Image sizes requested via wp_get_attachment_image_src during a single call.
	 *
	 * @var array<string>
	 */
	private $requested_sizes = array();

	/**
	 * Attachment ID used for tests.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Set up before test.
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$extend_schema     = new ExtendSchema( $formatters );
		$schema_controller = new SchemaController( $extend_schema );
		$this->sut         = $schema_controller->get( ImageAttachmentSchema::IDENTIFIER );

		$this->attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'Test Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
				'guid'           => 'http://example.org/wp-content/uploads/test.jpg',
			)
		);

		$this->requested_sizes = array();
		add_filter( 'wp_get_attachment_image_src', array( $this, 'capture_image_size' ), 10, 3 );
	}

	/**
	 * Tear down after test.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_get_attachment_image_src', array( $this, 'capture_image_size' ) );
		if ( $this->attachment_id ) {
			wp_delete_attachment( $this->attachment_id, true );
		}
		parent::tearDown();
	}

	/**
	 * Records the size requested for each call.
	 *
	 * @param array|false  $image         Image src array or false.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|int[] $size          Requested image size.
	 * @return array|false
	 */
	public function capture_image_size( $image, $attachment_id, $size ) {
		if ( is_string( $size ) ) {
			$this->requested_sizes[] = $size;
		}
		return $image;
	}

	/**
	 * The default thumbnail size remains `woocommerce_thumbnail` for backwards compatibility.
	 */
	public function test_default_thumbnail_size_is_woocommerce_thumbnail() {
		$this->sut->get_item_response( $this->attachment_id );

		$this->assertContains( 'woocommerce_thumbnail', $this->requested_sizes, 'Expected woocommerce_thumbnail to be requested by default.' );
		$this->assertContains( 'full', $this->requested_sizes, 'Expected full size to still be requested for src.' );
	}

	/**
	 * Callers can request a smaller registered image size for the thumbnail fields.
	 */
	public function test_custom_thumbnail_size_is_passed_through() {
		$this->sut->get_item_response( $this->attachment_id, 'thumbnail' );

		$this->assertContains( 'thumbnail', $this->requested_sizes, 'Expected thumbnail size to be requested when explicitly passed.' );
		$this->assertNotContains( 'woocommerce_thumbnail', $this->requested_sizes, 'woocommerce_thumbnail should not be requested when overridden.' );
		$this->assertContains( 'full', $this->requested_sizes, 'full size should still be requested for src field.' );
	}

	/**
	 * Returns null for a missing/invalid attachment id.
	 */
	public function test_returns_null_for_invalid_attachment() {
		$this->assertNull( $this->sut->get_item_response( 0 ) );
		$this->assertNull( $this->sut->get_item_response( 0, 'thumbnail' ) );
	}
}
