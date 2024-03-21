<?php
/**
 * PrepareUrl tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\PrepareUrl;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_PrepareUrl
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_PrepareUrl extends WC_Unit_Test_Case {
	/**
	 * Test it returns default value when url is not string.
	 */
	public function test_it_returns_default_value_when_url_is_not_string() {

		$prepare_url = new PrepareUrl();
		$default     = 'default value';
		$result      = $prepare_url->transform( 123, null, $default );
		$this->assertEquals( $default, $result );
	}

	/**
	 * Test it returns default when url cannot be parsed.
	 */
	public function test_it_returns_default_when_url_cannot_be_parsed() {
		$prepare_url = new PrepareUrl();
		$default     = 'default value';
		$result      = $prepare_url->transform( 'invalid url', null, $default );
		$this->assertEquals( $default, $result );
	}

	/**
	 * Test it returns url without protocol and trailing slash.
	 */
	public function test_it_returns_flatten_array() {
		$urls = array(
			'https://www.example.com',
			'https://www.example.com/',
			'http://www.example.com',
			'http://www.example.com/',
			'test://www.example.com/',
		);

		$prepare_url = new PrepareUrl();

		foreach ( $urls as $url ) {
			$result = $prepare_url->transform( $url );
			$this->assertEquals( 'www.example.com', $result );
		}
	}
}
