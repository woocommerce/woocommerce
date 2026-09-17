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
	 * @dataProvider provider_sanitize_reply_to_name
	 * @param string $name     Name to clean.
	 * @param string $expected Expected cleaned name.
	 */
	public function test_sanitize_reply_to_name( string $name, string $expected ) {
		$this->assertSame( $expected, EmailHeaders::sanitize_reply_to_name( $name ) );
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function provider_sanitize_reply_to_name(): array {
		return array(
			'CRLF collapsed to spaces' => array( "Foo\r\nBcc: a@b.test", 'Foo Bcc: a@b.test' ),
			'commas removed'           => array( 'Smith, Jr.', 'Smith Jr.' ),
			'unicode name unchanged'   => array( "María O'Brien", "María O'Brien" ),
			'whitespace only'          => array( '   ', '' ),
		);
	}
}
