<?php
/**
 * Marketing note tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Admin\Notes\Marketing;
use Automattic\WooCommerce\Admin\Notes\Note;


/**
 * Class WC_Tests_Marketing_Note
 */
class WC_Tests_Marketing_Note extends WC_Unit_Test_Case {

	/**
	 * @var Marketing
	 */
	private $instance;

	/**
	 * setUp
	 */
	public function setUp() {
		parent::setUp();
		$this->instance = new Marketing();
	}

	/**
	 * Given wc_admin is <= 5 days old
	 * When get_note() is called
	 * Then it should return null
	 */
	public function test_it_does_not_add_note_unless_5_days_old() {
		update_option( 'woocommerce_admin_install_timestamp', time() );

		$note = $this->instance->get_note();
		$this->assertNull( $note );
	}

	/**
	 * Given wc_admin is >= 5 days old
	 * When get_note() is called
	 * Then it should not return null
	 */
	public function test_it_does_add_note_when_5_days_old() {
		$five_days_ago = time() - 5 * 86400;
		update_option( 'woocommerce_admin_install_timestamp', $five_days_ago );

		$note = $this->instance->get_note();
		$this->assertNotNull( $note );
	}
}
