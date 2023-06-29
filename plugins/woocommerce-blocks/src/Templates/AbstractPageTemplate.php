<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * AbstractPageTemplate class.
 *
 * Shared logic for page templates.
 *
 * @internal
 */
abstract class AbstractPageTemplate {
	/**
	 * Page Template functionality is only initialized when using a block theme.
	 */
	public function __construct() {
		if ( wc_current_theme_is_fse_theme() ) {
			$this->init();
		}
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		add_filter( 'page_template_hierarchy', array( $this, 'page_template_hierarchy' ), 1 );
		add_action( 'current_screen', array( $this, 'page_template_editor_redirect' ) );
		add_filter( 'pre_get_document_title', array( $this, 'page_template_title' ) );
	}

	/**
	 * Returns the template slug.
	 *
	 * @return string
	 */
	abstract public static function get_slug();

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	abstract public static function get_placeholder_page();

	/**
	 * Should return the title of the page.
	 *
	 * @return string
	 */
	abstract public static function get_template_title();

	/**
	 * Should return true on pages/endpoints/routes where the template should be shown.
	 *
	 * @return boolean
	 */
	abstract protected function is_active_template();

	/**
	 * Returns the URL to edit the template.
	 *
	 * @return string
	 */
	protected function get_edit_template_url() {
		return admin_url( 'site-editor.php?postType=wp_template&postId=woocommerce%2Fwoocommerce%2F%2F' . $this->get_slug() );
	}

	/**
	 * When the page should be displaying the template, add it to the hierarchy.
	 *
	 * This places the template name e.g. `cart`, at the beginning of the template hierarchy array. The hook priority
	 * is 1 to ensure it runs first; other consumers e.g. extensions, could therefore inject their own template instead
	 * of this one when using the default priority of 10.
	 *
	 * @param array $templates Templates that match the pages_template_hierarchy.
	 */
	public function page_template_hierarchy( $templates ) {
		if ( $this->is_active_template() ) {
			array_unshift( $templates, $this->get_slug() );
		}
		return $templates;
	}

	/**
	 * Redirect the edit page screen to the template editor.
	 *
	 * @param \WP_Screen $current_screen Current screen information.
	 */
	public function page_template_editor_redirect( \WP_Screen $current_screen ) {
		$page         = $this->get_placeholder_page();
		$edit_page_id = 'page' === $current_screen->id && ! empty( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $page && $edit_page_id === $page->ID ) {
			wp_safe_redirect( $this->get_edit_template_url() );
			exit;
		}
	}

	/**
	 * Filter the page title when the template is active.
	 *
	 * @param string $title Page title.
	 * @return string
	 */
	public function page_template_title( $title ) {
		if ( $this->is_active_template() ) {
			return $this->get_template_title();
		}
		return $title;
	}
}
