<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\StoreResponses;
use WC_Unit_Test_Case;

/**
 * Tests for loading the store's own pages.
 */
class StoreResponsesTest extends WC_Unit_Test_Case {

	/**
	 * URLs requested in the current test.
	 *
	 * @var array<int, string>
	 */
	private array $requested = array();

	/**
	 * Answer each request from a map of URL to response, recording the URL.
	 *
	 * @param array<string, array|\WP_Error> $responses Responses by URL; anything else is a WP_Error.
	 */
	private function serve( array $responses ): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $responses ) {
				unset( $pre );
				$this->assertSame( 0, $args['redirection'], 'Redirects are followed here, not by the HTTP API' );
				$this->requested[] = $url;
				return $responses[ $url ] ?? new \WP_Error( 'http_request_failed', 'Timed out' );
			},
			10,
			3
		);
	}

	/**
	 * A response in the HTTP API's shape.
	 *
	 * @param int                   $status  Status code.
	 * @param string                $body    Body.
	 * @param array<string, string> $headers Headers.
	 */
	private static function response( int $status, string $body = '', array $headers = array() ): array {
		return array(
			'headers'  => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary( $headers ),
			'body'     => $body,
			'response' => array(
				'code'    => $status,
				'message' => '',
			),
			'cookies'  => array(),
		);
	}

	/**
	 * @testdox A path is loaded from the home URL once per scan, with its status and body.
	 */
	public function test_get_path_once(): void {
		$this->serve( array( home_url( '/llms.txt' ) => self::response( 200, '# Store' ) ) );
		$sut = new StoreResponses( true );

		$first  = $sut->get( '/llms.txt' );
		$second = $sut->get( home_url( '/llms.txt' ) );

		$this->assertSame( 200, $first['status'] );
		$this->assertSame( '# Store', $first['body'] );
		$this->assertSame( $first, $second );
		$this->assertCount( 1, $this->requested );
	}

	/**
	 * @testdox A failed request is null, so the check says couldn't check; a 404 is a response. After the home page fails no more requests are made.
	 */
	public function test_failed_request_is_null(): void {
		$this->serve( array( home_url( '/missing' ) => self::response( 404 ) ) );
		$sut = new StoreResponses( true );

		$this->assertSame( 404, $sut->get( '/missing' )['status'] );
		$this->assertNull( $sut->get( '/timeout' ) );
		$this->assertSame( 404, $sut->get( '/missing' )['status'], 'One slow page does not stop the scan' );
		$this->assertNull( $sut->home_page() );
		$this->assertNull( $sut->get( '/other' ) );
		$this->assertNull( $sut->post_json( '/missing-post', array() ) );
		$this->assertSame( array( home_url( '/missing' ), home_url( '/timeout' ), home_url( '/' ) ), $this->requested );
	}

	/**
	 * @testdox Redirects on the store's host are followed; a redirect to another host is not requested.
	 */
	public function test_redirects_stay_on_the_store_host(): void {
		$this->serve(
			array(
				home_url( '/a' ) => self::response( 301, '', array( 'Location' => '/b' ) ),
				home_url( '/b' ) => self::response( 200, 'B' ),
				home_url( '/c' ) => self::response( 302, '', array( 'Location' => 'https://elsewhere.test/c' ) ),
			)
		);
		$sut = new StoreResponses( true );

		$this->assertSame( 'B', $sut->get( '/a' )['body'] );
		$this->assertNull( $sut->get( '/c' ) );
		$this->assertNull( $sut->get( 'https://elsewhere.test/' ) );
		$this->assertNull( $sut->get( 'http://' . wp_parse_url( home_url(), PHP_URL_HOST ) . ':6379/' ), 'Another port on the store host is not the store' );
		$this->assertNull( $sut->get( 'gopher://' . wp_parse_url( home_url(), PHP_URL_HOST ) . '/' ) );
		$this->assertSame( array( home_url( '/a' ), home_url( '/b' ), home_url( '/c' ) ), $this->requested );
	}

	/**
	 * @testdox A redirect loop on the store's host gives up after two redirects.
	 */
	public function test_redirect_loop_gives_up(): void {
		$this->serve( array( home_url( '/loop' ) => self::response( 302, '', array( 'Location' => '/loop' ) ) ) );

		$this->assertNull( ( new StoreResponses( true ) )->get( '/loop' ) );
		$this->assertCount( 3, $this->requested );
	}

	/**
	 * @testdox In coming soon mode nothing is requested: visitors would get the coming soon page.
	 */
	public function test_coming_soon_requests_nothing(): void {
		$this->serve( array() );

		$this->assertNull( ( new StoreResponses( false ) )->get( '/' ) );
		$this->assertSame( array(), $this->requested );
	}
}
