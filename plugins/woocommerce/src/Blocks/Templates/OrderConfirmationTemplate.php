<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

/**
 * OrderConfirmationTemplate class.
 *
 * @internal
 */
class OrderConfirmationTemplate extends AbstractPageTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'order-confirmation';

	/**
	 * The title of the template.
	 *
	 * @var string
	 */
	public $template_title;

	/**
	 * The description of the template.
	 *
	 * @var string
	 */
	public $template_description;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->template_title       = _x( 'Order Confirmation', 'Template name', 'woocommerce' );
		$this->template_description = __( 'The Order Confirmation template serves as a receipt and confirmation of a successful purchase. It includes a summary of the ordered items, shipping, billing, and totals.', 'woocommerce' );
		BlockTemplatesRegistry::register_template( $this );
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		parent::init();
		add_action( 'wp_before_admin_bar_render', array( $this, 'remove_edit_page_link' ) );
	}

	/**
	 * Remove edit page from admin bar.
	 */
	public function remove_edit_page_link() {
		if ( $this->is_active_template() ) {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'edit' );
		}
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	protected function get_placeholder_page() {
		return null;
	}

	/**
	 * True when viewing the Order Received endpoint.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {
		return is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Should return the title of the page.
	 *
	 * @return string
	 */
	public static function get_template_title() {
		return __( 'Order Confirmation', 'woocommerce' );
	}
}
