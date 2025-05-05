<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer as CoreEmailEditorIntegration;

/**
 * Bootstrap class for initializing the Email Editor functionality.
 */
class Bootstrap {

	/**
	 * Email editor instance.
	 *
	 * @var Email_Editor
	 */
	private $email_editor;

	/**
	 * Core email editor integration instance.
	 *
	 * @var CoreEmailEditorIntegration
	 */
	private $core_email_editor_integration;

	/**
	 * Constructor.
	 *
	 * @param Email_Editor               $email_editor Email editor instance.
	 * @param CoreEmailEditorIntegration $core_email_editor_integration  Core email editor integration instance.
	 */
	public function __construct(
		Email_Editor $email_editor,
		CoreEmailEditorIntegration $core_email_editor_integration
	) {
		$this->email_editor                  = $email_editor;
		$this->core_email_editor_integration = $core_email_editor_integration;
	}

	/**
	 * Initialize the email editor functionality.
	 */
	public function init() {
		add_action(
			'init',
			array(
				$this,
				'initialize',
			)
		);

		add_filter(
			'woocommerce_email_editor_initialized',
			array(
				$this,
				'setup_email_editor_integrations',
			)
		);
	}

	/**
	 * Initialize the email editor.
	 */
	public function initialize() {
		$this->email_editor->initialize();
	}

	/**
	 * Setup email editor integrations.
	 */
	public function setup_email_editor_integrations() {
		$this->core_email_editor_integration->initialize();
	}
}
