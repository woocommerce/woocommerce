<?php
/**
 * Unit tests for the Verticals API client.
 *
 * @package WooCommerce\Verticals\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\Verticals;

use Automattic\WooCommerce\Blocks\Verticals\Client;
use \WP_UnitTestCase;

/**
 * Class Client_Test.
 */
class ClientTest extends WP_UnitTestCase {
	/**
	 * The client instance.
	 *
	 * @var Client $client
	 */
	private $client;

	/**
	 * Initialize the client instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->client = new Client();
	}

	/**
	 * Test get_verticals returns an error when the request fails.
	 */
	public function test_get_verticals_returns_an_error_when_the_request_fails() {
		add_filter( 'pre_http_request', array( $this, 'return_wp_error' ), 10, 3 );

		$response = $this->client->get_verticals();
		$this->assertInstanceOf( 'WP_Error', $response );

		$error_data = $response->get_error_data();
		$this->assertEquals( 'error', $error_data['code'] );
		$this->assertEquals( 'Request failed.', $error_data['message'] );

		remove_filter( 'pre_http_request', array( $this, 'return_wp_error' ) );
	}

	/**
	 * Test get_verticals returns an error when the request is unsuccessful.
	 */
	public function test_get_verticals_returns_an_error_when_the_request_is_unsuccessful() {
		add_filter( 'pre_http_request', array( $this, 'return_request_failed' ), 10, 3 );

		$response = $this->client->get_verticals();
		$this->assertInstanceOf( 'WP_Error', $response );

		$error_data = $response->get_error_data();
		$this->assertEquals( 500, $error_data['status'] );
		$this->assertEquals( 'rest_error', $error_data['code'] );
		$this->assertEquals( 'The request failed.', $error_data['message'] );

		remove_filter( 'pre_http_request', array( $this, 'return_request_failed' ) );
	}

	/**
	 * Test get_verticals returns an array of verticals.
	 */
	public function test_get_verticals_returns_only_verticals_with_images() {
		add_filter( 'pre_http_request', array( $this, 'return_verticals' ), 10, 3 );

		$verticals = $this->client->get_verticals();
		$this->assertIsArray( $verticals );

		foreach ( $verticals as $vertical ) {
			$this->assertTrue( $vertical['has_vertical_images'] );
		}

		remove_filter( 'pre_http_request', array( $this, 'return_verticals' ) );
	}

	/**
	 * Test get_vertical_images returns an error when the request fails.
	 */
	public function test_get_vertical_images_returns_an_error_when_the_request_fails() {
		add_filter( 'pre_http_request', array( $this, 'return_wp_error' ), 10, 3 );

		$response = $this->client->get_vertical_images( 1 );
		$this->assertInstanceOf( 'WP_Error', $response );

		$error_data = $response->get_error_data();
		$this->assertEquals( 'error', $error_data['code'] );
		$this->assertEquals( 'Request failed.', $error_data['message'] );

		remove_filter( 'pre_http_request', array( $this, 'return_wp_error' ) );
	}

	/**
	 * Test get_vertical_images returns an error when the request is unsuccessful.
	 */
	public function test_get_vertical_images_returns_an_error_when_the_request_is_unsuccessful() {
		add_filter( 'pre_http_request', array( $this, 'return_request_failed' ), 10, 3 );

		$response = $this->client->get_vertical_images( 1 );
		$this->assertInstanceOf( 'WP_Error', $response );

		$error_data = $response->get_error_data();
		$this->assertEquals( 500, $error_data['status'] );
		$this->assertEquals( 'rest_error', $error_data['code'] );
		$this->assertEquals( 'The request failed.', $error_data['message'] );

		remove_filter( 'pre_http_request', array( $this, 'return_request_failed' ) );
	}

	/**
	 * Test get_vertical_images returns an array of images.
	 */
	public function test_get_vertical_images_returns_an_array_of_images() {
		add_filter( 'pre_http_request', array( $this, 'return_vertical_images' ), 10, 3 );

		$images = $this->client->get_vertical_images( 1 );
		$this->assertIsArray( $images );
		$this->assertCount( 2, $images );

		remove_filter( 'pre_http_request', array( $this, 'return_vertical_images' ) );
	}

	/**
	 * Returns a failed request.
	 */
	public function return_request_failed() {
		return array(
			'body'     => '{"code":"rest_error","message":"The request failed.","data":{"status":500}}',
			'response' => array(
				'code' => 500,
			),
		);
	}

	/**
	 * Returns a WP_Error.
	 */
	public function return_wp_error() {
		return new \WP_Error( 'error', 'Request failed.' );
	}

	/**
	 * Returns a list of verticals.
	 */
	public function return_verticals() {
		return array(
			'body'     => '[
				{"id": "3","parent_id": "0","root_id": "3","name": "Arts & Entertainment","title": "Arts & Entertainment","has_vertical_images": true},
                {"id": "184","parent_id": "3","root_id": "3","name": "Celebrities & Entertainment News","title": "Celebrities & Entertainment News","has_vertical_images": false},
                {"id": "316","parent_id": "3","root_id": "3","name": "Comics & Animation","title": "Comics & Animation","has_vertical_images": true},
                {"id": "317","parent_id": "316","root_id": "3","name": "Anime & Manga","title": "Anime & Manga","has_vertical_images": false}
            ]',
			'response' => array(
				'code' => 200,
			),
		);
	}

	/**
	 * Returns a list of images.
	 */
	public function return_vertical_images() {
		return array(
			'body'     => '[
				{"ID": 3536,"guid": "http://wpcomverticals.files.wordpress.com/2022/05/kyle-head-p6rntdapbuk-unsplash.jpg","post_title": "kyle-head-p6rNTdAPbuk-unsplash","post_excerpt": "","post_content": "","alt": "","width": 5096,"height": 3397,"size": "1.19 MB","filename": "kyle-head-p6rntdapbuk-unsplash.jpg","extension": "jpg","meta": {"vertical_id": "3","pexels_object": "","tag_names": [],"source_url": "","photographer": ""}},
                {"ID": 3535,"guid": "http://wpcomverticals.files.wordpress.com/2022/05/nadim-merrikh-mbcwo39jvsi-unsplash.jpg","post_title": "nadim-merrikh-MBCWO39JVsI-unsplash","post_excerpt": "","post_content": "","alt": "","width": 6000,"height": 4000,"size": "1.36 MB","filename": "nadim-merrikh-mbcwo39jvsi-unsplash.jpg","extension": "jpg","meta": {"vertical_id": "3","pexels_object": "","tag_names": [],"source_url": "","photographer": ""}}
            ]',
			'response' => array(
				'code' => 200,
			),
		);
	}
}
