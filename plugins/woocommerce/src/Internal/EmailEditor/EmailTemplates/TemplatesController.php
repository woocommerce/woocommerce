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
