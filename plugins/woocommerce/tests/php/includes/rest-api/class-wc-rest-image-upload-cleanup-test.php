<?php
declare( strict_types = 1 );

/**
 * Tests for cleanup of image downloads used by REST writes.
 *
 * @package WooCommerce\Tests\RestApi
 */
class WC_REST_Image_Upload_Cleanup_Test extends WC_REST_Unit_Test_Case {

	/** @var bool Tests register their own controller's routes, which a reused server would keep serving from the first test. */
	protected static $reuse_rest_server = false;

	/** @var array Downloaded files, including files with no attachment. */
	private $uploads = array();

	/** @var int[] Attachments whose physical files need teardown. */
	private $attachment_ids = array();

	/** @var string Contents served by the fake download transport. */
	private $download_content;

	/**
	 * Set up a real upload pipeline with a local download fixture.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->download_content = file_get_contents( WC_ABSPATH . 'tests/legacy/data/Dr1Bczxq4q.png' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				if ( 'example.org' !== wp_parse_url( $url, PHP_URL_HOST ) ) {
					return $response;
				}
				file_put_contents( $args['filename'], $this->download_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Emulate download_url writing its temporary file.
				return array(
					'headers'  => array(),
					'body'     => '',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			10,
			3
		);
		add_action(
			'woocommerce_rest_api_uploaded_image_from_url',
			function ( $upload ) {
				$this->uploads[] = $upload;
			}
		);
		add_action(
			'add_attachment',
			function ( $attachment_id ) {
				$this->attachment_ids[] = $attachment_id;
			}
		);
	}

	/**
	 * Remove physical files, which database rollback cannot remove.
	 */
	public function tearDown(): void {
		try {
			foreach ( $this->attachment_ids as $attachment_id ) {
				wp_delete_attachment( $attachment_id, true );
			}
			foreach ( $this->uploads as $upload ) {
				if ( file_exists( $upload['file'] ) ) {
					wp_delete_file( $upload['file'] );
				}
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * One request per image-setting implementation; inherited versions share these paths.
	 *
	 * @return array
	 */
	public static function provide_write_requests(): array {
		return array(
			'products v1'   => array( 'products', 1, 'POST' ),
			'products v2'   => array( 'products', 2, 'POST' ),
			'products v3'   => array( 'products', 3, 'POST' ),
			'products v4'   => array( 'products', 4, 'POST' ),
			'variations v3' => array( 'variations', 3, 'PUT' ),
			'categories v1' => array( 'categories', 1, 'POST' ),
			'categories v3' => array( 'categories', 3, 'PUT' ),
		);
	}

	/**
	 * Build a request with the minimum parent or existing object it needs.
	 *
	 * @param string $kind Object kind.
	 * @param int    $version API version.
	 * @param string $method HTTP method.
	 * @param array  $image Image request data.
	 * @return WP_REST_Request
	 */
	private function image_request( string $kind, int $version, string $method, array $image ): WP_REST_Request {
		$route = "/wc/v$version/products";
		if ( 4 === $version ) {
			$sut = new Automattic\WooCommerce\Internal\RestApi\Routes\V4\Products\Controller();
			$sut->register_routes();
		}

		if ( 'categories' === $kind ) {
			$route .= '/categories';
			if ( 'PUT' === $method ) {
				$term   = wp_insert_term( 'Existing category', 'product_cat' );
				$route .= '/' . $term['term_id'];
			}
		} elseif ( 'variations' === $kind ) {
			$product = new WC_Product_Variable();
			$product->save();
			$route .= '/' . $product->get_id() . '/variations';
			if ( 'PUT' === $method ) {
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $product->get_id() );
				$route .= '/' . $variation->save();
			}
		} elseif ( 'PUT' === $method ) {
			$product = new WC_Product_Simple();
			$route  .= '/' . $product->save();
		}

		$request = new WP_REST_Request( $method, $route );
		$request->set_param( 'name', 'Image cleanup test' );
		$request->set_param( 'products' === $kind ? 'images' : 'image', 'products' === $kind ? array( $image ) : $image );
		return $request;
	}

	/**
	 * Enable only this test's unfiltered upload capability without a global constant.
	 */
	private function allow_unfiltered_uploads(): void {
		add_filter(
			'map_meta_cap',
			static function ( $caps, $cap ) {
				return 'unfiltered_upload' === $cap ? array( 'exist' ) : $caps;
			},
			10,
			2
		);
	}

	/**
	 * @testdox Rejected new non-images are deleted even when the category write succeeds.
	 * @dataProvider provide_write_requests
	 *
	 * @param string $kind Object kind.
	 * @param int    $version API version.
	 * @param string $method HTTP method.
	 */
	public function test_rejected_download_is_deleted( string $kind, int $version, string $method ): void {
		$this->allow_unfiltered_uploads();
		$this->download_content = 'Inert non-image fixture, with no executable code.';
		$request                = $this->image_request( $kind, $version, $method, array( 'src' => 'https://example.org/rejected.php' ) );
		$response               = $this->server->dispatch( $request );

		$this->assertSame( 'categories' === $kind ? ( 'POST' === $method ? 201 : 200 ) : 400, $response->get_status() );
		$this->assertCount( 1, $this->uploads, 'The upload must reach the real sideload handler.' );
		$this->assertCount( 1, $this->attachment_ids );
		$this->assertFileDoesNotExist( $this->uploads[0]['file'] );
		$this->assertNull( get_post( $this->attachment_ids[0] ) );
	}

	/**
	 * @testdox The default upload policy rejects PHP before it reaches the uploads directory.
	 */
	public function test_default_policy_rejects_non_image(): void {
		$this->download_content = 'Inert non-image fixture.';
		$request                = $this->image_request( 'products', 3, 'POST', array( 'src' => 'https://example.org/rejected.php' ) );
		$response               = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_product_image_upload_error', $response->get_data()['code'] );
		$this->assertSame( array(), $this->uploads );
		$this->assertSame( array(), $this->attachment_ids );
	}

	/**
	 * @testdox An image error after a valid featured image does not create an unreported product, including in a batch.
	 * @testWith [false, true]
	 *           [true, true]
	 *           [false, false]
	 *           [true, false]
	 *
	 * @param bool $batch Whether to use the batch endpoint.
	 * @param bool $upload_error Whether to fail an upload rather than reject an existing non-image ID.
	 */
	public function test_later_image_error_does_not_create_unreported_product( bool $batch, bool $upload_error ): void {
		$invalid_image = $upload_error
			? array( 'src' => 'https://example.org/rejected.php' )
			: array( 'id' => self::factory()->post->create() );
		$product_data  = array(
			'name'   => 'Product with rejected image',
			'sku'    => 'rejected-image-product',
			'images' => array(
				array( 'src' => 'https://example.org/featured.png' ),
				$invalid_image,
			),
		);
		$request       = new WP_REST_Request( 'POST', '/wc/v3/products' . ( $batch ? '/batch' : '' ) );
		$request->set_body_params(
			$batch ? array(
				'create' => array(
					array(
						'name'   => 'Product before image error',
						'sku'    => 'before-image-error',
						'images' => array( array( 'src' => 'https://example.org/before.png' ) ),
					),
					$product_data,
					array(
						'name'   => 'Product after image error',
						'sku'    => 'after-image-error',
						'images' => array( array( 'src' => 'https://example.org/after.png' ) ),
					),
				),
			) : $product_data
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( $batch ? 200 : 400, $response->get_status() );

		if ( $batch ) {
			$this->assertCount( 3, $data['create'], 'Each batch item must have a response, even after an image error.' );
			$this->assertSame( 0, $data['create'][1]['id'] );
			foreach ( array( 0, 2 ) as $index ) {
				$item = $data['create'][ $index ];
				$this->assertArrayNotHasKey( 'error', $item );
				$this->assertGreaterThan( 0, $item['id'] );
				$this->assertSame( $item['id'], wc_get_product_id_by_sku( $item['sku'] ), 'Successful items must return the persisted product ID.' );
				$this->assertInstanceOf( WP_Post::class, get_post( $item['images'][0]['id'] ), 'An image error must not delete another batch item\'s image.' );
			}
		}

		$error = $batch ? $data['create'][1]['error'] : $data;
		$this->assertSame( $upload_error ? 'woocommerce_product_image_upload_error' : 'woocommerce_product_invalid_image_id', $error['code'] );
		$this->assertSame( 400, $error['data']['status'] );
		$this->assertSame( 0, wc_get_product_id_by_sku( $product_data['sku'] ), 'An image error must not reserve the failed product\'s SKU.' );
		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => 'any',
					'title'       => $product_data['name'],
					'fields'      => 'ids',
				)
			),
			'An image error must not leave a product without its ID in the response.'
		);
		$this->assertCount( $batch ? 3 : 1, $this->uploads, 'The featured image must be uploaded before the later image fails.' );
		$failed_image_index = $batch ? 1 : 0;
		$this->assertFileDoesNotExist( $this->uploads[ $failed_image_index ]['file'] );
		$this->assertNull( get_post( $this->attachment_ids[ $failed_image_index ] ), 'The failed product\'s new featured image must be deleted.' );
	}

