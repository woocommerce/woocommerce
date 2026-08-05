<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Regenerate_Images_Request class.
 */
class WC_Regenerate_Images_Request_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Regenerate_Images_Request
	 */
	private $sut;

	/**
	 * Attachment created by a test, removed on teardown.
	 *
	 * @var int
	 */
	private $attachment_id = 0;

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
		if ( $this->attachment_id ) {
			wp_delete_attachment( $this->attachment_id, true );
			$this->attachment_id = 0;
		}

		parent::tearDown();
	}

	/**
	 * Create an attachment backed by a real image file.
	 *
	 * @return int Attachment ID.
	 */
	private function create_attachment(): int {
		$uploads = wp_upload_dir();
		$file    = trailingslashit( $uploads['path'] ) . 'wc-regen-request-test.jpg';

		wp_mkdir_p( $uploads['path'] );

		$image = imagecreatetruecolor( 1200, 800 );
		imagefilledrectangle( $image, 0, 0, 1200, 800, imagecolorallocate( $image, 30, 90, 140 ) );
		imagejpeg( $image, $file, 90 );
		imagedestroy( $image );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'WC regen request test',
				'post_status'    => 'inherit',
			),
			$file
		);

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );

		return $attachment_id;
	}

	/**
	 * @testdox Bulk regeneration should keep top level metadata keys it does not own.
	 */
	public function test_task_preserves_custom_metadata_keys(): void {
		$this->attachment_id = $this->create_attachment();

		$metadata                  = wp_get_attachment_metadata( $this->attachment_id, true );
		$metadata['test_api_meta'] = array( 'last_modified' => 1708332626 );
		$metadata['test_cdn_id']   = 'abc123';
		wp_update_attachment_metadata( $this->attachment_id, $metadata );

		$task = new ReflectionMethod( WC_Regenerate_Images_Request::class, 'task' );
		$task->setAccessible( true );
		$task->invoke( $this->sut, array( 'attachment_id' => $this->attachment_id ) );

		$stored = get_post_meta( $this->attachment_id, '_wp_attachment_metadata', true );

		$this->assertArrayHasKey( 'test_api_meta', $stored, 'Third party metadata keys should survive bulk regeneration' );
		$this->assertArrayHasKey( 'test_cdn_id', $stored, 'Third party metadata keys should survive bulk regeneration' );
		$this->assertNotEmpty( $stored['sizes'], 'Bulk regeneration should still write the generated sizes' );
	}
}
