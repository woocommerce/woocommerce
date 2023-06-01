<?php
/**
 * PrepareUrl tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\PrepareUrl;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_PrepareUrl
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_PrepareUrl extends WC_Unit_Test_Case {
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