	/**
	 * @testdox Suppressing an image upload error still returns the created product ID and keeps its valid images in a batch.
	 */
	public function test_batch_suppressed_image_error_returns_created_product(): void {
		add_filter( 'woocommerce_rest_suppress_image_upload_error', '__return_true' );
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
		$request->set_body_params(
			array(
				'create' => array(
					array(
						'name'   => 'Product with suppressed image error',
						'sku'    => 'suppressed-image-error',
						'images' => array(
							array( 'src' => 'https://example.org/featured.png' ),
							array( 'src' => 'https://example.org/rejected.php' ),
						),
					),
					array(
						'name'   => 'Product after suppressed image error',
						'sku'    => 'after-suppressed-image-error',
						'images' => array( array( 'src' => 'https://example.org/after.png' ) ),
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $data['create'] );
		$this->assertCount( 2, $this->uploads );
		foreach ( $data['create'] as $index => $item ) {
			$this->assertArrayNotHasKey( 'error', $item );
			$this->assertGreaterThan( 0, $item['id'] );
			$this->assertSame( $item['id'], wc_get_product_id_by_sku( $item['sku'] ) );
			$this->assertCount( 1, $item['images'] );
			$this->assertSame( $this->attachment_ids[ $index ], $item['images'][0]['id'] );
			$this->assertFileExists( $this->uploads[ $index ]['file'] );
		}
	}

	/**
	 * @testdox Other image-helper callers also delete their rejected new downloads.
	 * @testWith ["utility"]
	 *           ["importer"]
	 *
	 * @param string $caller Image upload caller.
	 */
	public function test_other_callers_delete_rejected_download( string $caller ): void {
		$this->allow_unfiltered_uploads();
		$this->download_content = 'Inert non-image fixture.';
		$error                  = null;

		try {
			if ( 'utility' === $caller ) {
				$sut = new Automattic\WooCommerce\RestApi\Utilities\ImageAttachment();
				$sut->upload_image_from_src( 'https://example.org/rejected.php' );
			} else {
				require_once WC_ABSPATH . 'includes/import/abstract-wc-product-importer.php';
				require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
				$sut = new WC_Product_CSV_Importer( WC_ABSPATH . 'tests/legacy/unit-tests/importer/sample.csv' );
				$sut->get_attachment_id_from_url( 'https://example.org/rejected.php', 0 );
			}
		} catch ( Exception $exception ) {
			$error = $exception;
		}

		$this->assertInstanceOf( Exception::class, $error );
		$this->assertCount( 1, $this->uploads );
		$this->assertCount( 1, $this->attachment_ids );
		$this->assertFileDoesNotExist( $this->uploads[0]['file'] );
		$this->assertNull( get_post( $this->attachment_ids[0] ) );
	}

	/**
	 * @testdox An unsuccessful attachment insert removes its otherwise untracked file.
	 */
	public function test_failed_attachment_insert_removes_file(): void {
		add_filter(
			'wp_insert_post_empty_content',
			static function ( $is_empty, $post ) {
				return 'attachment' === $post['post_type'] ? true : $is_empty;
			},
			10,
			2
		);
		$upload = wc_rest_upload_image_from_url( 'https://example.org/insert-failure.png' );
		$this->assertIsArray( $upload );
		$this->assertFileExists( $upload['file'] );

		$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload );

		$this->assertSame( 0, $attachment_id );
		$this->assertSame( array(), $this->attachment_ids );
		$this->assertFileDoesNotExist( $upload['file'] );
	}

	/**
	 * @testdox Rejecting an existing non-image never deletes it from the media library.
	 * @dataProvider provide_write_requests
	 *
	 * @param string $kind Object kind.
	 * @param int    $version API version.
	 * @param string $method HTTP method.
	 */
	public function test_existing_non_image_is_preserved( string $kind, int $version, string $method ): void {
		$upload          = wp_upload_bits( 'existing-manual.pdf', null, 'Existing PDF fixture' );
		$this->uploads[] = $upload;
		$attachment_id   = wp_insert_attachment( array( 'post_mime_type' => 'application/pdf' ), $upload['file'] );
		$request         = $this->image_request( $kind, $version, $method, array( 'id' => $attachment_id ) );
		$response        = $this->server->dispatch( $request );

		$this->assertSame( 'categories' === $kind ? ( 'POST' === $method ? 201 : 200 ) : 400, $response->get_status() );
		$this->assertInstanceOf( WP_Post::class, get_post( $attachment_id ) );
		$this->assertFileExists( $upload['file'] );
	}

	/**
	 * @testdox A valid PNG upload remains usable after a successful write.
	 * @dataProvider provide_write_requests
	 *
	 * @param string $kind Object kind.
	 * @param int    $version API version.
	 * @param string $method HTTP method.
	 */
	public function test_valid_image_is_preserved( string $kind, int $version, string $method ): void {
		$request  = $this->image_request(
			$kind,
			$version,
			$method,
			array(
				'src'      => 'https://example.org/accepted.png',
				'position' => 0,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 'POST' === $method ? 201 : 200, $response->get_status() );
		$this->assertCount( 1, $this->attachment_ids );
		$this->assertFileExists( $this->uploads[0]['file'] );
		$data  = $response->get_data();
		$image = 'products' === $kind ? $data['images'][0] : $data['image'];
		$this->assertSame( $this->attachment_ids[0], $image['id'] );
	}

	/**
	 * @testdox An SVG allowed by the existing upload filters remains accepted.
	 * @testWith ["products", 3, "POST"]
	 *           ["variations", 3, "POST"]
	 *           ["categories", 3, "POST"]
	 *
	 * @param string $kind Object kind.
	 * @param int    $version API version.
	 * @param string $method HTTP method.
	 */
	public function test_allowed_svg_is_preserved( string $kind, int $version, string $method ): void {
		$allow_svg = static function ( $mimes ) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		};
		add_filter( 'upload_mimes', $allow_svg );
		add_filter( 'woocommerce_rest_allowed_image_mime_types', $allow_svg );
		// Older Fileinfo versions need the XML declaration to recognize the SVG MIME type.
		$this->download_content = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>';
		$request                = $this->image_request( $kind, $version, $method, array( 'src' => 'https://example.org/accepted.svg' ) );
		$response               = $this->server->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
		$this->assertCount( 1, $this->attachment_ids );
		$this->assertSame( 'image/svg+xml', get_post_mime_type( $this->attachment_ids[0] ) );
		$this->assertFileExists( $this->uploads[0]['file'] );
	}

	/**
	 * @testdox A failed update removes unused new images and preserves an existing library file.
	 * @testWith [3]
	 *           [4]
	 *
	 * @param int $version API version.
	 */
	public function test_failed_update_cleans_only_unused_new_images( int $version ): void {
		$existing_id = self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf' ) );
		$request     = $this->image_request( 'products', $version, 'PUT', array() );
		$request->set_param(
			'images',
			array(
				array( 'src' => 'https://example.org/featured.png' ),
				array( 'src' => 'https://example.org/gallery.png' ),
				array( 'id' => $existing_id ),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertCount( 2, $this->uploads );
		foreach ( $this->uploads as $upload ) {
			$this->assertFileDoesNotExist( $upload['file'] );
		}
		foreach ( array_diff( $this->attachment_ids, array( $existing_id ) ) as $attachment_id ) {
			$this->assertNull( get_post( $attachment_id ) );
		}
		$this->assertInstanceOf( WP_Post::class, get_post( $existing_id ) );
	}

	/**
	 * @testdox An error after saving preserves the persisted featured and gallery images.
	 * @testWith [3]
	 *           [4]
	 *
	 * @param int $version API version.
	 */
	public function test_error_after_save_preserves_adopted_images( int $version ): void {
		$request = $this->image_request( 'products', $version, 'PUT', array() );
		$request->set_param(
			'images',
			array(
				array( 'src' => 'https://example.org/featured.png' ),
				array( 'src' => 'https://example.org/gallery.png' ),
			)
		);
		add_action(
			'woocommerce_rest_insert_product_object',
			static function () {
				throw new WC_REST_Exception( 'test_after_save', 'Failure after saving.', 400 );
			}
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'test_after_save', $response->get_data()['code'] );
		$this->assertCount( 2, $this->uploads );
		$this->assertSame( $this->attachment_ids[0], (int) get_post_meta( $request['id'], '_thumbnail_id', true ) );
		$this->assertSame( (string) $this->attachment_ids[1], get_post_meta( $request['id'], '_product_image_gallery', true ) );
		foreach ( $this->uploads as $upload ) {
			$this->assertFileExists( $upload['file'] );
		}
		foreach ( $this->attachment_ids as $attachment_id ) {
			$this->assertInstanceOf( WP_Post::class, get_post( $attachment_id ) );
		}
	}
}
