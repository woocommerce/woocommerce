<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates;

use Automattic\WooCommerce\EmailEditor\Engine\Templates\Template;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for managing WooCommerce email templates.
 *
 * @internal
 */
class TemplatesController {

	/**
	 * Prefix used for template identification.
	 *
	 * @var string
	 */
	private string $template_prefix = 'woocommerce';

	/**
	 * Initialize the controller by registering hooks.
	 *
	 * @internal
	 * @return void
	 */
	final public function init(): void {
		add_filter( 'woocommerce_email_editor_register_templates', array( $this, 'register_templates' ) );
		// Priority 100 ensures this runs last to remove email templates from the Site Editor.
		add_filter( 'get_block_templates', array( $this, 'filter_email_templates' ), 100, 1 );
	}

	/**
	 * Filters out email templates from the block templates list in the Site Editor.
	 *
	 * This function is necessary to prevent email templates from appearing in the Site Editor's
	 * template list. Email templates are stored in the database with the same post type as site
	 * templates, which causes them to be included in the Site Editor by default. By filtering
	 * them out, we ensure that only relevant site templates are displayed, improving the user
	 * experience and maintaining the intended separation between email and site templates.
	 *
	 * @param array $templates The list of block templates.
	 * @return array The filtered list of block templates.
	 */
	public function filter_email_templates( $templates ) {
		// Skip filtering if we're in a REST API request to avoid affecting API endpoints.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $templates;
		}

		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return $templates;
		}

		$current_screen = get_current_screen();
		if ( $current_screen && 'site-editor' === $current_screen->id ) {
			$templates = array_filter(
				$templates,
				function ( $template ) {
					return WooEmailTemplate::TEMPLATE_SLUG !== $template->slug;
				}
			);
		}

		return $templates;
	}

	/**
	 * Register WooCommerce email templates with the template registry.
	 *
	 * @param Templates_Registry $templates_registry The template registry instance.
	 * @return Templates_Registry
	 */
	public function register_templates( Templates_Registry $templates_registry ) {
		$templates   = array();
		$templates[] = new WooEmailTemplate();

		foreach ( $templates as $template ) {
			$the_template = new Template(
				$this->template_prefix,
				$template->get_slug(),
				$template->get_title(),
				$template->get_description(),
				$template->get_content(),
				array( Integration::EMAIL_POST_TYPE )
			);
			$templates_registry->register( $the_template );
		}

		return $templates_registry;
	}
}
