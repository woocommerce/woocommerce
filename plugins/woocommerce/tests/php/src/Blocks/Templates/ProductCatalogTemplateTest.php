<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\ProductCatalogTemplate;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductCatalogTemplate class.
 */
class ProductCatalogTemplateTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductCatalogTemplate
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProductCatalogTemplate();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET['post'], $_GET['action'] );
		parent::tearDown();
	}

	/**
	 * @testdox Should return null when not on post.php edit screen.
	 */
	public function test_redirect_returns_null_when_not_on_edit_screen(): void {
		$_GET['action'] = 'trash';
		$_GET['post']   = (string) wc_get_page_id( 'shop' );

		$result = $this->sut->redirect_shop_page_to_product_catalog_template();

		$this->assertNull( $result, 'Should not redirect when action is not edit' );
	}

	/**
	 * @testdox Should return null when post is not the shop page.
	 */
	public function test_redirect_returns_null_when_not_shop_page(): void {
		$_GET['action'] = 'edit';
		$_GET['post']   = '99999';

		$result = $this->sut->redirect_shop_page_to_product_catalog_template();

		$this->assertNull( $result, 'Should not redirect for non-shop pages' );
	}

	/**
	 * @testdox Should return null when shop page is not configured.
	 */
	public function test_redirect_returns_null_when_shop_page_not_configured(): void {
		$_GET['action'] = 'edit';
		$_GET['post']   = '-1';

		$result = $this->sut->redirect_shop_page_to_product_catalog_template();

		$this->assertNull( $result, 'Should not redirect when shop page is not set' );
	}

	/**
	 * @testdox Should return the site editor URL when editing the shop page on a block theme.
	 */
	public function test_redirect_returns_site_editor_url_for_shop_page(): void {
		if ( ! wp_is_block_theme() ) {
			$this->markTestSkipped( 'Requires a block theme.' );
		}

		wp_set_current_user( 1 );
		$_GET['action'] = 'edit';
		$_GET['post']   = (string) wc_get_page_id( 'shop' );

		$result = $this->sut->redirect_shop_page_to_product_catalog_template();

		$this->assertNotNull( $result, 'Should return a redirect URL for the shop page' );
		$this->assertStringContainsString( 'site-editor.php', $result );
		$this->assertStringContainsString( 'archive-product', $result );
		$this->assertStringContainsString( 'wp_template', $result );
	}

	/**
	 * @testdox Should build correct site editor URL with WooCommerce plugin slug.
	 */
	public function test_redirect_url_contains_correct_template_id(): void {
		if ( ! wp_is_block_theme() ) {
			$this->markTestSkipped( 'Requires a block theme.' );
		}

		wp_set_current_user( 1 );
		$_GET['action'] = 'edit';
		$_GET['post']   = (string) wc_get_page_id( 'shop' );

		$result = $this->sut->redirect_shop_page_to_product_catalog_template();

		$expected_post_id = BlockTemplateUtils::theme_has_template( 'archive-product' )
			? wp_get_theme()->get_stylesheet() . '//archive-product'
			: BlockTemplateUtils::PLUGIN_SLUG . '//archive-product';

		$this->assertStringContainsString( urlencode( $expected_post_id ), $result );
	}
}
