<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Payments\Finance\V1;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Link;
use WC_Unit_Test_Case;

/**
 * Tests for the Link value object.
 */
class LinkTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should trim the title and keep a valid https URL, including its query string.
	 */
	public function test_stores_title_and_url(): void {
		$sut = new Link( '  Deposit now ', 'https://example.com/payouts?currency=usd&amount=10' );

		$this->assertSame( 'Deposit now', $sut->get_title() );
		$this->assertSame( 'https://example.com/payouts?currency=usd&amount=10', $sut->get_url() );
	}

	/**
	 * @testdox Should reject an empty or whitespace-only title.
	 *
	 * @testWith [""]
	 *           ["  "]
	 *
	 * @param string $title The title.
	 */
	public function test_rejects_empty_title( string $title ): void {
		$this->expectException( \InvalidArgumentException::class );

		new Link( $title, 'https://example.com' );
	}

	/**
	 * @testdox Should reject URLs that are empty or not http(s).
	 *
	 * @testWith [""]
	 *           ["javascript:alert(1)"]
	 *           ["ftp://example.com/file"]
	 *           ["mailto:merchant@example.com"]
	 *
	 * @param string $url The url.
	 */
	public function test_rejects_invalid_urls( string $url ): void {
		$this->expectException( \InvalidArgumentException::class );

		new Link( 'Deposit now', $url );
	}
}
