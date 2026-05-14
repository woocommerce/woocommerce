<?php
/**
 * Tests for the ShopPageEditor class.
 *
 * @package WooCommerce\Tests\Internal\Admin
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ShopPageEditor;
use WC_Unit_Test_Case;

/**
 * Tests for the ShopPageEditor class.
 */
class ShopPageEditorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShopPageEditor
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShopPageEditor();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Cleaning up test state.
		unset( $GLOBALS['current_screen'], $GLOBALS['post'] );
		parent::tearDown();
	}

	/**
	 * @testdox Should not enqueue notice when screen is not a page.
	 */
	public function test_does_not_enqueue_notice_for_non_page_screen(): void {
		$this->set_current_screen( 'post' );

		$this->sut->maybe_add_shop_page_notice();

		$this->assertFalse(
			$this->has_inline_script_containing( 'createWarningNotice' ),
			'Notice should not be enqueued for non-page post types'
		);
	}

	/**
	 * @testdox Should not enqueue notice when editing a page that is not the shop page.
	 */
	public function test_does_not_enqueue_notice_for_non_shop_page(): void {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->set_current_screen( 'page' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up test state.
		$GLOBALS['post'] = get_post( $page_id );

		$this->sut->maybe_add_shop_page_notice();

		$this->assertFalse(
			$this->has_inline_script_containing( 'createWarningNotice' ),
			'Notice should not be enqueued for non-shop pages'
		);
	}

	/**
	 * @testdox Should enqueue notice when editing the shop page.
	 */
	public function test_enqueues_notice_for_shop_page(): void {
		$shop_page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );
		$this->set_current_screen( 'page' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up test state.
		$GLOBALS['post'] = get_post( $shop_page_id );

		$this->sut->maybe_add_shop_page_notice();

		$this->assertTrue(
			$this->has_inline_script_containing( 'createWarningNotice' ),
			'Notice should be enqueued for the shop page'
		);
	}

	/**
	 * @testdox Should include a link to the Product Catalog template in the notice.
	 */
	public function test_notice_includes_template_link(): void {
		$shop_page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );
		$this->set_current_screen( 'page' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up test state.
		$GLOBALS['post'] = get_post( $shop_page_id );

		$this->sut->maybe_add_shop_page_notice();

		$this->assertTrue(
			$this->has_inline_script_containing( 'archive-product' ),
			'Notice should include link to Product Catalog template'
		);
	}

	/**
	 * @testdox Should make the notice non-dismissible.
	 */
	public function test_notice_is_non_dismissible(): void {
		$shop_page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );
		$this->set_current_screen( 'page' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up test state.
		$GLOBALS['post'] = get_post( $shop_page_id );

		$this->sut->maybe_add_shop_page_notice();

		$this->assertTrue(
			$this->has_inline_script_containing( 'isDismissible: false' ),
			'Notice should be non-dismissible'
		);
	}

	/**
	 * @testdox Should not enqueue notice when no shop page is configured.
	 */
	public function test_does_not_enqueue_notice_when_no_shop_page_set(): void {
		delete_option( 'woocommerce_shop_page_id' );
		$this->set_current_screen( 'page' );
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up test state.
		$GLOBALS['post'] = get_post( $page_id );

		$this->sut->maybe_add_shop_page_notice();

		$this->assertFalse(
			$this->has_inline_script_containing( 'createWarningNotice' ),
			'Notice should not be enqueued when no shop page is configured'
		);
	}

	/**
	 * @testdox Should add a Shop page post state label.
	 */
	public function test_adds_shop_page_post_state(): void {
		$shop_page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		$post_states = $this->sut->add_shop_page_state( array(), get_post( $shop_page_id ) );

		$this->assertSame( 'Shop page', $post_states['woocommerce_shop_page'] );
	}

	/**
	 * @testdox Should hide template choices for the Shop page.
	 */
	public function test_hides_template_choices_for_shop_page(): void {
		$shop_page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		$page_templates = $this->sut->hide_shop_page_templates(
			array( 'template.php' => 'Template' ),
			wp_get_theme(),
			get_post( $shop_page_id ),
			'page'
		);

		$this->assertSame( array(), $page_templates );
	}

	/**
	 * @testdox Should keep template choices for non-Shop pages.
	 */
	public function test_keeps_template_choices_for_non_shop_page(): void {
		$page_id        = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$page_templates = array( 'template.php' => 'Template' );

		$this->assertSame(
			$page_templates,
			$this->sut->hide_shop_page_templates( $page_templates, wp_get_theme(), get_post( $page_id ), 'page' )
		);
	}

	/**
	 * @testdox Should disable available templates in block editor settings for the Shop page.
	 */
	public function test_disables_available_templates_for_shop_page(): void {
		$shop_page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		$settings = $this->sut->lock_shop_page_template_selector(
			array( 'availableTemplates' => array( 'template.php' => 'Template' ) ),
			new \WP_Block_Editor_Context( array( 'post' => get_post( $shop_page_id ) ) )
		);

		$this->assertSame( array(), $settings['availableTemplates'] );
	}

	/**
	 * Set the current screen to a given post type.
	 *
	 * @param string $post_type Post type slug.
	 */
	private function set_current_screen( string $post_type ): void {
		set_current_screen( 'post' );
		$screen            = get_current_screen();
		$screen->post_type = $post_type;
	}

	/**
	 * Check if wp-notices has an inline script containing the given string.
	 *
	 * @param string $needle String to search for.
	 * @return bool
	 */
	private function has_inline_script_containing( string $needle ): bool {
		$scripts = wp_scripts();

		if ( ! isset( $scripts->registered['wp-notices'] ) ) {
			return false;
		}

		$script = $scripts->registered['wp-notices'];

		if ( empty( $script->extra['after'] ) ) {
			return false;
		}

		foreach ( $script->extra['after'] as $inline ) {
			if ( strpos( $inline, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
