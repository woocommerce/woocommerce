<?php

/**
 * Class WC_Product_Download_Test.
 */
class WC_Product_Download_Test extends WC_Unit_Test_Case {

	/**
	 * Helper utitility to get mocked object of WC_Product_Download class.
	 *
	 * @return WC_Product_Download
	 */
	public function get_sut_with_get_file() {
		return $this->getMockBuilder( WC_Product_Download::class )
					->setMethods( array( 'get_file' ) )
					->getMock();
	}

	/**
	 * Test when file appears remote but is local.
	 */
	public function test_is_allowed_filetype_when_file_with_false_query_params() {
		$download = $this->get_sut_with_get_file();
		$payload = trailingslashit( site_url( '/' ) ) . 'non_exists/?/../../wp-config.php';
		$download->method( 'get_file' )->willReturn( $payload );
		$this->assertFalse( $download->is_allowed_filetype() );
	}

	/**
	 * Test when file appears remote, but is local and tries to appear remote by having characters to be stripped by esc_url_raw.
	 */
	public function test_is_allowed_filetype_when_file_with_quote_and_false_query_params() {
		$download = $this->get_sut_with_get_file();
		$payload = trailingslashit( site_url( '/' ) ) . '"non_exists/?/../../foo.php';
		$download->method( 'get_file' )->willReturn( $payload );
		$this->assertFalse( $download->is_allowed_filetype() );
	}

	/**
	 * Test when file has invalid scheme.
	 */
	public function test_is_allowed_filetype_when_file_with_url_escapable_scheme() {
		$download = $this->get_sut_with_get_file();
		$payload = trailingslashit( site_url( '/' ) );
		$payload = str_replace( 'http', 'http;', $payload );
		$payload = trailingslashit( $payload ) . 'wp-config.php?/../../foo'; // http;//example.com/wp-config.php?/../../foo.
		$download->method( 'get_file' )->willReturn( $payload );
		$this->assertFalse( $download->is_allowed_filetype() );
	}
}
