<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\StoreTagsProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the StoreTagsProvider class: which store values an email shows.
 */
class StoreTagsProviderTest extends WC_Unit_Test_Case {

	/**
	 * Registry with the store tags registered.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private $registry;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( \WC_Email::class ) ) {
			require_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
		}
		$this->registry = new Personalization_Tags_Registry( new Email_Editor_Logger() );
		( new StoreTagsProvider() )->register_tags( $this->registry );
	}

	/**
	 * @testdox Uses the email's sender name and address when the email sets them.
	 */
	public function test_store_name_and_email_from_wc_email(): void {
		$wc_email = $this->create_wc_email( 'Shop Team', 'team@example.com' );

		$this->assertSame( 'Shop Team', $this->value( 'store-name', array( 'wc_email' => $wc_email ) ) );
		$this->assertSame( 'team@example.com', $this->value( 'store-email', array( 'wc_email' => $wc_email ) ) );
	}

	/**
	 * @testdox Falls back to the site name, with quotes decoded, and the admin email.
	 */
	public function test_store_name_and_email_fallbacks(): void {
		update_option( 'blogname', "Pavel's \"Best\" Shop" );
		update_option( 'admin_email', 'admin@example.com' );

		$this->assertSame( "Pavel's \"Best\" Shop", $this->value( 'store-name', array( 'wc_email' => $this->create_wc_email( '', 'team@example.com' ) ) ) );
		$this->assertSame( 'admin@example.com', $this->value( 'store-email', array() ) );
	}

	/**
	 * @testdox Shows the customer note with its line breaks, and nothing without a note.
	 */
	public function test_admin_order_note(): void {
		$wc_email                = $this->create_wc_email( '', '' );
		$wc_email->customer_note = "First line\nSecond line";

		$this->assertSame( "First line<br />\nSecond line", $this->value( 'admin-order-note', array( 'wc_email' => $wc_email ) ) );
		$this->assertSame( '', $this->value( 'admin-order-note', array( 'wc_email' => $this->create_wc_email( '', '' ) ) ) );
	}

	/**
	 * Resolves a tag in the given context.
	 *
	 * @param string $tag     Tag name without the woocommerce/ prefix.
	 * @param array  $context Personalization context.
	 */
	private function value( string $tag, array $context ): string {
		return $this->registry->get_by_token( "[woocommerce/{$tag}]" )->execute_callback( $context );
	}

	/**
	 * Email with the given sender.
	 *
	 * @param string $from_name    Sender name.
	 * @param string $from_address Sender address.
	 * @return \WC_Email&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_wc_email( string $from_name, string $from_address ) {
		$wc_email = $this->createMock( \WC_Email::class );
		$wc_email->method( 'get_from_name' )->willReturn( $from_name );
		$wc_email->method( 'get_from_address' )->willReturn( $from_address );
		return $wc_email;
	}
}
