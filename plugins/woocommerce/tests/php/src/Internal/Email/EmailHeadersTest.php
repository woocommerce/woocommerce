<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\EmailHeaders;
use WC_Unit_Test_Case;

/**
 * EmailHeaders test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\EmailHeaders
 */
class EmailHeadersTest extends WC_Unit_Test_Case {

	/**
	 * @testWith ["Foo\r\nBcc: a@b.test", "Foo Bcc: a@b.test"]
	 *           ["Smith, Jr.", "Smith Jr."]
	 *           ["María O'Brien", "María O'Brien"]
	 *           ["   ", ""]
	 *
	 * @param string $name     Name to clean.
	 * @param string $expected Expected cleaned name.
	 */
	public function test_sanitize_reply_to_name( string $name, string $expected ) {
		$this->assertSame( $expected, EmailHeaders::sanitize_reply_to_name( $name ) );
	}

	/**
	 * @testdox A "sanitize_text_field" filter callback reintroducing line breaks does not leak them into the result.
	 */
	public function test_sanitize_reply_to_name_removes_line_breaks_reintroduced_by_filter(): void {
		$filter = static fn() => "Shop\r\nX-Test: 1";
		add_filter( 'sanitize_text_field', $filter );

		try {
			$result = EmailHeaders::sanitize_reply_to_name( 'Shop' );
		} finally {
			remove_filter( 'sanitize_text_field', $filter );
		}

		$this->assertStringNotContainsString( "\r", $result );
		$this->assertStringNotContainsString( "\n", $result );
	}
}
