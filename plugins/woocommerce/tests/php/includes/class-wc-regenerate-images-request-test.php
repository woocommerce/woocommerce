<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Tests\Helpers\ImageAttachmentTrait;

/**
 * Tests for the WC_Regenerate_Images_Request class.
 */
class WC_Regenerate_Images_Request_Test extends WC_Unit_Test_Case {

	use ImageAttachmentTrait;

	/**
	 * The System Under Test.
	 *
	 * @var WC_Regenerate_Images_Request
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Regenerate_Images_Request();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->remove_added_uploads();

		parent::tearDown();
	}

	/**
	 * @testdox Bulk regeneration should keep top level metadata keys it does not own.
	 */
	public function test_task_preserves_custom_metadata_keys(): void {
		$attachment_id = $this->create_image_attachment( 1200, 800, 'wc-regen-request-test.jpg' );

		$this->add_custom_attachment_metadata(
			$attachment_id,
			array(
				'test_api_meta' => array( 'last_modified' => 1708332626 ),
				'test_cdn_id'   => 'abc123',
			)
		);

		$task = new ReflectionMethod( WC_Regenerate_Images_Request::class, 'task' );
		$task->setAccessible( true );
		$task->invoke( $this->sut, array( 'attachment_id' => $attachment_id ) );

		$stored = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		$this->assertArrayHasKey( 'test_api_meta', $stored, 'Third party metadata keys should survive bulk regeneration' );
		$this->assertArrayHasKey( 'test_cdn_id', $stored, 'Third party metadata keys should survive bulk regeneration' );
		$this->assertNotEmpty( $stored['sizes'], 'Bulk regeneration should still write the generated sizes' );
	}
}
