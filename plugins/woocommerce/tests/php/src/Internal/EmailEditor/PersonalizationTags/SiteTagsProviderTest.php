<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\SiteTagsProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the SiteTagsProvider class: the site title an email shows.
 */
class SiteTagsProviderTest extends WC_Unit_Test_Case {

	/**
	 * Registry with the site tags registered.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private $registry;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->registry = new Personalization_Tags_Registry( new Email_Editor_Logger() );
		( new SiteTagsProvider() )->register_tags( $this->registry );
		update_option( 'blogname', "Pavel's \"Best\" Shop" );
	}

	/**
	 * @testdox Shows the site name with quotes decoded.
	 */
	public function test_site_title_decodes_quotes(): void {
		$this->assertSame( "Pavel's \"Best\" Shop", $this->value( array( 'order' => wc_create_order() ) ) );
		$this->assertSame( "Pavel's \"Best\" Shop", $this->value( array() ), 'Without an order' );
	}

	/**
	 * @testdox Shows the point of sale store name for an order placed at the point of sale.
	 */
	public function test_site_title_for_pos_order(): void {
		update_option( 'woocommerce_pos_store_name', 'Market Stall' );
		$order = wc_create_order();
		$order->set_created_via( 'pos-rest-api' );

		$this->assertSame( 'Market Stall', $this->value( array( 'order' => $order ) ) );
	}

	/**
	 * Resolves the site title tag in the given context.
	 *
	 * @param array $context Personalization context.
	 */
	private function value( array $context ): string {
		return $this->registry->get_by_token( '[woocommerce/site-title]' )->execute_callback( $context );
	}
}
