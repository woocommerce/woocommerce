<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Admin_Post_Types class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * WC_Admin_Post_Types_Test
 */
class WC_Admin_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Post_Types
	 */
	private WC_Admin_Post_Types $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new WC_Admin_Post_Types();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'current_screen', array( $this->sut, 'setup_screen' ) );
		remove_action( 'check_ajax_referer', array( $this->sut, 'setup_screen' ) );
		remove_filter( 'post_updated_messages', array( $this->sut, 'post_updated_messages' ) );
		remove_filter( 'woocommerce_order_updated_messages', array( $this->sut, 'order_updated_messages' ) );
		remove_filter( 'bulk_post_updated_messages', array( $this->sut, 'bulk_post_updated_messages' ) );
		remove_action( 'admin_print_scripts', array( $this->sut, 'disable_autosave' ) );
		remove_action( 'edit_form_top', array( $this->sut, 'edit_form_top' ) );
		remove_filter( 'enter_title_here', array( $this->sut, 'enter_title_here' ), 1 );
		remove_action( 'edit_form_after_title', array( $this->sut, 'edit_form_after_title' ) );
		remove_filter( 'default_hidden_meta_boxes', array( $this->sut, 'hidden_meta_boxes' ) );
		remove_action( 'post_submitbox_misc_actions', array( $this->sut, 'product_data_visibility' ), 5 );
		remove_filter( 'theme_page_templates', array( $this->sut, 'hide_cpt_archive_templates' ) );
		remove_action( 'edit_form_top', array( $this->sut, 'show_cpt_archive_notice' ) );
		remove_filter( 'display_post_states', array( $this->sut, 'add_display_post_states' ) );
		remove_action( 'bulk_edit_custom_box', array( $this->sut, 'bulk_edit' ) );
		remove_action( 'quick_edit_custom_box', array( $this->sut, 'quick_edit' ) );
		remove_action( 'save_post', array( $this->sut, 'bulk_and_quick_edit_hook' ) );
		remove_action( 'woocommerce_product_bulk_and_quick_edit', array( $this->sut, 'bulk_and_quick_edit_save_post' ) );
		$this->remove_admin_notices_hooks_added_by_sut();

		parent::tearDown();
	}

	/**
	 * @testdox Catalog visibility renders before extension rows in the publish box.
	 */
	public function test_product_data_visibility_uses_early_publish_box_priority(): void {
		$this->assertSame(
			5,
			has_action( 'post_submitbox_misc_actions', array( $this->sut, 'product_data_visibility' ) ),
			'Catalog visibility should render before extension rows using an early priority.'
		);
	}

	/**
	 * Removes closures registered by the tested instance on the admin notices hook.
	 */
	private function remove_admin_notices_hooks_added_by_sut(): void {
		global $wp_filter;

		if ( empty( $wp_filter['admin_notices'] ) || ! $wp_filter['admin_notices'] instanceof WP_Hook ) {
			return;
		}

		foreach ( $wp_filter['admin_notices']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( ! $callback['function'] instanceof Closure ) {
					continue;
				}

				$reflection = new ReflectionFunction( $callback['function'] );

				if ( $this->sut === $reflection->getClosureThis() ) {
					remove_action( 'admin_notices', $callback['function'], (int) $priority );
				}
			}
		}
	}
}
